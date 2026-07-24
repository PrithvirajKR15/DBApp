<?php

namespace Database\Seeders;

use App\Models\AgencyBranch;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\DriverEarning;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Third-party zone drivers as if created by agency executives.
 * Visible on Zone-wise Drivers and under each executive's My Drivers list.
 */
class AgencyDriverSeeder extends Seeder
{
    public function run(): void
    {
        $userRoleId = Role::findBySlug('user')->id;

        $hubs = AgencyBranch::query()
            ->with(['agency', 'zones'])
            ->where('status', AgencyBranch::STATUS_ACTIVE)
            ->whereHas('agency', fn ($q) => $q->where('status', 'active'))
            ->get()
            ->keyBy('name');

        if ($hubs->isEmpty()) {
            $this->command?->warn('AgencyDriverSeeder: no active agency hubs found. Run AgencySeeder first.');

            return;
        }

        $drivers = [
            // Uber Trivandrum Hub (executive.uber@kenland.in)
            [
                'email' => 'ravi.uber@agency.kenland.in',
                'name' => 'Ravi Kumar',
                'code' => 'DRV-AGY-1001',
                'mobile' => '9876502001',
                'hub' => 'Uber Trivandrum Hub',
                'zone' => 'Pattom',
                'reg' => 'UBR-TVM-1001',
                'availability' => 'Online',
                'vehicle_type' => 'scooter',
                'vehicle_brand' => 'Ather',
                'vehicle_model' => '450X',
                'plate_number' => 'KL-01-UB-1001',
                'rating' => 4.7,
            ],
            [
                'email' => 'anil.uber@agency.kenland.in',
                'name' => 'Anil Nair',
                'code' => 'DRV-AGY-1002',
                'mobile' => '9876502002',
                'hub' => 'Uber Trivandrum Hub',
                'zone' => 'Technopark',
                'reg' => 'UBR-TVM-1002',
                'availability' => 'Online',
                'vehicle_type' => 'motorcycle',
                'vehicle_brand' => 'Honda',
                'vehicle_model' => 'Activa',
                'plate_number' => 'KL-01-UB-1002',
                'rating' => 4.5,
            ],
            [
                'email' => 'vijay.uber@agency.kenland.in',
                'name' => 'Vijay Menon',
                'code' => 'DRV-AGY-1003',
                'mobile' => '9876502003',
                'hub' => 'Uber Trivandrum Hub',
                'zone' => 'Kowdiar',
                'reg' => 'UBR-TVM-1003',
                'availability' => 'Offline',
                'vehicle_type' => 'scooter',
                'vehicle_brand' => 'TVS',
                'vehicle_model' => 'iQube',
                'plate_number' => 'KL-01-UB-1003',
                'rating' => 4.6,
            ],
            [
                'email' => 'suresh.uber@agency.kenland.in',
                'name' => 'Suresh Pillai',
                'code' => 'DRV-AGY-1004',
                'mobile' => '9876502004',
                'hub' => 'Uber Trivandrum Hub',
                'zone' => 'Ulloor',
                'reg' => 'UBR-TVM-1004',
                'availability' => 'Transit',
                'vehicle_type' => 'motorcycle',
                'vehicle_brand' => 'Bajaj',
                'vehicle_model' => 'Pulsar',
                'plate_number' => 'KL-01-UB-1004',
                'rating' => 4.4,
            ],

            // Zwiggy South Hub
            [
                'email' => 'fazil.zwiggy@agency.kenland.in',
                'name' => 'Fazil Hassan',
                'code' => 'DRV-AGY-2001',
                'mobile' => '9876502101',
                'hub' => 'Zwiggy South Hub',
                'zone' => 'East Fort',
                'reg' => 'ZWG-STH-2001',
                'availability' => 'Online',
                'vehicle_type' => 'scooter',
                'vehicle_brand' => 'Ola',
                'vehicle_model' => 'S1',
                'plate_number' => 'KL-01-ZW-2001',
                'rating' => 4.8,
            ],
            [
                'email' => 'joseph.zwiggy@agency.kenland.in',
                'name' => 'Joseph Thomas',
                'code' => 'DRV-AGY-2002',
                'mobile' => '9876502102',
                'hub' => 'Zwiggy South Hub',
                'zone' => 'Vizhinjam',
                'reg' => 'ZWG-STH-2002',
                'availability' => 'Online',
                'vehicle_type' => 'motorcycle',
                'vehicle_brand' => 'Hero',
                'vehicle_model' => 'Pleasure',
                'plate_number' => 'KL-01-ZW-2002',
                'rating' => 4.3,
            ],

            // Zwiggy East Hub
            [
                'email' => 'arjun.zwiggy@agency.kenland.in',
                'name' => 'Arjun Das',
                'code' => 'DRV-AGY-2101',
                'mobile' => '9876502111',
                'hub' => 'Zwiggy East Hub',
                'zone' => 'Sasthamangalam',
                'reg' => 'ZWG-EST-2101',
                'availability' => 'Offline',
                'vehicle_type' => 'scooter',
                'vehicle_brand' => 'Yamaha',
                'vehicle_model' => 'Fascino',
                'plate_number' => 'KL-01-ZW-2101',
                'rating' => 4.5,
            ],

            // Dunzo North Hub
            [
                'email' => 'manoj.dunzo@agency.kenland.in',
                'name' => 'Manoj Krishnan',
                'code' => 'DRV-AGY-3001',
                'mobile' => '9876502201',
                'hub' => 'Dunzo North Hub',
                'zone' => 'Pattom',
                'reg' => 'DNZ-NTH-3001',
                'availability' => 'Online',
                'vehicle_type' => 'scooter',
                'vehicle_brand' => 'Ather',
                'vehicle_model' => 'Rizta',
                'plate_number' => 'KL-01-DZ-3001',
                'rating' => 4.9,
            ],
            [
                'email' => 'deepak.dunzo@agency.kenland.in',
                'name' => 'Deepak Varma',
                'code' => 'DRV-AGY-3002',
                'mobile' => '9876502202',
                'hub' => 'Dunzo North Hub',
                'zone' => 'Ulloor',
                'reg' => 'DNZ-NTH-3002',
                'availability' => 'Transit',
                'vehicle_type' => 'motorcycle',
                'vehicle_brand' => 'Honda',
                'vehicle_model' => 'Dio',
                'plate_number' => 'KL-01-DZ-3002',
                'rating' => 4.2,
            ],
            [
                'email' => 'nikhil.dunzo@agency.kenland.in',
                'name' => 'Nikhil Raj',
                'code' => 'DRV-AGY-3003',
                'mobile' => '9876502203',
                'hub' => 'Dunzo North Hub',
                'zone' => 'Murinjapalam',
                'reg' => 'DNZ-NTH-3003',
                'availability' => 'Online',
                'vehicle_type' => 'scooter',
                'vehicle_brand' => 'TVS',
                'vehicle_model' => 'Jupiter',
                'plate_number' => 'KL-01-DZ-3003',
                'rating' => 4.6,
            ],
        ];

        $created = 0;

        foreach ($drivers as $row) {
            $hub = $hubs->get($row['hub']);
            if (! $hub) {
                continue;
            }

            $zone = $hub->zones->firstWhere('name', $row['zone']);
            if (! $zone) {
                $zone = $hub->zones->first();
            }
            if (! $zone) {
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'mobile' => $row['mobile'],
                    'password' => Hash::make('password@123'),
                    'role_id' => $userRoleId,
                    'code' => $row['code'],
                    'status' => 'Active',
                    'gender' => 'Male',
                    'address' => $zone->name . ', Thiruvananthapuram',
                ]
            );

            $driver = Driver::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'driver_type' => Driver::TYPE_THIRD_PARTY,
                    'partner_type' => 'third-party',
                    'agency_branch_id' => $hub->id,
                    'agency_registration_number' => $row['reg'],
                    'rating' => $row['rating'],
                    'joined_at' => now()->subDays(rand(10, 90))->toDateString(),
                    'availability' => $row['availability'],
                    'vehicle_type' => $row['vehicle_type'],
                    'vehicle_brand' => $row['vehicle_brand'],
                    'vehicle_model' => $row['vehicle_model'],
                    'plate_number' => $row['plate_number'],
                    'vehicle_fuel' => 'EV',
                    'license_number' => 'KL-' . substr($row['code'], -4) . '-2024',
                    'shift' => 'Full Day (8AM - 8PM)',
                    'working_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    'service_areas' => $hub->zones->pluck('code')->values()->all(),
                ]
            );

            DriverAssignment::updateOrCreate(
                [
                    'driver_id' => $driver->id,
                    'type' => 'zone',
                    'is_active' => true,
                ],
                [
                    'store_id' => null,
                    'zone_id' => $zone->id,
                    'assigned_at' => now(),
                ]
            );

            DriverEarning::updateOrCreate(
                ['driver_id' => $driver->id, 'period' => 'total'],
                ['amount' => rand(8000, 28000), 'earned_at' => now()]
            );

            $created++;
        }

        $this->command?->info("AgencyDriverSeeder: {$created} third-party zone drivers seeded.");
    }
}
