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

            $driver = $this->createDriverProfile($user, $data);

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
     * @return Collection<int, array<string, mixed>>
     */
    public function listZoneDrivers(): Collection
    {
        return Driver::zoneDrivers()
            ->with(['user', 'activeAssignment.zone', 'orders'])
            ->whereHas('user')
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

    /**
     * @return array<string, mixed>
     */
    public function shapeZoneDriver(Driver $driver): array
    {
        $user = $driver->user;
        $zone = $driver->activeAssignment?->zone;
        $joinedAt = $driver->joined_at ?? $user?->created_at?->toDateString();
        $rating = $driver->rating !== null ? number_format((float) $driver->rating, 1) : '—';

        return array_merge($this->baseShapedDriver($driver, $user, $joinedAt, $rating), [
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
        $joinedAt = $driver->joined_at ?? $user?->created_at?->toDateString();
        $rating = $driver->rating !== null ? number_format((float) $driver->rating, 1) : '—';

        return array_merge($this->baseShapedDriver($driver, $user, $joinedAt, $rating), [
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
            'status' => ($data['status'] ?? 'Active') === 'Offline' ? 'Active' : ($data['status'] ?? 'Active'),
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'image' => $avatar ? $avatar->store('drivers/image', 'public') : null,
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
            'status' => $data['status'] ?? $user->status,
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
            } elseif (str_contains($image, '/')) {
                $image = asset('storage/' . ltrim(str_replace('storage/', '', $image), '/'));
            } else {
                $image = asset('assets/img/avatars/' . $image);
            }
        }

        return [
            'id' => $user?->code,
            'name' => $user?->name,
            'phone' => $user?->mobile,
            'avatar' => $image ?? '1.png',
            'status' => $user?->status ?? 'Pending',
            'availability' => $driver->availability ?? 'Offline',
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
            'downtown' => 'downtown-zone',
            'northwest' => 'northwest-district',
            'southeast' => 'southeast-hub',
            'uptown' => 'uptown-area',
            'east' => 'east-side',
            'west' => 'west-end',
            'midtown' => 'midtown',
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
