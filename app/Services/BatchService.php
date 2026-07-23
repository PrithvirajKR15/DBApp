<?php

namespace App\Services;

use App\Jobs\DispatchStoreOrdersJob;
use App\Models\BatchHub;
use App\Models\BatchSetting;
use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchGroup;
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
     * Persist a parent batch group + child route batches with drivers already assigned.
     * Overflow orders from generation are left untouched (still pending for admin).
     *
     * @param  array<string, mixed>  $payload
     * @return array{group: array<string, mixed>, batches: list<array<string, mixed>>}
     */
    public function storeGeneratedBatches(array $payload): array
    {
        $storeCode = (string) ($payload['store_id'] ?? '');
        $batches = $payload['batches'] ?? [];
        $overflowCount = (int) ($payload['overflow_count'] ?? 0);

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

        $driverCodes = collect($batches)
            ->map(fn ($b) => (string) ($b['driver_code'] ?? ''))
            ->filter()
            ->values();

        if ($driverCodes->count() !== count($batches)) {
            throw ValidationException::withMessages([
                'batches' => 'Each child batch must have a store driver assigned.',
            ]);
        }

        if ($driverCodes->unique()->count() !== $driverCodes->count()) {
            throw ValidationException::withMessages([
                'driver_code' => 'Each store driver can only be assigned to one batch in this group.',
            ]);
        }

        return DB::transaction(function () use ($store, $storeCode, $batches, $payload, $overflowCount, $driverCodes) {
            $driversByCode = Driver::with(['user', 'activeAssignment'])
                ->whereHas('user', fn ($q) => $q->whereIn('code', $driverCodes->all()))
                ->storeDrivers()
                ->get()
                ->keyBy(fn (Driver $d) => $d->user?->code);

            foreach ($driverCodes as $code) {
                $driver = $driversByCode->get($code);
                if (! $driver || ! $driver->user) {
                    throw ValidationException::withMessages([
                        'driver_code' => "Store driver {$code} not found.",
                    ]);
                }

                $assignment = $driver->activeAssignment;
                if ($assignment?->store_id && (int) $assignment->store_id !== (int) $store->id) {
                    throw ValidationException::withMessages([
                        'driver_code' => "Driver {$code} is not assigned to this store.",
                    ]);
                }

                if ($driver->current_batch_id) {
                    throw ValidationException::withMessages([
                        'driver_code' => "Driver {$code} is already busy on another batch.",
                    ]);
                }
            }

            $orderCount = collect($batches)->sum(
                fn ($b) => count($b['orders'] ?? [])
            );

            $settings = BatchSetting::query()->first();
            $group = DeliveryBatchGroup::create([
                'code' => $this->nextGroupCode($storeCode),
                'store_id' => $store->id,
                'status' => DeliveryBatchGroup::STATUS_OPEN,
                'batch_count' => count($batches),
                'order_count' => $orderCount,
                'overflow_count' => max(0, $overflowCount),
                'slot_window' => $settings?->slot_window,
            ]);

            $created = [];

            foreach ($batches as $batch) {
                $code = (string) ($batch['id'] ?? '');
                $driverCode = (string) ($batch['driver_code'] ?? '');
                if ($code === '' || $driverCode === '') {
                    continue;
                }

                $driver = $driversByCode->get($driverCode);
                $user = $driver->user;
                $avatar = $user->image ? basename((string) $user->image) : '1.png';
                $hub = $batch['hub'] ?? null;
                if (! is_array($hub) || ($hub['lat'] ?? null) === null || ($hub['lng'] ?? null) === null) {
                    $hub = [
                        'lat' => $store->lat,
                        'lng' => $store->lng,
                        'name' => $payload['store_name'] ?? $store->name,
                    ];
                }

                $route = $batch['route'] ?? [];
                $orders = $batch['orders'] ?? [];

                $model = DeliveryBatch::updateOrCreate(
                    ['code' => $code],
                    [
                        'store_id' => $store->id,
                        'batch_group_id' => $group->id,
                        'driver_id' => $driver->id,
                        'zone' => $batch['zone'] ?? null,
                        'zone_key' => $batch['zone_key'] ?? null,
                        'route_label' => $batch['route_label'] ?? null,
                        'status' => DeliveryBatch::STATUS_ASSIGNED,
                        'stops' => (int) ($batch['stops'] ?? count($orders)),
                        'distance' => $batch['distance'] ?? null,
                        'est_time' => $batch['est_time'] ?? null,
                        'value' => (float) ($batch['value'] ?? 0),
                        'driver_code' => $user->code,
                        'driver_name' => $user->name,
                        'driver_avatar' => $avatar,
                        'hub_lat' => $hub['lat'] ?? $store->lat,
                        'hub_lng' => $hub['lng'] ?? $store->lng,
                        'hub_name' => $hub['name'] ?? ($payload['store_name'] ?? $store->name),
                        'route_hub_to_first' => $route['hub_to_first'] ?? null,
                        'route_return' => $route['return'] ?? null,
                    ]
                );

                $model->batchStops()->delete();
                $orderCodesForBatch = [];
                $createdStops = 0;

                foreach ($orders as $stop) {
                    $orderCode = (string) ($stop['id'] ?? '');
                    if ($orderCode === '') {
                        continue;
                    }

                    $orderModel = Order::where('code', $orderCode)->first();

                    // Broadcast / already-taken orders can never enter a store batch.
                    if ($orderModel && ! $orderModel->canBeBatched()) {
                        continue;
                    }

                    // Only pending (or not-yet-assigned) orders should be pulled in.
                    if ($orderModel && $orderModel->status !== Order::STATUS_PENDING && $orderModel->delivery_batch_id) {
                        continue;
                    }

                    $lat = $stop['lat'] ?? $orderModel?->lat;
                    $lng = $stop['lng'] ?? $orderModel?->lng;

                    DeliveryBatchStop::create([
                        'delivery_batch_id' => $model->id,
                        'order_id' => $orderModel?->id,
                        'stop' => (int) ($stop['stop'] ?? 0),
                        'order_code' => $orderCode,
                        'customer' => $stop['customer'] ?? $orderModel?->customer ?? 'Customer',
                        'address' => $stop['address'] ?? $orderModel?->address,
                        'locality' => $stop['locality'] ?? $orderModel?->locality,
                        'lat' => $lat !== null ? (float) $lat : null,
                        'lng' => $lng !== null ? (float) $lng : null,
                        'value' => (float) ($stop['value'] ?? $orderModel?->value ?? 0),
                        'payment' => $stop['payment'] ?? $orderModel?->payment,
                        'prep' => $stop['prep'] ?? $orderModel?->prep,
                        'delivery' => 'Assigned',
                    ]);

                    $createdStops++;
                    $orderCodesForBatch[] = $orderCode;
                }

                if ($createdStops === 0) {
                    throw ValidationException::withMessages([
                        'batches' => "Batch {$code} has no mappable orders left to assign. Refresh pending orders and generate again.",
                    ]);
                }

                $this->renumberStops($model->id);
                $model->update([
                    'stops' => $createdStops,
                    'value' => round((float) $model->batchStops()->sum('value'), 2),
                ]);

                $driver->update(['current_batch_id' => $model->id]);

                if ($orderCodesForBatch !== []) {
                    Order::whereIn('code', $orderCodesForBatch)->update([
                        'views' => ['batched'],
                        'status' => Order::STATUS_ASSIGNED,
                        'assignment_type' => Order::ASSIGNMENT_STORE_BATCH,
                        'delivery_batch_id' => $model->id,
                        'driver_id' => $driver->id,
                        'delivery' => 'assigned',
                    ]);
                }

                $created[] = $this->operations->shapeBatch(
                    $model->fresh(['batchStops', 'store'])
                );
            }

            $this->refreshHubStats($storeCode, $store->id);

            return [
                'group' => $this->operations->shapeBatchGroup($group->fresh(['batches.batchStops', 'batches.store', 'store'])),
                'batches' => $created,
            ];
        });
    }

    /**
     * Assign or reassign a store driver to a child batch (only before delivery starts).
     *
     * @return array<string, mixed>
     */
    public function assignStoreDriver(string $batchCode, string $driverCode): array
    {
        $batch = DeliveryBatch::with(['batchStops', 'store', 'driver', 'group'])
            ->where('code', $batchCode)
            ->first();

        if (! $batch) {
            throw ValidationException::withMessages([
                'batch' => 'Batch not found.',
            ]);
        }

        if (! $batch->isEditable()) {
            throw ValidationException::withMessages([
                'batch' => 'This batch can no longer be reassigned — delivery has already started.',
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

        if ($driver->current_batch_id && (int) $driver->current_batch_id !== (int) $batch->id) {
            throw ValidationException::withMessages([
                'driver_code' => 'Driver is already busy on another batch.',
            ]);
        }

        return DB::transaction(function () use ($batch, $driver) {
            $previousDriverId = $batch->driver_id;

            if ($previousDriverId && (int) $previousDriverId !== (int) $driver->id) {
                Driver::where('id', $previousDriverId)
                    ->where('current_batch_id', $batch->id)
                    ->update(['current_batch_id' => null]);
            }

            $user = $driver->user;
            $avatar = $user->image ? basename((string) $user->image) : '1.png';

            $batch->update([
                'status' => DeliveryBatch::STATUS_ASSIGNED,
                'driver_id' => $driver->id,
                'driver_code' => $user->code,
                'driver_name' => $user->name,
                'driver_avatar' => $avatar,
            ]);

            $driver->update(['current_batch_id' => $batch->id]);

            $orderCodes = $batch->batchStops->pluck('order_code')->filter()->all();
            if ($orderCodes !== []) {
                Order::whereIn('code', $orderCodes)->update([
                    'driver_id' => $driver->id,
                    'delivery' => 'assigned',
                    'status' => Order::STATUS_ASSIGNED,
                ]);

                DeliveryBatchStop::where('delivery_batch_id', $batch->id)
                    ->update(['delivery' => 'Assigned']);
            }

            $batch->group?->refreshStatusFromChildren();

            return $this->operations->shapeBatch($batch->fresh(['batchStops', 'store']));
        });
    }

    /**
     * Move one order from an editable child batch to another editable batch
     * in the same parent group, and renumber stops on both.
     *
     * @return array{from: array<string, mixed>, to: array<string, mixed>}
     */
    public function moveOrder(string $orderCode, string $fromBatchCode, string $toBatchCode): array
    {
        if ($fromBatchCode === $toBatchCode) {
            throw ValidationException::withMessages([
                'to_batch' => 'Order is already on that batch.',
            ]);
        }

        return DB::transaction(function () use ($orderCode, $fromBatchCode, $toBatchCode) {
            $from = DeliveryBatch::with(['batchStops', 'store', 'driver', 'group'])
                ->where('code', $fromBatchCode)
                ->lockForUpdate()
                ->first();
            $to = DeliveryBatch::with(['batchStops', 'store', 'driver', 'group'])
                ->where('code', $toBatchCode)
                ->lockForUpdate()
                ->first();

            if (! $from || ! $to) {
                throw ValidationException::withMessages(['batch' => 'Batch not found.']);
            }

            if (! $from->isEditable() || ! $to->isEditable()) {
                throw ValidationException::withMessages([
                    'batch' => 'Orders can only be moved between batches that have not started delivery.',
                ]);
            }

            if ($from->batch_group_id && $to->batch_group_id && (int) $from->batch_group_id !== (int) $to->batch_group_id) {
                throw ValidationException::withMessages([
                    'batch' => 'Orders can only be moved between child batches in the same parent group.',
                ]);
            }

            $stop = $from->batchStops->firstWhere('order_code', $orderCode);
            if (! $stop) {
                throw ValidationException::withMessages([
                    'order' => 'Order is not on the source batch.',
                ]);
            }

            if ($from->batchStops->count() <= 1) {
                throw ValidationException::withMessages([
                    'order' => 'Cannot move the last order out of a batch. Reassign the whole batch instead.',
                ]);
            }

            if ($to->batchStops->contains('order_code', $orderCode)) {
                throw ValidationException::withMessages([
                    'order' => 'Order is already on the target batch.',
                ]);
            }

            // Append at end using DB max so we never rely on a stale in-memory
            // collection count after concurrent edits.
            $nextStop = (int) DeliveryBatchStop::where('delivery_batch_id', $to->id)->max('stop') + 1;

            $stop->update([
                'delivery_batch_id' => $to->id,
                'stop' => $nextStop,
                'delivery' => $to->driver_id ? 'Assigned' : ($stop->delivery ?? 'Waiting'),
            ]);

            Order::where('code', $orderCode)->update([
                'delivery_batch_id' => $to->id,
                'driver_id' => $to->driver_id,
                'delivery' => $to->driver_id ? 'assigned' : 'waiting',
                'status' => $to->driver_id ? Order::STATUS_ASSIGNED : Order::STATUS_BATCHED,
            ]);

            // Compact both batches to contiguous 1..N stop numbers.
            $this->renumberStops($from->id);
            $this->renumberStops($to->id);
            $this->refreshBatchAggregates($from->id);
            $this->refreshBatchAggregates($to->id);

            $from->unsetRelation('batchStops');
            $to->unsetRelation('batchStops');

            return [
                'from' => $this->operations->shapeBatch($from->fresh(['batchStops', 'store'])),
                'to' => $this->operations->shapeBatch($to->fresh(['batchStops', 'store'])),
            ];
        });
    }

    /**
     * Reorder stop numbers on an editable batch.
     *
     * @param  list<string>  $orderedOrderCodes
     * @return array<string, mixed>
     */
    public function reorderStops(string $batchCode, array $orderedOrderCodes): array
    {
        return DB::transaction(function () use ($batchCode, $orderedOrderCodes) {
            $batch = DeliveryBatch::with(['batchStops', 'store'])
                ->where('code', $batchCode)
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                throw ValidationException::withMessages(['batch' => 'Batch not found.']);
            }

            if (! $batch->isEditable()) {
                throw ValidationException::withMessages([
                    'batch' => 'Stop order cannot be changed after delivery has started.',
                ]);
            }

            $existing = $batch->batchStops->pluck('order_code')->sort()->values()->all();
            $incoming = collect($orderedOrderCodes)->filter()->values()->all();
            $incomingSorted = collect($incoming)->sort()->values()->all();

            if ($existing !== $incomingSorted || count($incoming) !== count($existing)) {
                throw ValidationException::withMessages([
                    'order_codes' => 'Stop list must include every order on this batch exactly once.',
                ]);
            }

            foreach ($incoming as $index => $code) {
                DeliveryBatchStop::where('delivery_batch_id', $batch->id)
                    ->where('order_code', $code)
                    ->update(['stop' => $index + 1]);
            }

            return $this->operations->shapeBatch($batch->fresh(['batchStops', 'store']));
        });
    }

    /**
     * Delete one child batch before delivery starts. Orders return to pending
     * and the driver is freed. Removes the parent group if it becomes empty.
     *
     * @return array{deleted: string, group_deleted: bool, group_id: ?string}
     */
    public function deleteBatch(string $batchCode): array
    {
        return DB::transaction(function () use ($batchCode) {
            $batch = DeliveryBatch::with(['batchStops', 'store', 'driver', 'group'])
                ->where('code', $batchCode)
                ->lockForUpdate()
                ->first();

            if (! $batch) {
                throw ValidationException::withMessages(['batch' => 'Batch not found.']);
            }

            if (! $batch->isEditable()) {
                throw ValidationException::withMessages([
                    'batch' => 'This batch cannot be deleted — delivery has already started.',
                ]);
            }

            $group = $batch->group;
            $store = $batch->store;
            $groupCode = $group?->code;

            $this->releaseBatchResources($batch);
            $batch->batchStops()->delete();
            $batch->delete();

            $groupDeleted = false;
            if ($group) {
                $remaining = $group->batches()->count();
                if ($remaining === 0) {
                    $group->delete();
                    $groupDeleted = true;
                } else {
                    $group->update([
                        'batch_count' => $remaining,
                        'order_count' => (int) $group->batches()->sum('stops'),
                    ]);
                    $group->refreshStatusFromChildren();
                }
            }

            if ($store) {
                $this->refreshHubStats($store->code, $store->id);
            }

            return [
                'deleted' => $batchCode,
                'group_deleted' => $groupDeleted,
                'group_id' => $groupDeleted ? null : $groupCode,
            ];
        });
    }

    /**
     * Delete an entire parent batch group (all child routes) before any child
     * has started delivery. Orders return to pending; drivers are freed.
     *
     * @return array{deleted: string, batches_deleted: int}
     */
    public function deleteBatchGroup(string $groupCode): array
    {
        return DB::transaction(function () use ($groupCode) {
            $group = DeliveryBatchGroup::with(['batches.batchStops', 'batches.driver', 'store'])
                ->where('code', $groupCode)
                ->lockForUpdate()
                ->first();

            if (! $group) {
                throw ValidationException::withMessages(['group' => 'Batch group not found.']);
            }

            $locked = $group->batches->first(fn (DeliveryBatch $b) => ! $b->isEditable());
            if ($locked) {
                throw ValidationException::withMessages([
                    'group' => 'Cannot delete this group — at least one child batch has started delivery.',
                ]);
            }

            $store = $group->store;
            $count = $group->batches->count();

            foreach ($group->batches as $batch) {
                $this->releaseBatchResources($batch);
                $batch->batchStops()->delete();
                $batch->delete();
            }

            $group->delete();

            if ($store) {
                $this->refreshHubStats($store->code, $store->id);
            }

            return [
                'deleted' => $groupCode,
                'batches_deleted' => $count,
            ];
        });
    }

    /**
     * Free the driver and return orders to the pending pool.
     */
    protected function releaseBatchResources(DeliveryBatch $batch): void
    {
        if ($batch->driver_id) {
            Driver::where('id', $batch->driver_id)
                ->where('current_batch_id', $batch->id)
                ->update(['current_batch_id' => null]);
        }

        $orderCodes = $batch->batchStops->pluck('order_code')->filter()->all();
        if ($orderCodes === []) {
            // Also clear any orders that point at this batch without stop rows.
            Order::where('delivery_batch_id', $batch->id)->update([
                'delivery_batch_id' => null,
                'driver_id' => null,
                'assignment_type' => null,
                'status' => Order::STATUS_PENDING,
                'delivery' => 'waiting',
                'views' => null,
            ]);

            return;
        }

        Order::whereIn('code', $orderCodes)->update([
            'delivery_batch_id' => null,
            'driver_id' => null,
            'assignment_type' => null,
            'status' => Order::STATUS_PENDING,
            'delivery' => 'waiting',
            'views' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function completeBatch(string $batchCode): array
    {
        return $this->finishBatch($batchCode, DeliveryBatch::STATUS_COMPLETED, Order::STATUS_DELIVERED, 'Delivered');
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelBatch(string $batchCode): array
    {
        return $this->finishBatch($batchCode, DeliveryBatch::STATUS_CANCELLED, Order::STATUS_CANCELLED, 'Cancelled');
    }

    /**
     * @return array<string, mixed>
     */
    private function finishBatch(string $batchCode, string $batchStatus, string $orderStatus, string $legacyDelivery): array
    {
        $batch = DeliveryBatch::with(['batchStops', 'store', 'driver', 'group'])
            ->where('code', $batchCode)
            ->first();

        if (! $batch) {
            throw ValidationException::withMessages([
                'batch' => 'Batch not found.',
            ]);
        }

        return DB::transaction(function () use ($batch, $batchStatus, $orderStatus, $legacyDelivery) {
            $batch->update(['status' => $batchStatus]);

            if ($batch->driver_id) {
                $batch->driver()->where('current_batch_id', $batch->id)->update(['current_batch_id' => null]);
            }

            $orderCodes = $batch->batchStops->pluck('order_code')->filter()->all();
            if ($orderCodes !== []) {
                Order::whereIn('code', $orderCodes)->update([
                    'status' => $orderStatus,
                    'delivery' => $legacyDelivery,
                ]);
            }

            $batch->group?->refreshStatusFromChildren();

            if ($batch->store) {
                DispatchStoreOrdersJob::dispatch($batch->store);
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

    protected function renumberStops(int $batchId): void
    {
        $stops = DeliveryBatchStop::where('delivery_batch_id', $batchId)
            ->orderBy('stop')
            ->orderBy('id')
            ->get();

        // Two-phase update avoids any transient duplicate stop numbers if a
        // unique (batch, stop) constraint is added later, and keeps numbering
        // stable when rows are moved between batches.
        foreach ($stops as $index => $stop) {
            $stop->update(['stop' => 1000 + $index + 1]);
        }

        foreach ($stops as $index => $stop) {
            $stop->update(['stop' => $index + 1]);
        }
    }

    protected function refreshBatchAggregates(int $batchId): void
    {
        $batch = DeliveryBatch::with('batchStops')->find($batchId);
        if (! $batch) {
            return;
        }

        $batch->update([
            'stops' => $batch->batchStops->count(),
            'value' => round((float) $batch->batchStops->sum('value'), 2),
        ]);
    }

    protected function nextGroupCode(string $storeCode): string
    {
        $date = now()->format('Ymd');
        $prefix = 'BG-'.$storeCode.'-'.$date.'-';
        $seq = DeliveryBatchGroup::where('code', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
    }

    protected function refreshHubStats(string $storeCode, int $storeId): void
    {
        $hub = BatchHub::where('code', $storeCode)->first();
        if (! $hub) {
            return;
        }

        $pending = (int) Order::where('store_id', $storeId)
            ->where('status', Order::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('assignment_type')
                    ->orWhere('assignment_type', Order::ASSIGNMENT_STORE_BATCH);
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereNull('delivery_batch_id')
            ->count();

        $ordersPerBatch = max(1, (int) (BatchSetting::query()->value('orders_per_batch') ?? 5));
        $driversCount = (int) Driver::storeDrivers()
            ->whereHas('activeAssignment', fn ($q) => $q->where('store_id', $storeId))
            ->availableForBatch()
            ->count();

        $hub->update([
            'pending' => $pending,
            'drivers_count' => $driversCount,
            'est_batches' => (int) ceil($pending / max(1, $ordersPerBatch)),
            'status' => $pending > 0 ? ($hub->status === 'offline' ? 'active' : $hub->status) : $hub->status,
        ]);
    }
}
