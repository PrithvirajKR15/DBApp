<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\DriverEarning;
use App\Models\DriverLocation;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DriverSeeder extends Seeder
{
    /**
     * Mobiles already handed out, so every driver-user gets a unique login.
     *
     * @var array<string, true>
     */
    private array $usedMobiles = [];

    public function run(): void
    {
        $userRoleId = Role::findBySlug('user')->id;
        $storesByName = Store::pluck('id', 'name');
        $this->usedMobiles = User::pluck('mobile')->flip()->map(fn () => true)->all();

        // 1) Fleet / roster drivers migrated from the old static data file.
        $fleet = include resource_path('views/content/pages/drivers-data.php');

        foreach ($fleet as $d) {
            $rating = ($d['rating'] ?? '—') === '—' ? null : (float) $d['rating'];
            $serviceAreas = $d['service_areas'] ?? ($d['coverage_areas'] ?? null);

            $user = User::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'mobile' => $this->uniqueMobile($d['phone'] ?? Str::random(10)),
                    'password' => Hash::make('password@123'),
                    'role_id' => $userRoleId,
                    'code' => $d['id'],
                    'status' => $this->accountStatus($d['status'] ?? 'Active'),
                    'image' => $d['avatar'] ?? null,
                    'dob' => $d['dob'] ?? null,
                    'gender' => $d['gender'] ?? null,
                    'address' => $d['address'] ?? null,
                ]
            );

            $driver = Driver::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'rating' => $rating,
                    'joined_at' => $d['timestamp'] ?? null,
                    'availability' => $d['availability'] ?? $this->availabilityFromLegacyStatus($d['status'] ?? 'Active'),
                    'vehicle_type' => $d['vehicle_type'] ?? null,
                    'vehicle_brand' => $d['vehicle_brand'] ?? null,
                    'vehicle_model' => $d['vehicle_model'] ?? null,
                    'plate_number' => $d['plate_number'] ?? null,
                    'vehicle_fuel' => $d['vehicle_fuel'] ?? null,
                    'license_number' => $d['license_number'] ?? null,
                    'shift' => $d['shift'] ?? null,
                    'working_days' => $d['working_days'] ?? null,
                    'partner_type' => $d['partner_type'] ?? null,
                    'service_areas' => $serviceAreas,
                ]
            );

            $amount = isset($d['earnings']) ? (float) preg_replace('/[^0-9.]/', '', $d['earnings']) : 0;
            if ($amount > 0) {
                DriverEarning::updateOrCreate(
                    ['driver_id' => $driver->id, 'period' => 'total'],
                    ['amount' => $amount, 'earned_at' => now()]
                );
            }

            $storeId = $storesByName[$d['store'] ?? null] ?? null;
            $zoneName = ($d['zone'] ?? 'None') === 'None' ? null : $d['zone'];
            $this->assign($driver, $storeId, $zoneName ? $this->zoneId($zoneName) : null);
        }

        // 2) Drivers that appear on the live map (with a real-time location fix).
        $liveDrivers = [
            ['code' => 'DRV-9001', 'name' => 'Mike Johnson', 'image' => '5.png', 'rating' => 4.8, 'zone' => 'Manhattan Core', 'live_status' => 'Transit', 'lat' => 40.7128, 'lng' => -74.0060, 'speed_kmh' => 32],
            ['code' => 'DRV-9002', 'name' => 'Sarah Connor', 'image' => '6.png', 'rating' => 4.9, 'zone' => 'Manhattan Core', 'live_status' => 'Transit', 'lat' => 40.7250, 'lng' => -74.0150, 'speed_kmh' => 28],
            ['code' => 'DRV-9003', 'name' => 'David Smith', 'image' => '7.png', 'rating' => 4.5, 'zone' => 'Manhattan Core', 'live_status' => 'Transit', 'lat' => 40.7050, 'lng' => -73.9960, 'speed_kmh' => 40],
            ['code' => 'DRV-9004', 'name' => 'Emily Rodriguez', 'image' => '2.png', 'rating' => 4.9, 'zone' => 'Downtown Zone', 'live_status' => 'Idle', 'lat' => 40.7180, 'lng' => -74.0010],
            ['code' => 'DRV-9005', 'name' => 'James Park', 'image' => '3.png', 'rating' => 4.7, 'zone' => 'Northwest District', 'live_status' => 'Idle', 'lat' => 40.7290, 'lng' => -73.9890],
            ['code' => 'DRV-9006', 'name' => 'Carlos Mendes', 'image' => '4.png', 'rating' => 4.6, 'zone' => 'Manhattan Core', 'live_status' => 'Transit', 'lat' => 40.7110, 'lng' => -74.0130, 'speed_kmh' => 22],
            ['code' => 'DRV-9007', 'name' => 'Lisa Chen', 'image' => '1.png', 'rating' => 4.4, 'zone' => 'Brooklyn Zone', 'live_status' => 'Offline', 'lat' => 40.7200, 'lng' => -73.9750, 'recorded_min_ago' => 45],
        ];

        foreach ($liveDrivers as $d) {
            $slug = Str::slug($d['name'], '.');

            $user = User::updateOrCreate(
                ['email' => $slug . '@fleet.kenland.in'],
                [
                    'name' => $d['name'],
                    'mobile' => $this->uniqueMobile('+1 555 ' . str_pad((string) (crc32($d['code']) % 10000), 4, '0', STR_PAD_LEFT)),
                    'password' => Hash::make('password@123'),
                    'role_id' => $userRoleId,
                    'code' => $d['code'],
                    'status' => 'Active',
                    'image' => $d['image'],
                ]
            );

            $driver = Driver::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'rating' => $d['rating'],
                    'availability' => $d['live_status'] === 'Offline' ? 'Offline' : 'Online',
                ]
            );

            $this->assign($driver, null, $this->zoneId($d['zone']));

            DriverLocation::updateOrCreate(
                ['driver_id' => $driver->id],
                [
                    'lat' => $d['lat'],
                    'lng' => $d['lng'],
                    'speed_kmh' => $d['speed_kmh'] ?? null,
                    'live_status' => $d['live_status'],
                    'recorded_at' => now()->subMinutes($d['recorded_min_ago'] ?? 0),
                ]
            );
        }
    }

    /**
     * Resolve a zone name to its id, creating the zone if it is missing.
     */
    private function zoneId(string $name): int
    {
        return Zone::firstOrCreate(
            ['name' => $name],
            ['code' => Str::slug($name)]
        )->id;
    }

    /**
     * Create/refresh the driver's active store or zone assignment.
     */
    private function assign(Driver $driver, ?int $storeId, ?int $zoneId): void
    {
        DriverAssignment::updateOrCreate(
            ['driver_id' => $driver->id],
            [
                'store_id' => $storeId,
                'zone_id' => $zoneId,
                'type' => $storeId ? 'store' : 'zone',
                'is_active' => true,
                'assigned_at' => now(),
            ]
        );
    }

    private function uniqueMobile(string $mobile): string
    {
        $candidate = $mobile;
        $suffix = 1;

        while (isset($this->usedMobiles[$candidate])) {
            $candidate = $mobile . $suffix;
            $suffix++;
        }

        $this->usedMobiles[$candidate] = true;

        return $candidate;
    }

    /**
     * Map legacy combined status values onto account status.
     */
    private function accountStatus(string $legacyStatus): string
    {
        return match ($legacyStatus) {
            'Offline', 'Active', 'Approved' => 'Active',
            'Pending Review', 'Docs Verified', 'Pending' => 'Pending',
            'Suspended' => 'Suspended',
            default => 'Active',
        };
    }

    /**
     * Map legacy combined status values onto operational availability.
     */
    private function availabilityFromLegacyStatus(string $legacyStatus): string
    {
        return match ($legacyStatus) {
            'Offline' => 'Offline',
            'Active', 'Approved' => 'Online',
            default => 'Offline',
        };
    }
}
