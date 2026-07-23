<?php

namespace Database\Seeders;

use App\Models\BatchHub;
use App\Models\BatchSetting;
use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchGroup;
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
            'broadcast_radius_km' => (float) ($settings['broadcast_radius_km'] ?? 5),
            'broadcast_offer_seconds' => (int) ($settings['broadcast_offer_seconds'] ?? 90),
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
                    'status' => Order::STATUS_PENDING,
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

        $driversByUserCode = Driver::with('user')->get()
            ->filter(fn (Driver $d) => $d->user?->code)
            ->mapWithKeys(fn (Driver $d) => [$d->user->code => $d]);

        // One parent group per store for seeded batches (demo index hierarchy).
        $batchesByStore = collect($data['batches'] ?? [])->groupBy(fn ($b) => $b['store_id'] ?? 'unknown');
        $groupIdsByStoreCode = [];

        foreach ($batchesByStore as $storeCode => $storeBatches) {
            $storeId = $storesByCode[$storeCode] ?? null;
            if (! $storeId) {
                continue;
            }

            $statuses = $storeBatches->map(fn ($b) => $this->normalizeBatchStatus($b['status'] ?? null));
            $groupStatus = DeliveryBatchGroup::STATUS_OPEN;
            if ($statuses->every(fn ($s) => in_array($s, [DeliveryBatch::STATUS_COMPLETED, DeliveryBatch::STATUS_CANCELLED], true))) {
                $groupStatus = DeliveryBatchGroup::STATUS_COMPLETED;
            } elseif ($statuses->contains(DeliveryBatch::STATUS_IN_PROGRESS)) {
                $groupStatus = DeliveryBatchGroup::STATUS_IN_PROGRESS;
            }

            $group = DeliveryBatchGroup::create([
                'code' => 'BG-'.$storeCode.'-SEED',
                'store_id' => $storeId,
                'status' => $groupStatus,
                'batch_count' => $storeBatches->count(),
                'order_count' => $storeBatches->sum(fn ($b) => (int) ($b['stops'] ?? count($b['orders'] ?? []))),
                'overflow_count' => 0,
                'slot_window' => $settings['slot_window'] ?? null,
            ]);

            $groupIdsByStoreCode[$storeCode] = $group->id;
        }

        foreach ($data['batches'] ?? [] as $batch) {
            $storeCode = $batch['store_id'] ?? null;
            $driverInfo = $batch['driver'] ?? null;
            $driverModel = $driverInfo['id'] ?? null ? ($driversByUserCode[$driverInfo['id']] ?? null) : null;
            $hub = $batch['hub'] ?? null;
            $route = $batch['route'] ?? [];
            $status = $this->normalizeBatchStatus($batch['status'] ?? null);

            $model = DeliveryBatch::updateOrCreate(
                ['code' => $batch['id']],
                [
                    'store_id' => $storesByCode[$storeCode] ?? null,
                    'batch_group_id' => $groupIdsByStoreCode[$storeCode] ?? null,
                    'driver_id' => $driverModel?->id,
                    'zone' => $batch['zone'] ?? null,
                    'zone_key' => $batch['zone_key'] ?? null,
                    'route_label' => $batch['route_label'] ?? null,
                    'status' => $status,
                    'stops' => (int) ($batch['stops'] ?? count($batch['orders'] ?? [])),
                    'distance' => $batch['distance'] ?? null,
                    'est_time' => $batch['est_time'] ?? null,
                    'value' => (float) ($batch['value'] ?? 0),
                    'driver_code' => $driverInfo['id'] ?? null,
                    'driver_name' => $driverInfo['name'] ?? null,
                    'driver_avatar' => $driverInfo['avatar'] ?? null,
                    'hub_lat' => $hub['lat'] ?? null,
                    'hub_lng' => $hub['lng'] ?? null,
                    'hub_name' => $hub['name'] ?? ($batch['store'] ?? null),
                    'route_hub_to_first' => $route['hub_to_first'] ?? null,
                    'route_return' => $route['return'] ?? null,
                ]
            );

            $model->batchStops()->delete();
            $orderCodes = [];

            foreach ($batch['orders'] ?? [] as $stop) {
                $order = Order::where('code', $stop['id'])->first();
                $orderCodes[] = $stop['id'];

                DeliveryBatchStop::create([
                    'delivery_batch_id' => $model->id,
                    'order_id' => $order?->id,
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

            if ($orderCodes !== []) {
                Order::whereIn('code', $orderCodes)->update([
                    'delivery_batch_id' => $model->id,
                    'assignment_type' => Order::ASSIGNMENT_STORE_BATCH,
                    'driver_id' => $driverModel?->id,
                    'status' => $driverModel ? Order::STATUS_ASSIGNED : Order::STATUS_BATCHED,
                ]);
            }

            if ($driverModel && in_array($status, DeliveryBatch::ACTIVE_STATUSES, true)) {
                $driverModel->update(['current_batch_id' => $model->id]);
            }
        }

        $this->refreshHubStats($storesByCode);
    }

    /**
     * The demo data file predates the canonical batch status vocabulary
     * (pending|assigned|in_progress|completed|cancelled) and uses a couple
     * of ad hoc synonyms — normalize them so current_batch_id ("is this
     * driver busy?") backfill logic recognizes every active batch.
     */
    private function normalizeBatchStatus(?string $status): string
    {
        return match ($status) {
            'accepted' => DeliveryBatch::STATUS_ASSIGNED,
            'in_transit' => DeliveryBatch::STATUS_IN_PROGRESS,
            'completed', 'cancelled', 'pending', 'assigned', 'in_progress' => $status,
            default => DeliveryBatch::STATUS_PENDING,
        };
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

            $type = ($d['type'] ?? 'zone') === 'store' ? 'store' : 'zone';

            $driver = Driver::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'driver_type' => $type === 'store' ? Driver::TYPE_STORE : Driver::TYPE_THIRD_PARTY,
                    'availability' => ($d['status'] ?? 'available') === 'available' ? 'Online' : 'Offline',
                    'vehicle_type' => 'scooter',
                    'plate_number' => $d['vehicle'] ?? null,
                    'partner_type' => ($d['type'] ?? 'zone') === 'store' ? 'store' : 'independent',
                    'service_areas' => $d['zones'] ?? null,
                ]
            );
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
