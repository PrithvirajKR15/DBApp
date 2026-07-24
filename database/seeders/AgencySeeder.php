<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyBranch;
use App\Models\AgencyExecutive;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@kenland.in')->first()
            ?? User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();

        $storeAdmin = User::where('email', 'storeadmin@kenland.in')->first();
        $downtown = Store::where('code', 'downtown')->first();
        $palayamStore = Store::where('code', 'uptown')->first();
        $palayamAdmin = $palayamStore
            ? User::where('email', 'uptown.store@kenland.in')->first()
            : null;

        if (! $admin) {
            return;
        }

        if ($storeAdmin && $downtown && ! $storeAdmin->store_id) {
            $storeAdmin->update(['store_id' => $downtown->id]);
        }

        $zones = Zone::query()->get()->keyBy('code');

        // ── Pending requests (Store Admin submissions) ──────────────────
        $pendingRapido = $this->upsertAgency([
            'name' => 'Rapido Partners Kerala',
            'phone' => '+91 98765 10001',
            'email' => 'ops.trivandrum@rapido.example',
            'gstin' => '32AABCR1234A1Z5',
            'address_line1' => 'TC 25/1840, MG Road',
            'city' => 'Thiruvananthapuram',
            'state' => 'Kerala',
            'pincode' => '695001',
            'status' => Agency::STATUS_PENDING,
            'created_by' => $storeAdmin?->id ?? $admin->id,
            'store_id' => $downtown?->id,
        ]);

        $pendingShadow = $this->upsertAgency([
            'name' => 'Shadowfax Local Hub',
            'phone' => '+91 98765 10002',
            'email' => 'tvm@shadowfax.example',
            'gstin' => '32AABCS5678B1Z2',
            'address_line1' => 'Near Palayam Market',
            'city' => 'Thiruvananthapuram',
            'state' => 'Kerala',
            'pincode' => '695033',
            'status' => Agency::STATUS_PENDING,
            'created_by' => $palayamAdmin?->id ?? $storeAdmin?->id ?? $admin->id,
            'store_id' => $palayamStore?->id ?? $downtown?->id,
        ]);

        // ── Rejected request ────────────────────────────────────────────
        $this->upsertAgency([
            'name' => 'QuickDrop Trial Agency',
            'phone' => '+91 98765 10003',
            'email' => 'trial@quickdrop.example',
            'address_line1' => 'Kazhakkoottam Industrial Estate',
            'city' => 'Thiruvananthapuram',
            'state' => 'Kerala',
            'pincode' => '695582',
            'status' => Agency::STATUS_REJECTED,
            'created_by' => $storeAdmin?->id ?? $admin->id,
            'store_id' => $downtown?->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(3),
            'rejection_reason' => 'Incomplete company documents and GSTIN verification failed.',
        ]);

        // ── Approved agencies (Admin-created → active) ──────────────────
        $uber = $this->upsertAgency([
            'name' => 'Uber Direct Kerala',
            'phone' => '+91 98765 20001',
            'email' => 'fleet.kerala@uber.example',
            'gstin' => '32AABCU9012C1Z8',
            'address_line1' => 'Technopark Phase 1, Module 3',
            'city' => 'Thiruvananthapuram',
            'state' => 'Kerala',
            'pincode' => '695581',
            'status' => Agency::STATUS_ACTIVE,
            'created_by' => $admin->id,
            'store_id' => null,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(14),
        ]);

        $zwiggy = $this->upsertAgency([
            'name' => 'Zwiggy Fleet Services',
            'phone' => '+91 98765 20002',
            'email' => 'support@zwiggyfleet.example',
            'gstin' => '32AABCZ3456D1Z1',
            'address_line1' => 'Kowdiar Palace Road',
            'address_line2' => 'Near Museum Junction',
            'city' => 'Thiruvananthapuram',
            'state' => 'Kerala',
            'pincode' => '695003',
            'status' => Agency::STATUS_ACTIVE,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(10),
        ]);

        // Store-admin agency that Admin already approved
        $dunzo = $this->upsertAgency([
            'name' => 'Dunzo City Couriers',
            'phone' => '+91 98765 20003',
            'email' => 'pattom@dunzo.example',
            'gstin' => '32AABCD7890E1Z4',
            'address_line1' => 'Pattom Palace Road',
            'city' => 'Thiruvananthapuram',
            'state' => 'Kerala',
            'pincode' => '695004',
            'status' => Agency::STATUS_ACTIVE,
            'created_by' => $storeAdmin?->id ?? $admin->id,
            'store_id' => $downtown?->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(5),
        ]);

        // Pending agencies — draft hubs
        $this->replaceHubs($pendingRapido, [
            ['name' => 'Rapido Trivandrum Hub', 'zones' => [$zones->get('pattom'), $zones->get('ulloor')], 'cost' => 8.50, 'min' => 35.00],
        ]);
        $this->replaceHubs($pendingShadow, [
            ['name' => 'Shadowfax Central Hub', 'zones' => [$zones->get('palayam'), $zones->get('kowdiar')], 'cost' => 9.00, 'min' => 40.00],
        ]);

        $uberHubs = $this->replaceHubs($uber, [
            ['name' => 'Uber Trivandrum Hub', 'zones' => [
                $zones->get('pattom'), $zones->get('technopark'), $zones->get('kowdiar'),
                $zones->get('ulloor'), $zones->get('kesavadasapuram'),
            ], 'cost' => 12.00, 'min' => 50.00],
        ]);
        $uberHub = $uberHubs[0] ?? null;

        $zwiggyHubs = $this->replaceHubs($zwiggy, [
            ['name' => 'Zwiggy South Hub', 'zones' => [
                $zones->get('east-fort'), $zones->get('vizhinjam'), $zones->get('kovalam'),
            ], 'cost' => 10.00, 'min' => 40.00],
            ['name' => 'Zwiggy East Hub', 'zones' => [
                $zones->get('sasthamangalam'), $zones->get('technopark'), $zones->get('peroorkada'),
            ], 'cost' => 10.50, 'min' => 42.00],
        ]);
        $zwiggySouth = $zwiggyHubs[0] ?? null;
        $zwiggyEast = $zwiggyHubs[1] ?? null;

        $dunzoHubs = $this->replaceHubs($dunzo, [
            ['name' => 'Dunzo North Hub', 'zones' => [
                $zones->get('pattom'), $zones->get('ulloor'), $zones->get('murinjapalam'),
            ], 'cost' => 9.50, 'min' => 38.00],
        ]);
        $dunzoHub = $dunzoHubs[0] ?? null;

        // Executives — one per hub (Uber covers all Trivandrum zones in one hub)
        if ($uberHub) {
            $this->upsertExecutive($uber, [
                'email' => 'executive.uber@kenland.in',
                'name' => 'Arun Uber Executive',
                'mobile' => '9876511001',
            ], [$uberHub->id]);
        }

        if ($zwiggyEast) {
            $this->upsertExecutive($zwiggy, [
                'email' => 'executive.zwiggy.east@kenland.in',
                'name' => 'Meera Zwiggy East',
                'mobile' => '9876511002',
            ], [$zwiggyEast->id]);
        }

        if ($zwiggySouth) {
            $this->upsertExecutive($zwiggy, [
                'email' => 'executive.zwiggy.south@kenland.in',
                'name' => 'Suresh Zwiggy South',
                'mobile' => '9876511003',
            ], [$zwiggySouth->id]);
        }

        if ($dunzoHub) {
            $this->upsertExecutive($dunzo, [
                'email' => 'executive.dunzo@kenland.in',
                'name' => 'Priya Dunzo Executive',
                'mobile' => '9876511004',
            ], [$dunzoHub->id]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertAgency(array $data): Agency
    {
        return Agency::updateOrCreate(
            ['name' => $data['name']],
            $data
        );
    }

    /**
     * Wipe existing hubs for the agency and recreate from the given definitions.
     *
     * @param  list<array{name: string, zones: list<?Zone>, cost: float, min: float}>  $hubs
     * @return list<?AgencyBranch>
     */
    private function replaceHubs(Agency $agency, array $hubs): array
    {
        $agency->branches()->each(function (AgencyBranch $branch) {
            $branch->zones()->detach();
            $branch->executives()->detach();
            $branch->delete();
        });

        $created = [];
        foreach ($hubs as $hub) {
            $created[] = $this->upsertHub(
                $agency,
                $hub['name'],
                $hub['zones'],
                $hub['cost'],
                $hub['min']
            );
        }

        return $created;
    }

    /**
     * @param  list<?Zone>  $zones
     */
    private function upsertHub(Agency $agency, string $name, array $zones, float $costPerKm, float $minCharge): ?AgencyBranch
    {
        $zoneIds = collect($zones)->filter()->pluck('id')->unique()->values();
        if ($zoneIds->isEmpty()) {
            return null;
        }

        $hub = AgencyBranch::create([
            'agency_id' => $agency->id,
            'name' => $name,
            'cost_per_km' => $costPerKm,
            'minimum_order_charge' => $minCharge,
            'status' => AgencyBranch::STATUS_ACTIVE,
        ]);

        $hub->zones()->sync($zoneIds->all());

        return $hub->fresh(['zones']);
    }

    /**
     * @param  array{email: string, name: string, mobile: string}  $userData
     * @param  list<int>  $branchIds
     */
    private function upsertExecutive(Agency $agency, array $userData, array $branchIds): AgencyExecutive
    {
        $roleId = Role::findBySlug('executive')->id;

        $user = User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['name'],
                'mobile' => $userData['mobile'],
                'password' => Hash::make('password@123'),
                'role_id' => $roleId,
                'status' => 'Active',
            ]
        );

        $executive = AgencyExecutive::updateOrCreate(
            ['user_id' => $user->id],
            [
                'agency_id' => $agency->id,
                'status' => AgencyExecutive::STATUS_ACTIVE,
            ]
        );

        $executive->branches()->sync($branchIds);

        return $executive->fresh(['user', 'branches']);
    }
}
