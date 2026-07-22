<?php

namespace App\Services;

use App\Models\BatchHub;
use App\Models\BatchSetting;
use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchStop;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatchService
{
    public function __construct(
        protected OperationsDataService $operations
    ) {}

    /**
     * Persist generated batches and remove their orders from the pending pool.
     *
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function storeGeneratedBatches(array $payload): array
    {
        $storeCode = (string) ($payload['store_id'] ?? '');
        $batches = $payload['batches'] ?? [];

        if ($storeCode === '' || ! is_array($batches) || $batches === []) {
            throw ValidationException::withMessages([
                'batches' => 'At least one batch is required for a store.',
            ]);
        }

        $store = Store::where('code', $storeCode)->first();
        if (! $store) {
            throw ValidationException::withMessages([
                'store_id' => 'Store not found.',
            ]);
        }

        return DB::transaction(function () use ($store, $storeCode, $batches, $payload) {
            $created = [];
            $orderCodes = [];

            foreach ($batches as $batch) {
                $code = (string) ($batch['id'] ?? '');
                if ($code === '') {
                    continue;
                }

                $hub = $batch['hub'] ?? null;
                $route = $batch['route'] ?? [];
                $orders = $batch['orders'] ?? [];

                $model = DeliveryBatch::updateOrCreate(
                    ['code' => $code],
                    [
                        'store_id' => $store->id,
                        'zone' => $batch['zone'] ?? null,
                        'zone_key' => $batch['zone_key'] ?? null,
                        'route_label' => $batch['route_label'] ?? null,
                        'status' => $batch['status'] ?? 'pending',
                        'stops' => (int) ($batch['stops'] ?? count($orders)),
                        'distance' => $batch['distance'] ?? null,
                        'est_time' => $batch['est_time'] ?? null,
                        'value' => (float) ($batch['value'] ?? 0),
                        'driver_code' => null,
                        'driver_name' => null,
                        'driver_avatar' => null,
                        'hub_lat' => $hub['lat'] ?? null,
                        'hub_lng' => $hub['lng'] ?? null,
                        'hub_name' => $hub['name'] ?? ($payload['store_name'] ?? $store->name),
                        'route_hub_to_first' => $route['hub_to_first'] ?? null,
                        'route_return' => $route['return'] ?? null,
                    ]
                );

                $model->batchStops()->delete();

                foreach ($orders as $stop) {
                    $orderCode = (string) ($stop['id'] ?? '');
                    if ($orderCode === '') {
                        continue;
                    }

                    DeliveryBatchStop::create([
                        'delivery_batch_id' => $model->id,
                        'stop' => (int) ($stop['stop'] ?? 0),
                        'order_code' => $orderCode,
                        'customer' => $stop['customer'] ?? 'Customer',
                        'address' => $stop['address'] ?? null,
                        'locality' => $stop['locality'] ?? null,
                        'lat' => $stop['lat'] ?? null,
                        'lng' => $stop['lng'] ?? null,
                        'value' => (float) ($stop['value'] ?? 0),
                        'payment' => $stop['payment'] ?? null,
                        'prep' => $stop['prep'] ?? null,
                        'delivery' => $stop['delivery'] ?? 'Waiting',
                    ]);

                    $orderCodes[] = $orderCode;
                }

                $created[] = $this->operations->shapeBatch(
                    $model->fresh(['batchStops', 'store'])
                );
            }

            if ($orderCodes !== []) {
                Order::whereIn('code', array_unique($orderCodes))->update([
                    'views' => ['batched'],
                ]);
            }

            $this->refreshHubStats($storeCode, $store->id);

            return $created;
        });
    }

    /**
     * Assign a store driver to a batch (direct assign — no acceptance window).
     *
     * @return array<string, mixed>
     */
    public function assignStoreDriver(string $batchCode, string $driverCode): array
    {
        $batch = DeliveryBatch::with(['batchStops', 'store'])
            ->where('code', $batchCode)
            ->first();

        if (! $batch) {
            throw ValidationException::withMessages([
                'batch' => 'Batch not found.',
            ]);
        }

        $driver = Driver::with(['user', 'activeAssignment.store'])
            ->whereHas('user', fn ($q) => $q->where('code', $driverCode))
            ->storeDrivers()
            ->first();

        if (! $driver || ! $driver->user) {
            throw ValidationException::withMessages([
                'driver_code' => 'Store driver not found.',
            ]);
        }

        $assignment = $driver->activeAssignment;
        if ($batch->store_id && $assignment?->store_id && (int) $assignment->store_id !== (int) $batch->store_id) {
            throw ValidationException::withMessages([
                'driver_code' => 'Driver is not assigned to this batch store.',
            ]);
        }

        return DB::transaction(function () use ($batch, $driver) {
            $user = $driver->user;
            $avatar = $user->image ? basename((string) $user->image) : '1.png';

            $batch->update([
                'status' => 'assigned',
                'driver_code' => $user->code,
                'driver_name' => $user->name,
                'driver_avatar' => $avatar,
            ]);

            $orderCodes = $batch->batchStops->pluck('order_code')->filter()->all();
            if ($orderCodes !== []) {
                Order::whereIn('code', $orderCodes)->update([
                    'driver_id' => $driver->id,
                    'delivery' => 'assigned',
                ]);

                DeliveryBatchStop::where('delivery_batch_id', $batch->id)
                    ->update(['delivery' => 'Assigned']);
            }

            return $this->operations->shapeBatch($batch->fresh(['batchStops', 'store']));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSettings(array $data): array
    {
        $settings = BatchSetting::query()->first() ?? new BatchSetting;

        $settings->fill([
            'orders_per_batch' => (int) ($data['orders_per_batch'] ?? $settings->orders_per_batch ?? 5),
            'accept_minutes' => (int) ($data['accept_minutes'] ?? $settings->accept_minutes ?? 5),
            'max_distance_km' => (float) ($data['max_distance_km'] ?? $settings->max_distance_km ?? 10),
            'max_route_minutes' => (int) ($data['max_route_minutes'] ?? $settings->max_route_minutes ?? 45),
            // Batches are store-driver only — never fall back to zone/individual drivers.
            'prefer_store_drivers' => true,
            'auto_fallback_zone' => false,
            'slot_window' => $data['slot_window'] ?? $settings->slot_window,
        ]);
        $settings->save();

        return [
            'orders_per_batch' => (int) $settings->orders_per_batch,
            'accept_minutes' => (int) $settings->accept_minutes,
            'max_distance_km' => (float) $settings->max_distance_km,
            'max_route_minutes' => (int) $settings->max_route_minutes,
            'prefer_store_drivers' => true,
            'auto_fallback_zone' => false,
            'slot_window' => $settings->slot_window,
        ];
    }

    protected function refreshHubStats(string $storeCode, int $storeId): void
    {
        $hub = BatchHub::where('code', $storeCode)->first();
        if (! $hub) {
            return;
        }

        $pending = (int) Order::where('store_id', $storeId)
            ->whereNull('views')
            ->whereNotNull('zone_key')
            ->where('delivery', 'waiting')
            ->count();

        $ordersPerBatch = max(1, (int) (BatchSetting::query()->value('orders_per_batch') ?? 5));
        $driversCount = (int) Driver::storeDrivers()
            ->whereHas('activeAssignment', fn ($q) => $q->where('store_id', $storeId))
            ->count();

        $hub->update([
            'pending' => $pending,
            'drivers_count' => $driversCount,
            'est_batches' => (int) ceil($pending / $ordersPerBatch),
            'status' => $pending > 0 ? ($hub->status === 'offline' ? 'active' : $hub->status) : $hub->status,
        ]);
    }
}
