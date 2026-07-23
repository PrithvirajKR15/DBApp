<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\DriverDocument;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DriverService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listStoreDrivers(): Collection
    {
        return Driver::storeDrivers()
            ->with(['user', 'activeAssignment.store', 'orders'])
            ->whereHas('user')
            ->get()
            ->map(fn (Driver $driver) => $this->shapeStoreDriver($driver));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $documents
     */
    public function createStoreDriver(array $data, ?UploadedFile $avatar, array $documents): Driver
    {
        return DB::transaction(function () use ($data, $avatar, $documents) {
            [$user] = $this->createDriverUser($data, $avatar);

            $driver = $this->createDriverProfile($user, $data, [
                'driver_type' => Driver::TYPE_STORE,
            ]);

            DriverAssignment::create([
                'driver_id' => $driver->id,
                'store_id' => $data['store_id'],
                'zone_id' => null,
                'type' => 'store',
                'is_active' => true,
                'assigned_at' => now(),
            ]);

            $this->storeDocuments($driver, $documents);

            return $driver->load(['user', 'activeAssignment.store', 'orders']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $documents
     */
    public function updateStoreDriver(string $code, array $data, ?UploadedFile $avatar, array $documents): Driver
    {
        return DB::transaction(function () use ($code, $data, $avatar, $documents) {
            $driver = $this->findStoreDriverByCode($code);
            $this->updateDriverUser($driver->user, $data, $avatar);
            $this->updateDriverProfile($driver, $data);

            $assignment = $driver->activeAssignment;
            if ($assignment) {
                $assignment->update(['store_id' => $data['store_id']]);
            } else {
                DriverAssignment::create([
                    'driver_id' => $driver->id,
                    'store_id' => $data['store_id'],
                    'zone_id' => null,
                    'type' => 'store',
                    'is_active' => true,
                    'assigned_at' => now(),
                ]);
            }

            $this->storeDocuments($driver, $documents, replaceExisting: true);

            return $driver->fresh(['user', 'activeAssignment.store', 'orders']);
        });
    }

    public function deleteStoreDriver(string $code): void
    {
        DB::transaction(function () use ($code) {
            $driver = $this->findStoreDriverByCode($code);
            $driver->user?->delete();
        });
    }

    public function findStoreDriverByCode(string $code): Driver
    {
        return Driver::storeDrivers()
            ->whereHas('user', fn ($q) => $q->where('code', $code))
            ->with(['user', 'activeAssignment.store', 'orders'])
            ->firstOrFail();
    }

    /**
     * Hired zone fleet only (Active + Suspended).
     * Pending / Rejected applications stay on Approvals.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listZoneDrivers(): Collection
    {
        return Driver::zoneDrivers()
            ->with(['user', 'activeAssignment.zone', 'orders'])
            ->whereHas('user', fn ($q) => $q->whereIn('status', ['Active', 'Suspended']))
            ->get()
            ->map(fn (Driver $driver) => $this->shapeZoneDriver($driver));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $documents
     */
    public function createZoneDriver(array $data, ?UploadedFile $avatar, array $documents): Driver
    {
        return DB::transaction(function () use ($data, $avatar, $documents) {
            [$user] = $this->createDriverUser($data, $avatar);

            $driver = $this->createDriverProfile($user, $data, [
                'driver_type' => Driver::TYPE_THIRD_PARTY,
                'partner_type' => $data['partner_type'] ?? 'independent',
                'agency_name' => $data['agency_name'] ?? null,
                'agency_id' => $data['agency_id'] ?? null,
                'service_areas' => $this->normalizeServiceAreas($data['zone_id'], $data['service_areas'] ?? []),
            ]);

            DriverAssignment::create([
                'driver_id' => $driver->id,
                'store_id' => null,
                'zone_id' => $data['zone_id'],
                'type' => 'zone',
                'is_active' => true,
                'assigned_at' => now(),
            ]);

            $this->storeDocuments($driver, $documents);

            return $driver->load(['user', 'activeAssignment.zone', 'orders']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $documents
     */
    public function updateZoneDriver(string $code, array $data, ?UploadedFile $avatar, array $documents): Driver
    {
        return DB::transaction(function () use ($code, $data, $avatar, $documents) {
            $driver = $this->findZoneDriverByCode($code);
            $this->updateDriverUser($driver->user, $data, $avatar);

            $driver->update([
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'vehicle_brand' => $data['vehicle_brand'] ?? null,
                'vehicle_model' => $data['vehicle_model'] ?? null,
                'plate_number' => $data['plate_number'] ?? null,
                'vehicle_fuel' => $data['vehicle_fuel'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'shift' => $data['shift'] ?? null,
                'working_days' => $data['working_days'] ?? null,
                'partner_type' => $data['partner_type'] ?? 'independent',
                'agency_name' => $data['agency_name'] ?? null,
                'agency_id' => $data['agency_id'] ?? null,
                'service_areas' => $this->normalizeServiceAreas($data['zone_id'], $data['service_areas'] ?? []),
                'availability' => $data['availability'] ?? $driver->availability ?? 'Offline',
            ]);

            $assignment = $driver->activeAssignment;
            if ($assignment) {
                $assignment->update(['zone_id' => $data['zone_id']]);
            } else {
                DriverAssignment::create([
                    'driver_id' => $driver->id,
                    'store_id' => null,
                    'zone_id' => $data['zone_id'],
                    'type' => 'zone',
                    'is_active' => true,
                    'assigned_at' => now(),
                ]);
            }

            $this->storeDocuments($driver, $documents, replaceExisting: true);

            return $driver->fresh(['user', 'activeAssignment.zone', 'orders']);
        });
    }

    public function deleteZoneDriver(string $code): void
    {
        DB::transaction(function () use ($code) {
            $driver = $this->findZoneDriverByCode($code);
            $driver->user?->delete();
        });
    }

    public function findZoneDriverByCode(string $code): Driver
    {
        return Driver::zoneDrivers()
            ->whereHas('user', fn ($q) => $q->where('code', $code))
            ->with(['user', 'activeAssignment.zone', 'orders'])
            ->firstOrFail();
    }

    public function findDriverByCode(string $code): Driver
    {
        return Driver::query()
            ->whereHas('user', fn ($q) => $q->where('code', $code))
            ->with(['user', 'activeAssignment.store', 'activeAssignment.zone', 'orders'])
            ->firstOrFail();
    }

    /**
     * Update personal fields editable from the driver profile page.
     *
     * @param  array{name: string, email: string, mobile: string, address?: string|null}  $data
     */
    public function updateDriverPersonalInfo(string $code, array $data): Driver
    {
        $driver = $this->findDriverByCode($code);
        $user = $driver->user;

        $user->update([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'mobile' => $this->formatMobile($data['mobile']),
            'address' => $data['address'] ?? null,
        ]);

        return $driver->fresh(['user', 'activeAssignment.store', 'activeAssignment.zone', 'orders']);
    }

    /**
     * @param  array{status?: string, availability?: string}  $data
     */
    public function updateDriverAccountStatus(string $code, array $data): Driver
    {
        $driver = $this->findDriverByCode($code);

        if (isset($data['status'])) {
            $driver->user->update([
                'status' => $this->normalizeAccountStatus($data['status']),
            ]);
        }

        if (isset($data['availability'])) {
            $driver->update([
                'availability' => $this->normalizeAvailability($data['availability']),
            ]);
        }

        return $driver->fresh(['user', 'activeAssignment.store', 'activeAssignment.zone', 'orders']);
    }

    /**
     * Drivers in the application queue: Pending Review + Rejected.
     * Suspended is for hired drivers only (fleet list / profile), not approvals.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listApprovalDrivers(): Collection
    {
        return Driver::query()
            ->whereHas('user', fn ($q) => $q->whereIn('status', ['Pending', 'Rejected']))
            ->with(['user', 'activeAssignment.store', 'activeAssignment.zone', 'documents'])
            ->get()
            ->sortByDesc(fn (Driver $driver) => $driver->user?->created_at)
            ->values()
            ->map(fn (Driver $driver) => $this->shapeApprovalDriver($driver));
    }

    public function countApprovalDriversByStatus(string $status): int
    {
        $status = $this->normalizeAccountStatus($status);

        return Driver::query()
            ->whereHas('user', fn ($q) => $q->where('status', $status))
            ->count();
    }

    /**
     * Shape a driver row for the Approvals registrations table.
     *
     * @return array<string, mixed>
     */
    public function shapeApprovalDriver(Driver $driver): array
    {
        $shaped = $this->shapeDriver($driver);
        $user = $driver->user;
        $registeredAt = $user?->created_at ?? $driver->joined_at;

        $vehicleParts = array_filter([
            $this->formatVehicleTypeLabel($driver->vehicle_type),
            $driver->vehicle_brand,
        ]);
        $vehicle = $vehicleParts
            ? implode(' · ', $vehicleParts)
            : ($driver->vehicle_model ?: '—');

        $partnerType = ($driver->partner_type ?? 'independent') === 'third-party'
            ? 'Third-party'
            : 'Independent';

        $serviceArea = $shaped['type'] === 'zone'
            ? ($shaped['zone'] ?? 'None')
            : ($shaped['store'] ?? 'None');

        $hasAllDocs = $this->hasAllRequiredDocuments($driver);
        $status = $this->normalizeAccountStatus($user?->status);
        $subtext = '';
        if ($status === 'Pending') {
            $subtext = $hasAllDocs ? 'Ready for approval' : 'Awaiting docs';
        }

        return [
            'id' => $shaped['id'],
            'name' => $shaped['name'],
            'phone' => $shaped['phone'],
            'email' => $shaped['email'],
            'avatar' => $shaped['avatar'],
            'partnerType' => $partnerType,
            'partner_type' => $driver->partner_type ?? 'independent',
            'vehicle' => $vehicle,
            'serviceArea' => $serviceArea === 'None' ? '—' : $serviceArea,
            'type' => $shaped['type'],
            'zone' => $shaped['zone'] ?? null,
            'zone_id' => $shaped['zone_id'] ?? null,
            'store' => $shaped['store'] ?? null,
            'date' => $registeredAt ? $registeredAt->format('M j, Y') : '',
            'time' => $registeredAt ? $registeredAt->format('h:i A') : '',
            'timestamp' => $registeredAt?->toDateTimeString(),
            'status' => $status,
            'subtext' => $subtext,
            'docs_complete' => $hasAllDocs,
        ];
    }

    private function formatVehicleTypeLabel(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        return match (strtolower($type)) {
            'scooter' => 'Scooter',
            'motorcycle', 'bike' => 'Motorcycle',
            'car' => 'Car',
            'van' => 'Van',
            'bicycle', 'cycle' => 'Bicycle',
            default => ucfirst($type),
        };
    }

    private function hasAllRequiredDocuments(Driver $driver): bool
    {
        $uploaded = $driver->relationLoaded('documents')
            ? $driver->documents->pluck('doc_type')->unique()->all()
            : $driver->documents()->pluck('doc_type')->unique()->all();

        foreach (DriverDocument::REQUIRED_TYPES as $type) {
            if (! in_array($type, $uploaded, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeDriver(Driver $driver): array
    {
        $type = $driver->activeAssignment?->type;

        if ($type === 'zone') {
            return $this->shapeZoneDriver($driver);
        }

        return $this->shapeStoreDriver($driver);
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeZoneDriver(Driver $driver): array
    {
        $user = $driver->user;
        $zone = $driver->activeAssignment?->zone;
        $joinedAt = $driver->joined_at ?? $user?->created_at;
        $joinedDate = $joinedAt ? \Illuminate\Support\Carbon::parse($joinedAt)->toDateString() : null;
        $rating = $driver->rating !== null ? number_format((float) $driver->rating, 1) : '—';

        return array_merge($this->baseShapedDriver($driver, $user, $joinedDate, $rating), [
            'type' => 'zone',
            'store' => 'None',
            'zone' => $zone?->name ?? 'None',
            'zone_id' => $zone?->id,
            'zone_code' => $zone?->code,
            'partner_type' => $driver->partner_type ?? 'independent',
            'agency_name' => $driver->agency_name,
            'agency_id' => $driver->agency_id,
            'service_areas' => $this->normalizeLegacyServiceAreas($driver->service_areas ?? []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeStoreDriver(Driver $driver): array
    {
        $user = $driver->user;
        $store = $driver->activeAssignment?->store;
        $joinedAt = $driver->joined_at ?? $user?->created_at;
        $joinedDate = $joinedAt ? \Illuminate\Support\Carbon::parse($joinedAt)->toDateString() : null;
        $rating = $driver->rating !== null ? number_format((float) $driver->rating, 1) : '—';

        return array_merge($this->baseShapedDriver($driver, $user, $joinedDate, $rating), [
            'type' => 'store',
            'store' => $store?->name ?? 'None',
            'store_id' => $store?->id,
            'zone' => 'None',
            'partner_type' => $driver->partner_type ?? 'independent',
            'service_areas' => $driver->service_areas ?? [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createDriverProfile(User $user, array $data, array $extra = []): Driver
    {
        return Driver::create(array_merge([
            'user_id' => $user->id,
            'joined_at' => now()->toDateString(),
            'availability' => $data['availability'] ?? 'Offline',
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'vehicle_brand' => $data['vehicle_brand'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'vehicle_fuel' => $data['vehicle_fuel'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'shift' => $data['shift'] ?? null,
            'working_days' => $data['working_days'] ?? null,
        ], $extra));
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function createDriverUser(array $data, ?UploadedFile $avatar): array
    {
        $roleId = Role::findBySlug('user')->id;
        // $plainPassword = Str::random(12);
        $plainPassword = 'password';
        $mobile = $this->formatMobile($data['mobile']);

        $user = User::create([
            'name' => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
            'email' => $data['email'],
            'mobile' => $mobile,
            'password' => Hash::make($plainPassword),
            'role_id' => $roleId,
            'code' => $this->nextDriverCode(),
            'status' => $this->normalizeAccountStatus($data['status'] ?? 'Active'),
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'image' => $avatar ? $avatar->store('drivers/avatars', 'public') : null,
            'dev_remark' => $this->encodeDevRemark($data['email'], $mobile, $plainPassword),
        ]);

        return [$user, $plainPassword];
    }

    private function updateDriverUser(User $user, array $data, ?UploadedFile $avatar): void
    {
        $mobile = $this->formatMobile($data['mobile']);

        $user->update([
            'name' => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
            'email' => $data['email'],
            'mobile' => $mobile,
            'status' => isset($data['status'])
                ? $this->normalizeAccountStatus($data['status'])
                : $user->status,
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        if ($avatar) {
            $user->update([
                'image' => $avatar->store('drivers/avatars', 'public'),
            ]);
        }
    }

    private function updateDriverProfile(Driver $driver, array $data): void
    {
        $driver->update([
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'vehicle_brand' => $data['vehicle_brand'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'vehicle_fuel' => $data['vehicle_fuel'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'shift' => $data['shift'] ?? null,
            'working_days' => $data['working_days'] ?? null,
            'availability' => $data['availability'] ?? $driver->availability ?? 'Offline',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseShapedDriver(Driver $driver, ?User $user, ?string $joinedAt, string $rating): array
    {
        $image = $user?->image;
        if ($image && ! str_starts_with($image, 'data:')) {
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                // keep absolute URLs as-is
            } elseif (str_starts_with($image, '/storage/') || str_starts_with($image, 'storage/')) {
                $image = asset(ltrim($image, '/'));
            } elseif (str_contains($image, '/')) {
                // Uploaded paths like drivers/avatars/xyz.png
                $image = asset('storage/' . ltrim($image, '/'));
            } else {
                // Seeded filenames like 5.png live under public avatars
                $image = asset('assets/img/avatars/' . $image);
            }
        }

        return [
            'id' => $user?->code,
            'name' => $user?->name,
            'phone' => $user?->mobile,
            'avatar' => $image ?? '1.png',
            'status' => $this->normalizeAccountStatus($user?->status),
            'availability' => $this->normalizeAvailability($driver->availability),
            'rating' => $rating,
            'deliveries' => $driver->deliveries,
            'joined' => $joinedAt ? date('F j, Y', strtotime($joinedAt)) : '',
            'timestamp' => $joinedAt,
            'email' => $user?->email,
            'dob' => $user?->dob?->format('Y-m-d'),
            'gender' => $user?->gender,
            'address' => $user?->address,
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_brand' => $driver->vehicle_brand,
            'vehicle_model' => $driver->vehicle_model,
            'plate_number' => $driver->plate_number,
            'vehicle_fuel' => $driver->vehicle_fuel,
            'license_required' => 'Yes',
            'license_number' => $driver->license_number,
            'shift' => $driver->shift,
            'working_days' => $driver->working_days ?? [],
        ];
    }

    /**
     * Canonical account statuses:
     * Pending → application awaiting review
     * Active → approved / hired
     * Rejected → application denied
     * Suspended → hired driver temporarily blocked
     */
    public function normalizeAccountStatus(?string $status): string
    {
        return match ($status) {
            'Pending Review', 'Docs Verified', 'Pending' => 'Pending',
            'Approved', 'Active', 'Offline' => 'Active',
            'Rejected' => 'Rejected',
            'Suspended' => 'Suspended',
            default => 'Pending',
        };
    }

    /**
     * Canonical availability values: Online, Offline, Transit.
     */
    public function normalizeAvailability(?string $availability): string
    {
        return match ($availability) {
            'Online', 'Transit', 'Offline' => $availability,
            default => 'Offline',
        };
    }

    /**
     * @param  array<int, string>  $serviceAreas
     * @return array<int, string>
     */
    private function normalizeServiceAreas(int $primaryZoneId, array $serviceAreas): array
    {
        $primaryCode = Zone::whereKey($primaryZoneId)->value('code');

        return collect($serviceAreas)
            ->map(fn ($area) => $this->legacyAreaKeyToCode((string) $area))
            ->filter()
            ->unique()
            ->reject(fn ($code) => $code === $primaryCode)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $serviceAreas
     * @return array<int, string>
     */
    private function normalizeLegacyServiceAreas(array $serviceAreas): array
    {
        return collect($serviceAreas)
            ->map(fn ($area) => $this->legacyAreaKeyToCode((string) $area))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function legacyAreaKeyToCode(string $area): ?string
    {
        if (Zone::where('code', $area)->exists()) {
            return $area;
        }

        $legacyMap = [
            'downtown' => 'pattom',
            'northwest' => 'pattom',
            'southeast' => 'east-fort',
            'uptown' => 'palayam',
            'east' => 'technopark',
            'west' => 'medical-college',
            'midtown' => 'kowdiar',
            'pattom' => 'pattom',
            'kesavadasapuram' => 'kesavadasapuram',
            'ulloor' => 'ulloor',
            'murinjapalam' => 'murinjapalam',
            'kowdiar' => 'kowdiar',
            'palayam' => 'palayam',
            'thampanoor' => 'thampanoor',
            'vellayambalam' => 'vellayambalam',
            'statue' => 'statue',
            'sasthamangalam' => 'sasthamangalam',
            'technopark' => 'technopark',
            'peroorkada' => 'peroorkada',
            'medical-college' => 'medical-college',
            'kazhakkoottam' => 'kazhakkoottam',
            'east-fort' => 'east-fort',
            'vizhinjam' => 'vizhinjam',
            'kovalam' => 'kovalam',
        ];

        return $legacyMap[$area] ?? null;
    }

    private function nextDriverCode(): string
    {
        $lastCode = User::withTrashed()
            ->where('code', 'like', 'DRV-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC')
            ->value('code');

        $nextNumber = $lastCode ? ((int) substr($lastCode, 4)) + 1 : 0001;

        do {
            $code = 'DRV-' . $nextNumber;
            $exists = User::withTrashed()->where('code', $code)->exists();
            $nextNumber++;
        } while ($exists);

        return $code;
    }

    private function formatMobile(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile) ?? '';

        if (strlen($digits) === 10) {
            return '+91 ' . substr($digits, 0, 5) . ' ' . substr($digits, 5);
        }

        return $mobile;
    }

    private function encodeDevRemark(string $email, string $mobile, string $password): string
    {
        return base64_encode(json_encode([
            'email' => $email,
            'mobile' => $mobile,
            'password' => $password,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, UploadedFile|null>  $documents
     */
    private function storeDocuments(Driver $driver, array $documents, bool $replaceExisting = false): void
    {
        foreach (DriverDocument::REQUIRED_TYPES as $type) {
            $file = $documents[$type] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($replaceExisting) {
                $driver->documents()->where('doc_type', $type)->delete();
            }

            $path = $file->store("drivers/{$driver->id}/documents", 'public');

            DriverDocument::create([
                'driver_id' => $driver->id,
                'doc_type' => $type,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'uploaded_at' => now(),
            ]);
        }
    }
}
