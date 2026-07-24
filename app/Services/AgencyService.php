<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AgencyBranch;
use App\Models\AgencyExecutive;
use App\Models\Driver;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AgencyService
{
    /**
     * @return Collection<int, Zone>
     */
    public function zonesForUser(User $user): Collection
    {
        if ($user->isAdmin()) {
            return Zone::query()->orderBy('name')->get();
        }

        if ($user->isStoreAdmin()) {
            $store = $this->primaryStoreFor($user);

            return $store
                ? $store->zones()->orderBy('name')->get()
                : collect();
        }

        if ($user->isExecutive()) {
            $branchIds = $this->executiveBranchIds($user);

            return Zone::query()
                ->whereHas('agencyBranches', fn (Builder $q) => $q->whereIn('agency_branches.id', $branchIds ?: [0]))
                ->orderBy('name')
                ->get();
        }

        return collect();
    }

    public function primaryStoreFor(User $user): ?Store
    {
        if ($user->store_id) {
            return Store::query()->with('zones')->find($user->store_id);
        }

        return Store::query()->with('zones')->where('user_id', $user->id)->first();
    }

    /**
     * Agencies the user may list.
     *
     * @return Builder<Agency>
     */
    public function visibleAgenciesQuery(User $user): Builder
    {
        $query = Agency::query()->with(['creator', 'store', 'branches.zones']);

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isStoreAdmin()) {
            $store = $this->primaryStoreFor($user);
            $zoneIds = $store?->zones->pluck('id') ?? collect();

            return $query->where(function (Builder $q) use ($user, $store, $zoneIds) {
                $q->where('created_by', $user->id);

                if ($store) {
                    $q->orWhere('store_id', $store->id);
                }

                if ($zoneIds->isNotEmpty()) {
                    $q->orWhereHas('branches.zones', fn (Builder $b) => $b->whereIn('zones.id', $zoneIds));
                }
            });
        }

        if ($user->isExecutive()) {
            $agencyId = $user->agencyExecutive?->agency_id;

            return $query->where('id', $agencyId);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canEditAgency(User $user, Agency $agency): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStoreAdmin()) {
            return (int) $agency->created_by === (int) $user->id;
        }

        return false;
    }

    public function canDeleteAgency(User $user, Agency $agency): bool
    {
        return $this->canEditAgency($user, $agency);
    }

    /**
     * @return list<int>
     */
    public function executiveBranchIds(User $user): array
    {
        $executive = $user->agencyExecutive;

        if (! $executive) {
            return [];
        }

        return $executive->branches()->pluck('agency_branches.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAgency(User $actor, array $data): Agency
    {
        $status = $actor->isAdmin()
            ? Agency::STATUS_ACTIVE
            : Agency::STATUS_PENDING;

        $storeId = null;
        if ($actor->isStoreAdmin()) {
            $store = $this->primaryStoreFor($actor);
            if (! $store) {
                throw ValidationException::withMessages([
                    'store' => 'Store Admin must be linked to a store before registering an agency.',
                ]);
            }
            $storeId = $store->id;
        } elseif (! empty($data['store_id'])) {
            $storeId = (int) $data['store_id'];
        }

        return Agency::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'gstin' => $data['gstin'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'status' => $status,
            'created_by' => $actor->id,
            'store_id' => $storeId,
            'approved_by' => $actor->isAdmin() ? $actor->id : null,
            'approved_at' => $actor->isAdmin() ? now() : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAgency(User $actor, Agency $agency, array $data): Agency
    {
        if (! $this->canEditAgency($actor, $agency)) {
            abort(403, 'You can only edit agencies you created.');
        }

        $agency->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'gstin' => $data['gstin'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
        ]);

        return $agency->fresh(['branches.zones', 'executives.user']);
    }

    public function deleteAgency(User $actor, Agency $agency): void
    {
        if (! $this->canDeleteAgency($actor, $agency)) {
            abort(403, 'You can only delete agencies you created.');
        }

        $agency->delete();
    }

    public function approve(User $admin, Agency $agency): Agency
    {
        if (! $admin->isAdmin()) {
            abort(403);
        }

        $agency->update([
            'status' => Agency::STATUS_ACTIVE,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return $agency->fresh();
    }

    public function reject(User $admin, Agency $agency, ?string $reason = null): Agency
    {
        if (! $admin->isAdmin()) {
            abort(403);
        }

        $agency->update([
            'status' => Agency::STATUS_REJECTED,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $agency->fresh();
    }

    /**
     * Create a single hub/contract covering one or many zones.
     *
     * @param  array<string, mixed>  $data
     */
    public function createBranch(User $actor, Agency $agency, array $data): AgencyBranch
    {
        return $this->createBranches($actor, $agency, $data)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Support\Collection<int, AgencyBranch>
     */
    public function createBranches(User $actor, Agency $agency, array $data)
    {
        $this->assertCanManageBranches($actor, $agency);

        $zoneIds = collect($data['zone_ids'] ?? [])
            ->when(empty($data['zone_ids']) && ! empty($data['zone_id']), fn ($c) => collect([(int) $data['zone_id']]))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($zoneIds->isEmpty()) {
            throw ValidationException::withMessages([
                'zone_ids' => 'Select at least one zone.',
            ]);
        }

        foreach ($zoneIds as $zoneId) {
            $this->assertZoneAllowed($actor, $zoneId);
        }

        $overlapping = $agency->branches()
            ->whereHas('zones', fn (Builder $q) => $q->whereIn('zones.id', $zoneIds))
            ->with('zones')
            ->get();

        if ($overlapping->isNotEmpty()) {
            $names = $overlapping->flatMap->zones
                ->whereIn('id', $zoneIds)
                ->pluck('name')
                ->unique()
                ->join(', ');
            throw ValidationException::withMessages([
                'zone_ids' => "These zones are already covered by another hub: {$names}.",
            ]);
        }

        $name = trim((string) ($data['name'] ?? $data['name_prefix'] ?? ''));
        if ($name === '') {
            $name = $agency->name;
        }

        $branch = $agency->branches()->create([
            'name' => $name,
            'cost_per_km' => $data['cost_per_km'] ?? 0,
            'minimum_order_charge' => $data['minimum_order_charge'] ?? 0,
            'status' => $data['status'] ?? AgencyBranch::STATUS_ACTIVE,
        ]);

        $branch->zones()->sync($zoneIds->all());

        return collect([$branch->load(['zones', 'agency'])]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBranch(User $actor, AgencyBranch $branch, array $data): AgencyBranch
    {
        if ($actor->isAdmin()) {
            // allowed
        } elseif ($actor->isStoreAdmin() && $this->canEditAgency($actor, $branch->agency)) {
            // allowed
        } else {
            abort(403, 'You cannot manage branches for this agency.');
        }

        $zoneIds = null;
        if (array_key_exists('zone_ids', $data)) {
            $zoneIds = collect($data['zone_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($zoneIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'zone_ids' => 'Select at least one zone.',
                ]);
            }

            foreach ($zoneIds as $zoneId) {
                $this->assertZoneAllowed($actor, $zoneId);
            }

            $overlapping = AgencyBranch::query()
                ->where('agency_id', $branch->agency_id)
                ->where('id', '!=', $branch->id)
                ->whereHas('zones', fn (Builder $q) => $q->whereIn('zones.id', $zoneIds))
                ->with('zones')
                ->get();

            if ($overlapping->isNotEmpty()) {
                $names = $overlapping->flatMap->zones
                    ->whereIn('id', $zoneIds)
                    ->pluck('name')
                    ->unique()
                    ->join(', ');
                throw ValidationException::withMessages([
                    'zone_ids' => "These zones are already covered by another hub: {$names}.",
                ]);
            }
        }

        $branch->update([
            'name' => $data['name'] ?? $branch->name,
            'cost_per_km' => $data['cost_per_km'] ?? $branch->cost_per_km,
            'minimum_order_charge' => $data['minimum_order_charge'] ?? $branch->minimum_order_charge,
            'status' => $data['status'] ?? $branch->status,
        ]);

        if ($zoneIds !== null) {
            $branch->zones()->sync($zoneIds->all());
        }

        return $branch->fresh(['zones', 'agency']);
    }

    public function deleteBranch(User $actor, AgencyBranch $branch): void
    {
        $this->assertCanManageBranches($actor, $branch->agency);
        $branch->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $branchIds
     */
    public function createExecutive(User $actor, Agency $agency, array $data, array $branchIds): AgencyExecutive
    {
        if (! $agency->isActive() && ! $actor->isAdmin()) {
            throw ValidationException::withMessages([
                'agency' => 'Executives can only be added to approved agencies.',
            ]);
        }

        if ($actor->isStoreAdmin()) {
            $visible = $this->visibleAgenciesQuery($actor)->where('agencies.id', $agency->id)->exists();
            if (! $visible) {
                abort(403);
            }
            $this->assertBranchesInStoreZones($actor, $agency, $branchIds);
        } elseif (! $actor->isAdmin()) {
            abort(403);
        }

        $validBranchIds = $agency->branches()->whereIn('id', $branchIds)->pluck('id')->all();
        if (count($validBranchIds) === 0) {
            throw ValidationException::withMessages([
                'branch_ids' => 'Select at least one zone branch for this executive.',
            ]);
        }

        return DB::transaction(function () use ($agency, $data, $validBranchIds) {
            $roleId = Role::findBySlug('executive')->id;
            $password = $data['password'] ?? 'password@123';

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? ('9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT)),
                'password' => Hash::make($password),
                'role_id' => $roleId,
                'status' => 'Active',
                'address' => $data['address'] ?? null,
            ]);

            $executive = AgencyExecutive::create([
                'user_id' => $user->id,
                'agency_id' => $agency->id,
                'status' => AgencyExecutive::STATUS_ACTIVE,
            ]);

            $executive->branches()->sync($validBranchIds);

            return $executive->load(['user', 'branches.zones']);
        });
    }

    /**
     * @param  list<int>  $branchIds
     */
    public function syncExecutiveBranches(User $actor, AgencyExecutive $executive, array $branchIds): AgencyExecutive
    {
        if (! $actor->isAdmin() && ! ($actor->isStoreAdmin() && $this->visibleAgenciesQuery($actor)->where('agencies.id', $executive->agency_id)->exists())) {
            abort(403);
        }

        $validBranchIds = $executive->agency->branches()->whereIn('id', $branchIds)->pluck('id')->all();
        $executive->branches()->sync($validBranchIds);

        return $executive->fresh(['user', 'branches.zones']);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function shapeAgencyList(Collection $agencies): Collection
    {
        return $agencies->map(fn (Agency $agency) => $this->shapeAgency($agency));
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeAgency(Agency $agency): array
    {
        $agency->loadMissing(['creator', 'store', 'branches.zones', 'executives.user', 'executives.branches.zones']);

        return [
            'id' => $agency->id,
            'name' => $agency->name,
            'phone' => $agency->phone,
            'email' => $agency->email,
            'gstin' => $agency->gstin,
            'address_line1' => $agency->address_line1,
            'address_line2' => $agency->address_line2,
            'city' => $agency->city,
            'state' => $agency->state,
            'pincode' => $agency->pincode,
            'status' => $agency->status,
            'created_by' => $agency->created_by,
            'creator_name' => $agency->creator?->name,
            'store_id' => $agency->store_id,
            'store_name' => $agency->store?->name,
            'approved_at' => $agency->approved_at?->toDateTimeString(),
            'rejection_reason' => $agency->rejection_reason,
            'branches_count' => $agency->branches->count(),
            'executives_count' => $agency->executives->count(),
            'branches' => $agency->branches->map(fn (AgencyBranch $b) => $this->shapeBranch($b))->values(),
            'executives' => $agency->executives->map(fn (AgencyExecutive $e) => $this->shapeExecutive($e))->values(),
            'created_at' => $agency->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeBranch(AgencyBranch $branch): array
    {
        $branch->loadMissing(['zones', 'agency']);

        $zones = $branch->zones->map(fn (Zone $z) => [
            'id' => $z->id,
            'name' => $z->name,
            'code' => $z->code,
        ])->values();

        return [
            'id' => $branch->id,
            'agency_id' => $branch->agency_id,
            'agency_name' => $branch->agency?->name,
            'name' => $branch->name,
            'zone_ids' => $zones->pluck('id')->values(),
            'zones' => $zones,
            'zone_names' => $zones->pluck('name')->join(', '),
            // Backward-compatible single-zone fields (first zone)
            'zone_id' => $zones->first()['id'] ?? null,
            'zone_name' => $zones->first()['name'] ?? null,
            'zone_code' => $zones->first()['code'] ?? null,
            'cost_per_km' => $branch->cost_per_km,
            'minimum_order_charge' => $branch->minimum_order_charge,
            'status' => $branch->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeExecutive(AgencyExecutive $executive): array
    {
        $executive->loadMissing(['user', 'branches.zones']);
        $branchIds = $executive->branches->pluck('id')->values();

        return [
            'id' => $executive->id,
            'agency_id' => $executive->agency_id,
            'user_id' => $executive->user_id,
            'name' => $executive->user?->name,
            'email' => $executive->user?->email,
            'mobile' => $executive->user?->mobile,
            'status' => $executive->status,
            'branch_ids' => $branchIds,
            'drivers_count' => $this->executiveDriversQuery($executive)->count(),
            'branches' => $executive->branches->map(fn (AgencyBranch $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'zone_names' => $b->zones->pluck('name')->join(', '),
                'zone_name' => $b->zones->pluck('name')->join(', '),
            ])->values(),
        ];
    }

    /**
     * Drivers under hubs assigned to this executive.
     */
    public function executiveDriversQuery(AgencyExecutive $executive): Builder
    {
        $executive->loadMissing('branches');
        $branchIds = $executive->branches->pluck('id')->all();

        return Driver::zoneDrivers()
            ->whereIn('agency_branch_id', $branchIds ?: [0])
            ->whereHas('user', fn (Builder $q) => $q->whereIn('status', ['Active', 'Suspended']));
    }

    public function assertCanViewExecutive(User $actor, AgencyExecutive $executive): void
    {
        $visible = $this->visibleAgenciesQuery($actor)
            ->whereKey($executive->agency_id)
            ->exists();

        if (! $visible) {
            abort(403, 'You cannot view this executive.');
        }
    }

    /**
     * Hubs available for zone-driver third-party selection.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function branchesForDriverForm(User $user, ?int $zoneId = null): Collection
    {
        $query = AgencyBranch::query()
            ->with(['agency', 'zones'])
            ->where('status', AgencyBranch::STATUS_ACTIVE)
            ->whereHas('agency', fn (Builder $q) => $q->where('status', Agency::STATUS_ACTIVE));

        if ($zoneId) {
            $query->whereHas('zones', fn (Builder $q) => $q->where('zones.id', $zoneId));
        }

        if ($user->isExecutive()) {
            $query->whereIn('id', $this->executiveBranchIds($user) ?: [0]);
        } elseif ($user->isStoreAdmin()) {
            $zoneIds = $this->zonesForUser($user)->pluck('id');
            $query->whereHas('zones', fn (Builder $q) => $q->whereIn('zones.id', $zoneIds));
        }

        return $query->orderBy('name')->get()->map(fn (AgencyBranch $b) => $this->shapeBranch($b));
    }

    public function assertZoneAllowed(User $actor, int $zoneId): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        $allowed = $this->zonesForUser($actor)->pluck('id')->contains($zoneId);
        if (! $allowed) {
            throw ValidationException::withMessages([
                'zone_id' => 'You can only use zones tied to your store.',
            ]);
        }
    }

    private function assertCanManageBranches(User $actor, Agency $agency): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        if ($actor->isStoreAdmin() && $this->canEditAgency($actor, $agency)) {
            return;
        }

        abort(403, 'You cannot manage branches for this agency.');
    }

    /**
     * @param  list<int>  $branchIds
     */
    private function assertBranchesInStoreZones(User $actor, Agency $agency, array $branchIds): void
    {
        $allowedZoneIds = $this->zonesForUser($actor)->pluck('id');
        $hubs = $agency->branches()->with('zones')->whereIn('id', $branchIds)->get();

        foreach ($hubs as $hub) {
            foreach ($hub->zones as $zone) {
                if (! $allowedZoneIds->contains($zone->id)) {
                    throw ValidationException::withMessages([
                        'branch_ids' => 'One or more selected hubs include zones outside your store.',
                    ]);
                }
            }
        }
    }
}
