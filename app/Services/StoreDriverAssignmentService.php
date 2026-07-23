<?php

namespace App\Services;

use App\Jobs\BroadcastOrderJob;
use App\Models\BatchSetting;
use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchStop;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Primary assignment path: for a store with pending orders, check for
 * available (non-busy) store drivers first. If any are free, route-optimize
 * and batch as many pending orders as fit (one batch per available driver —
 * see BatchRouteOptimizerService). Anything that doesn't fit — because there
 * are no store drivers free at all, or because pending orders outnumber
 * what the available drivers' batches can absorb — falls back to the
 * broadcast pool. This is the single entry point new orders (and drivers
 * that just freed up) go through; see Order::booted() and
 * BatchService::finishBatch().
 */
class StoreDriverAssignmentService
{
    public function __construct(
        protected BatchRouteOptimizerService $optimizer
    ) {}

    public function dispatchPendingOrders(Store $store): void
    {
        DB::transaction(function () use ($store) {
            $pendingOrders = Order::where('store_id', $store->id)
                ->where('status', Order::STATUS_PENDING)
                ->whereNull('delivery_batch_id')
                ->lockForUpdate()
                ->get();

            if ($pendingOrders->isEmpty()) {
                return;
            }

            $availableDrivers = $this->availableStoreDrivers($store);

            if ($availableDrivers->isEmpty()) {
                // All store drivers busy (or none exist) — every pending
                // order for this store overflows to the broadcast pool.
                $this->sendToBroadcast($pendingOrders);

                return;
            }

            $config = $this->batchConfig();
            $result = $this->optimizer->optimizeForStore($store, $pendingOrders, $availableDrivers, $config);

            foreach ($result['batches'] as $batchData) {
                $this->persistBatch($store, $batchData);
            }

            if ($result['overflow'] !== []) {
                $this->sendToBroadcast(collect($result['overflow']));
            }
        });
    }

    /**
     * @return Collection<int, Driver>
     */
    protected function availableStoreDrivers(Store $store): Collection
    {
        return Driver::query()
            ->availableForBatch()
            ->whereHas('activeAssignment', fn ($q) => $q->where('store_id', $store->id))
            ->with(['user', 'latestLocation'])
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    protected function sendToBroadcast(Collection $orders): void
    {
        foreach ($orders as $order) {
            BroadcastOrderJob::dispatch($order);
        }
    }

    /**
     * @return array{orders_per_batch: int, max_distance_km: float, max_route_minutes: int}
     */
    protected function batchConfig(): array
    {
        $settings = BatchSetting::query()->first();

        return [
            'orders_per_batch' => (int) ($settings?->orders_per_batch ?? 5),
            'max_distance_km' => (float) ($settings?->max_distance_km ?? 10),
            'max_route_minutes' => (int) ($settings?->max_route_minutes ?? 45),
        ];
    }

    /**
     * @param  array{orders: list<Order>, metrics: array, driver: ?Driver}  $batchData
     */
    protected function persistBatch(Store $store, array $batchData): void
    {
        $driver = $batchData['driver'];
        $orders = $batchData['orders'];
        $metrics = $batchData['metrics'];
        $user = $driver?->user;

        $batch = DeliveryBatch::create([
            'code' => $this->nextBatchCode(),
            'store_id' => $store->id,
            'driver_id' => $driver?->id,
            'status' => $driver ? DeliveryBatch::STATUS_ASSIGNED : DeliveryBatch::STATUS_PENDING,
            'stops' => count($orders),
            'distance' => round($metrics['distance_km'], 1).' km',
            'est_time' => round($metrics['duration_min']).' min',
            'value' => round(collect($orders)->sum('value'), 2),
            'hub_lat' => $store->lat,
            'hub_lng' => $store->lng,
            'hub_name' => $store->name,
            'route_hub_to_first' => round($metrics['hub_to_first_km'], 1).' km',
            'route_return' => round($metrics['return_km'], 1).' km',
            'driver_code' => $user?->code,
            'driver_name' => $user?->name,
            'driver_avatar' => $user?->image ? basename((string) $user->image) : null,
        ]);

        foreach ($orders as $index => $order) {
            DeliveryBatchStop::create([
                'delivery_batch_id' => $batch->id,
                'order_id' => $order->id,
                'stop' => $index + 1,
                'order_code' => $order->code,
                'customer' => $order->customer,
                'address' => $order->address,
                'locality' => $order->locality,
                'lat' => $order->lat,
                'lng' => $order->lng,
                'value' => $order->value,
                'payment' => $order->payment,
                'prep' => $order->prep,
                'delivery' => $driver ? 'Assigned' : 'Waiting',
            ]);

            $order->update([
                'status' => $driver ? Order::STATUS_ASSIGNED : Order::STATUS_BATCHED,
                'assignment_type' => Order::ASSIGNMENT_STORE_BATCH,
                'delivery_batch_id' => $batch->id,
                'driver_id' => $driver?->id,
                'delivery' => $driver ? 'assigned' : 'waiting',
                'views' => ['batched'],
            ]);
        }

        if ($driver) {
            // Busy = has an active batch. This is what makes the driver
            // unavailable for the next dispatch pass.
            $driver->update(['current_batch_id' => $batch->id]);
        }
    }

    protected function nextBatchCode(): string
    {
        $lastCode = DeliveryBatch::query()
            ->where('code', 'like', 'BATCH-%')
            ->orderByRaw('CAST(SUBSTRING(code, 7) AS UNSIGNED) DESC')
            ->value('code');

        $nextNumber = $lastCode ? ((int) substr($lastCode, 6)) + 1 : 1;

        do {
            $code = 'BATCH-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = DeliveryBatch::where('code', $code)->exists();
            $nextNumber++;
        } while ($exists);

        return $code;
    }
}
