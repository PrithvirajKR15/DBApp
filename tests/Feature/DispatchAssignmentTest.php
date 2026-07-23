<?php

namespace Tests\Feature;

use App\Models\BroadcastOffer;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Order;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\BroadcastDispatchService;
use App\Services\StoreDriverAssignmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DispatchAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Store has enough available drivers -> orders batch correctly with
     * route sequencing (not an arbitrary even split).
     */
    public function test_store_batches_pending_orders_across_available_drivers_with_route_sequencing(): void
    {
        $store = Store::create([
            'code' => 'STR-1', 'name' => 'Test Store',
            'lat' => 8.5000, 'lng' => 76.9500, 'status' => 'Active',
        ]);

        $driverA = $this->makeStoreDriver($store, 'DRV-A');
        $driverB = $this->makeStoreDriver($store, 'DRV-B');

        // Cap batches at 2 stops each so 4 orders forces a real split across
        // both drivers (mirrors the spec's own example: 10 orders / 2
        // drivers -> 2 batches — here scaled down to 4 orders / cap 2).
        \App\Models\BatchSetting::create([
            'orders_per_batch' => 2, 'max_distance_km' => 10, 'max_route_minutes' => 45,
        ]);

        // Two geographic clusters, both well within range of the hub:
        // orders 1-2 near each other, orders 3-4 near each other elsewhere.
        $orders = Order::withoutEvents(fn () => collect([
            $this->makeOrder($store, 'ORD-1', 8.5010, 76.9510, 40),
            $this->makeOrder($store, 'ORD-2', 8.5015, 76.9515, 60),
            $this->makeOrder($store, 'ORD-3', 8.5250, 76.9500, 30),
            $this->makeOrder($store, 'ORD-4', 8.5255, 76.9505, 50),
        ]));

        app(StoreDriverAssignmentService::class)->dispatchPendingOrders($store->fresh());

        $batches = $store->fresh()->orders()->get(); // sanity: relation works
        $freshOrders = Order::whereIn('code', ['ORD-1', 'ORD-2', 'ORD-3', 'ORD-4'])->get();

        // With a 2-per-batch cap and 4 orders, this must split into exactly
        // 2 batches (one per driver) — not left in a single oversized batch,
        // and not more batches than there are available drivers.
        $batchIds = $freshOrders->pluck('delivery_batch_id')->unique()->filter();
        $this->assertCount(2, $batchIds);

        foreach ($freshOrders as $order) {
            $this->assertSame(Order::ASSIGNMENT_STORE_BATCH, $order->assignment_type);
            $this->assertSame(Order::STATUS_ASSIGNED, $order->status);
            $this->assertNotNull($order->driver_id);
            $this->assertNotNull($order->delivery_batch_id);
        }

        // Each batch has its own driver, and the driver is now busy.
        foreach ($batchIds as $batchId) {
            $batch = \App\Models\DeliveryBatch::find($batchId);
            $this->assertNotNull($batch->driver_id);
            $this->assertSame($batch->driver->fresh()->current_batch_id, $batch->id);

            // Stops are sequenced 1..N with no gaps/duplicates.
            $stopNumbers = $batch->batchStops()->pluck('stop')->sort()->values()->all();
            $this->assertSame(range(1, count($stopNumbers)), $stopNumbers);
        }

        // Both drivers were used (2 orders each cluster -> 2 distinct batches/drivers).
        $this->assertNotEquals(
            \App\Models\DeliveryBatch::whereIn('id', $batchIds)->pluck('driver_id')->unique()->count(),
            1
        );
    }

    /**
     * Store drivers all busy -> orders correctly flow to broadcast.
     */
    public function test_orders_flow_to_broadcast_when_all_store_drivers_are_busy(): void
    {
        $store = Store::create([
            'code' => 'STR-2', 'name' => 'Busy Store',
            'lat' => 8.5000, 'lng' => 76.9500, 'status' => 'Active',
        ]);

        $driver = $this->makeStoreDriver($store, 'DRV-BUSY');
        // Mark the only store driver busy (an active batch already assigned).
        $busyBatch = \App\Models\DeliveryBatch::create([
            'code' => 'BATCH-EXIST', 'store_id' => $store->id, 'driver_id' => $driver->id,
            'status' => \App\Models\DeliveryBatch::STATUS_ASSIGNED, 'stops' => 1,
        ]);
        $driver->update(['current_batch_id' => $busyBatch->id]);

        // A third-party driver online nearby so broadcast has someone to notify.
        $thirdParty = $this->makeThirdPartyDriver('DRV-TP1', 8.5001, 76.9501);

        $order = Order::withoutEvents(fn () => $this->makeOrder($store, 'ORD-OVERFLOW', 8.5005, 76.9505, 70));

        app(StoreDriverAssignmentService::class)->dispatchPendingOrders($store->fresh());

        $order->refresh();
        $this->assertSame(Order::STATUS_BROADCASTING, $order->status);
        $this->assertSame(Order::ASSIGNMENT_BROADCAST, $order->assignment_type);
        $this->assertNull($order->delivery_batch_id);
        $this->assertNull($order->driver_id);

        $this->assertDatabaseHas('broadcast_offers', [
            'order_id' => $order->id,
            'driver_id' => $thirdParty->id,
            'status' => BroadcastOffer::STATUS_PENDING,
        ]);
    }

    /**
     * Two third-party drivers accept a broadcast order simultaneously ->
     * only one succeeds, the other gets an "offer no longer available"
     * response (race-safety via row lock on the order, see
     * BroadcastDispatchService::acceptOffer).
     */
    public function test_only_one_driver_wins_when_two_accept_the_same_broadcast_order_simultaneously(): void
    {
        $store = Store::create([
            'code' => 'STR-3', 'name' => 'Race Store',
            'lat' => 8.5000, 'lng' => 76.9500, 'status' => 'Active',
        ]);

        $driver1 = $this->makeThirdPartyDriver('DRV-R1', 8.5001, 76.9501);
        $driver2 = $this->makeThirdPartyDriver('DRV-R2', 8.5002, 76.9502);

        $order = Order::withoutEvents(fn () => $this->makeOrder($store, 'ORD-RACE', 8.5003, 76.9503, 45));

        $broadcasts = app(BroadcastDispatchService::class);
        $broadcasts->broadcast($order->fresh());

        $offer1 = BroadcastOffer::where('order_id', $order->id)->where('driver_id', $driver1->id)->first();
        $offer2 = BroadcastOffer::where('order_id', $order->id)->where('driver_id', $driver2->id)->first();

        $this->assertNotNull($offer1);
        $this->assertNotNull($offer2);

        // Simulate driver 1 winning the race.
        $winningOrder = $broadcasts->acceptOffer($offer1, $driver1);
        $this->assertSame($driver1->id, $winningOrder->driver_id);
        $this->assertSame(Order::STATUS_ASSIGNED, $winningOrder->status);

        // Driver 2's subsequent accept attempt on the same order must fail cleanly.
        try {
            $broadcasts->acceptOffer($offer2->fresh(), $driver2);
            $this->fail('Expected the second accept to be rejected as no longer available.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('offer', $e->errors());
        }

        $this->assertSame(BroadcastOffer::STATUS_ACCEPTED, $offer1->fresh()->status);
        $this->assertSame(BroadcastOffer::STATUS_EXPIRED, $offer2->fresh()->status);

        // Only one driver ends up holding the order.
        $this->assertSame(1, Order::where('id', $order->id)->where('driver_id', $driver1->id)->count());
    }

    /**
     * A broadcast-accepted order can never later be pulled into a batch.
     */
    public function test_broadcast_accepted_order_is_never_later_added_to_a_batch(): void
    {
        $store = Store::create([
            'code' => 'STR-4', 'name' => 'Guard Store',
            'lat' => 8.5000, 'lng' => 76.9500, 'status' => 'Active',
        ]);

        $thirdParty = $this->makeThirdPartyDriver('DRV-G1', 8.5001, 76.9501);
        $order = Order::withoutEvents(fn () => $this->makeOrder($store, 'ORD-GUARD', 8.5002, 76.9502, 55));

        $broadcasts = app(BroadcastDispatchService::class);
        $broadcasts->broadcast($order->fresh());

        $offer = BroadcastOffer::where('order_id', $order->id)->where('driver_id', $thirdParty->id)->first();
        $broadcasts->acceptOffer($offer, $thirdParty);

        $order->refresh();
        $this->assertSame(Order::ASSIGNMENT_BROADCAST, $order->assignment_type);
        $this->assertFalse($order->canBeBatched());

        // Now a store driver frees up for this store and a dispatch pass
        // runs — the already-broadcast order must not be swept into a batch.
        $storeDriver = $this->makeStoreDriver($store, 'DRV-LATE');
        app(StoreDriverAssignmentService::class)->dispatchPendingOrders($store->fresh());

        $order->refresh();
        $this->assertSame(Order::ASSIGNMENT_BROADCAST, $order->assignment_type);
        $this->assertNull($order->delivery_batch_id);
        $this->assertSame($thirdParty->id, $order->driver_id);

        // Defensive guard: even if an admin manually tries to include this
        // broadcast-accepted order's code in a hand-built batch via the
        // admin "Generate Delivery Batch" flow, it must be skipped.
        $batchable = Order::withoutEvents(fn () => $this->makeOrder($store, 'ORD-OK', 8.5003, 76.9503, 40));

        app(\App\Services\BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'store_name' => $store->name,
            'overflow_count' => 0,
            'batches' => [[
                'id' => 'BATCH-MANUAL-1',
                'driver_code' => $storeDriver->user->code,
                'orders' => [
                    ['id' => $order->code, 'stop' => 1, 'customer' => $order->customer],
                    ['id' => $batchable->code, 'stop' => 2, 'customer' => $batchable->customer],
                ],
            ]],
        ]);

        $this->assertDatabaseMissing('delivery_batch_stops', ['order_code' => $order->code]);
        $this->assertDatabaseHas('delivery_batch_stops', ['order_code' => $batchable->code]);
        $this->assertSame(Order::ASSIGNMENT_BROADCAST, $order->fresh()->assignment_type);
    }

    private function makeStoreDriver(Store $store, string $code): Driver
    {
        $user = User::create([
            'name' => $code, 'mobile' => '+91 90000 '.substr(md5($code), 0, 5),
            'email' => strtolower($code).'@test.com', 'password' => 'x',
            'role_id' => Role::findBySlug('user')->id, 'code' => $code, 'status' => 'Active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id, 'driver_type' => Driver::TYPE_STORE,
            'availability' => 'Online', 'joined_at' => now(),
        ]);

        DriverAssignment::create([
            'driver_id' => $driver->id, 'store_id' => $store->id, 'zone_id' => null,
            'type' => 'store', 'is_active' => true, 'assigned_at' => now(),
        ]);

        return $driver;
    }

    private function makeThirdPartyDriver(string $code, float $lat, float $lng): Driver
    {
        $user = User::create([
            'name' => $code, 'mobile' => '+91 90001 '.substr(md5($code), 0, 5),
            'email' => strtolower($code).'@test.com', 'password' => 'x',
            'role_id' => Role::findBySlug('user')->id, 'code' => $code, 'status' => 'Active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id, 'driver_type' => Driver::TYPE_THIRD_PARTY,
            'availability' => 'Online', 'joined_at' => now(),
        ]);

        $driver->locations()->create([
            'lat' => $lat, 'lng' => $lng, 'live_status' => 'Idle', 'recorded_at' => now(),
        ]);

        return $driver;
    }

    private function makeOrder(Store $store, string $code, float $lat, float $lng, float $value): Order
    {
        return Order::create([
            'code' => $code, 'store_id' => $store->id, 'customer' => 'Customer '.$code,
            'address' => 'Test address', 'value' => $value, 'lat' => $lat, 'lng' => $lng,
            'status' => Order::STATUS_PENDING, 'delivery' => 'waiting',
        ]);
    }
}
