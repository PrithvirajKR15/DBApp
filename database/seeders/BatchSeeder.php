<?php

namespace Database\Seeders;

use App\Models\BatchHub;
use App\Models\BatchSetting;
use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchStop;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\DriverLocation;
use App\Models\Order;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $data = include database_path('seeders/data/batches.php');
        $storesByCode = Store::pluck('id', 'code');

        $settings = $data['settings'] ?? [];
        BatchSetting::query()->delete();
        BatchSetting::create([
            'orders_per_batch' => (int) ($settings['orders_per_batch'] ?? 5),
            'accept_minutes' => (int) ($settings['accept_minutes'] ?? 5),
            'max_distance_km' => (float) ($settings['max_distance_km'] ?? 10),
            'max_route_minutes' => (int) ($settings['max_route_minutes'] ?? 45),
            'prefer_store_drivers' => (bool) ($settings['prefer_store_drivers'] ?? true),
            'auto_fallback_zone' => (bool) ($settings['auto_fallback_zone'] ?? true),
            'slot_window' => $settings['slot_window'] ?? null,
        ]);

        foreach ($data['stores'] ?? [] as $hub) {
            BatchHub::updateOrCreate(
                ['code' => $hub['id']],
                [
                    'name' => $hub['name'],
                    'zone' => $hub['zone'] ?? null,
                    'branch' => $hub['branch'] ?? null,
                    'pending' => (int) ($hub['pending'] ?? 0),
                    'drivers_count' => (int) ($hub['drivers'] ?? 0),
                    'est_batches' => (int) ($hub['est_batches'] ?? 0),
                    'status' => $hub['status'] ?? 'active',
                    'slot' => $hub['slot'] ?? null,
                    'color' => $hub['color'] ?? null,
                    'lat' => $hub['lat'] ?? null,
                    'lng' => $hub['lng'] ?? null,
                ]
            );
        }

        foreach ($data['pending_orders'] ?? [] as $po) {
            $storeCode = $po['store_id'] ?? null;
            Order::updateOrCreate(
                ['code' => $po['id']],
                [
                    'store_id' => $storesByCode[$storeCode] ?? null,
                    'driver_id' => null,
                    'customer' => $po['customer'],
                    'phone' => null,
                    'area' => $po['area'] ?? null,
                    'address' => $po['address'] ?? null,
                    'slot' => null,
                    'slot_label' => null,
                    'placed_at' => null,
                    'urgent' => false,
                    'value' => (float) ($po['value'] ?? 0),
                    'items' => 0,
                    'payment' => $po['payment'] ?? null,
                    'prep' => $po['prep'] ?? null,
                    'prep_pct' => ($po['prep'] ?? '') === 'ready' ? 100 : 40,
                    'delivery' => 'waiting',
                    'eta' => null,
                    'lat' => $po['lat'] ?? null,
                    'lng' => $po['lng'] ?? null,
                    'views' => null,
                    'locality' => $po['locality'] ?? null,
                    'zone_key' => $po['zone_key'] ?? null,
                ]
            );
        }

        $this->seedBatchDrivers($data['drivers'] ?? [], $storesByCode);

        foreach ($data['batches'] ?? [] as $batch) {
            $storeCode = $batch['store_id'] ?? null;
            $driver = $batch['driver'] ?? null;
            $hub = $batch['hub'] ?? null;
            $route = $batch['route'] ?? [];

            $model = DeliveryBatch::updateOrCreate(
                ['code' => $batch['id']],
                [
                    'store_id' => $storesByCode[$storeCode] ?? null,
                    'zone' => $batch['zone'] ?? null,
                    'zone_key' => $batch['zone_key'] ?? null,
                    'route_label' => $batch['route_label'] ?? null,
                    'status' => $batch['status'] ?? 'pending',
                    'stops' => (int) ($batch['stops'] ?? count($batch['orders'] ?? [])),
                    'distance' => $batch['distance'] ?? null,
                    'est_time' => $batch['est_time'] ?? null,
                    'value' => (float) ($batch['value'] ?? 0),
                    'driver_code' => $driver['id'] ?? null,
                    'driver_name' => $driver['name'] ?? null,
                    'driver_avatar' => $driver['avatar'] ?? null,
                    'hub_lat' => $hub['lat'] ?? null,
                    'hub_lng' => $hub['lng'] ?? null,
                    'hub_name' => $hub['name'] ?? ($batch['store'] ?? null),
                    'route_hub_to_first' => $route['hub_to_first'] ?? null,
                    'route_return' => $route['return'] ?? null,
                ]
            );

            $model->batchStops()->delete();
            foreach ($batch['orders'] ?? [] as $stop) {
                DeliveryBatchStop::create([
                    'delivery_batch_id' => $model->id,
                    'stop' => (int) ($stop['stop'] ?? 0),
                    'order_code' => $stop['id'],
                    'customer' => $stop['customer'],
                    'address' => $stop['address'] ?? null,
                    'locality' => $stop['locality'] ?? null,
                    'lat' => $stop['lat'] ?? null,
                    'lng' => $stop['lng'] ?? null,
                    'value' => (float) ($stop['value'] ?? 0),
                    'payment' => $stop['payment'] ?? null,
                    'prep' => $stop['prep'] ?? null,
                    'delivery' => $stop['delivery'] ?? null,
                ]);
            }
        }

        $this->refreshHubStats($storesByCode);
    }

    /**
     * @param  list<array<string, mixed>>  $drivers
     * @param  \Illuminate\Support\Collection<string, int>  $storesByCode
     */
    private function seedBatchDrivers(array $drivers, $storesByCode): void
    {
        $userRoleId = Role::findBySlug('user')->id;
        $usedMobiles = User::pluck('mobile')->flip()->map(fn () => true)->all();

        foreach ($drivers as $d) {
            $code = $d['id'] ?? null;
            if (! $code) {
                continue;
            }

            $slug = Str::slug($d['name'] ?? $code, '.');
            $mobile = '+1 555 ' . str_pad((string) (crc32($code) % 10000), 4, '0', STR_PAD_LEFT);
            while (isset($usedMobiles[$mobile])) {
                $mobile .= '1';
            }
            $usedMobiles[$mobile] = true;

            $user = User::updateOrCreate(
                ['email' => $slug . '.batch@kenland.in'],
                [
                    'name' => $d['name'],
                    'mobile' => $mobile,
                    'password' => Hash::make('password@123'),
                    'role_id' => $userRoleId,
                    'code' => $code,
                    'status' => 'Active',
                    'image' => $d['avatar'] ?? '1.png',
                ]
            );

            $driver = Driver::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'availability' => ($d['status'] ?? 'available') === 'available' ? 'Online' : 'Offline',
                    'vehicle_type' => 'scooter',
                    'plate_number' => $d['vehicle'] ?? null,
                    'partner_type' => ($d['type'] ?? 'zone') === 'store' ? 'store' : 'independent',
                    'service_areas' => $d['zones'] ?? null,
                ]
            );

            $type = ($d['type'] ?? 'zone') === 'store' ? 'store' : 'zone';
            $storeId = $type === 'store' ? ($storesByCode[$d['store_id'] ?? ''] ?? null) : null;
            $zoneId = null;

            if ($type === 'zone') {
                $region = strtolower((string) ($d['zones'][0] ?? 'central'));
                $regionPrimary = [
                    'north' => 'pattom',
                    'central' => 'kowdiar',
                    'east' => 'technopark',
                    'west' => 'medical-college',
                    'south' => 'east-fort',
                ];
                $zoneCode = $regionPrimary[$region] ?? $region;
                $zone = Zone::where('code', $zoneCode)->first()
                    ?? Zone::where('region', $region)->first();

                if ($zone) {
                    $zoneId = $zone->id;
                }
            }

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

            if (isset($d['lat'], $d['lng'])) {
                DriverLocation::updateOrCreate(
                    ['driver_id' => $driver->id],
                    [
                        'lat' => $d['lat'],
                        'lng' => $d['lng'],
                        'live_status' => 'Idle',
                        'recorded_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $storesByCode
     */
    private function refreshHubStats($storesByCode): void
    {
        $ordersPerBatch = max(1, (int) (BatchSetting::query()->value('orders_per_batch') ?? 5));

        foreach (BatchHub::all() as $hub) {
            $storeId = $storesByCode[$hub->code] ?? null;
            if (! $storeId) {
                continue;
            }

            $pending = (int) Order::where('store_id', $storeId)
                ->whereNull('views')
                ->whereNotNull('zone_key')
                ->where('delivery', 'waiting')
                ->count();

            $driversCount = (int) Driver::storeDrivers()
                ->whereHas('activeAssignment', fn ($q) => $q->where('store_id', $storeId))
                ->count();

            $hub->update([
                'pending' => $pending,
                'drivers_count' => $driversCount,
                'est_batches' => (int) ceil($pending / $ordersPerBatch),
            ]);
        }
    }
}
