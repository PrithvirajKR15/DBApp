<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Store-manager path: directly assign an order to a STORE_ASSIGNED driver
 * that belongs to the order's store. Third-party drivers are rejected here —
 * they must claim via the broadcast accept race.
 */
class StoreOrderAssignmentService
{
    public function __construct(
        protected OrderTimelineService $timeline
    ) {}

    public function assign(Order $order, Driver $driver): Order
    {
        if (! $driver->isStoreDriver()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Only store-assigned drivers can be manually assigned.',
            ]);
        }

        $driverStoreId = $driver->store_id
            ?? $driver->activeAssignment?->store_id;

        if ((int) $driverStoreId !== (int) $order->store_id) {
            throw ValidationException::withMessages([
                'driver_id' => 'Driver does not belong to this order\'s store.',
            ]);
        }

        if ($driver->dispatch_status === Driver::DISPATCH_OFFLINE
            || $driver->availability === 'Offline') {
            throw ValidationException::withMessages([
                'driver_id' => 'Driver is offline.',
            ]);
        }

        return DB::transaction(function () use ($order, $driver) {
            $locked = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($locked->driver_id !== null) {
                throw ValidationException::withMessages([
                    'order' => 'Order is already assigned.',
                ]);
            }

            if (in_array($locked->status, [
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'order' => "Cannot assign order in status [{$locked->status}].",
                ]);
            }

            $lockedDriver = Driver::where('id', $driver->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'driver_id' => $lockedDriver->id,
                'status' => Order::STATUS_ASSIGNED,
                'assignment_type' => Order::ASSIGNMENT_STORE_BATCH,
                'delivery' => 'assigned',
            ]);

            $lockedDriver->update([
                'dispatch_status' => Driver::DISPATCH_BUSY,
                'availability' => 'Transit',
            ]);

            $fresh = $locked->fresh(['driver.user', 'store']);
            $this->timeline->syncFromOrderState($fresh, [
                \App\Models\OrderTimelineStep::KEY_ASSIGNED => now()->format('h:i A'),
            ]);

            return $fresh;
        });
    }
}
