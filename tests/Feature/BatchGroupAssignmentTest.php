<?php

namespace Tests\Feature;

use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchGroup;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Order;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\BatchService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BatchGroupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_store_generated_batches_creates_parent_group_with_assigned_children(): void
    {
        $store = $this->makeStore('STR-G1');
        $driverA = $this->makeStoreDriver($store, 'DRV-GA');
        $driverB = $this->makeStoreDriver($store, 'DRV-GB');

        $orders = Order::withoutEvents(fn () => collect([
            $this->makeOrder($store, 'ORD-G1', 8.5010, 76.9510),
            $this->makeOrder($store, 'ORD-G2', 8.5012, 76.9512),
            $this->makeOrder($store, 'ORD-G3', 8.5250, 76.9500),
            $this->makeOrder($store, 'ORD-G4', 8.5252, 76.9502),
        ]));

        $result = app(BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'store_name' => $store->name,
            'overflow_count' => 2,
            'batches' => [
                [
                    'id' => 'BT-G-A',
                    'driver_code' => $driverA->user->code,
                    'stops' => 2,
                    'orders' => [
                        ['id' => 'ORD-G1', 'stop' => 1, 'customer' => 'A'],
                        ['id' => 'ORD-G2', 'stop' => 2, 'customer' => 'B'],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                    'route' => ['hub_to_first' => '0.2 km', 'return' => '0.2 km'],
                ],
                [
                    'id' => 'BT-G-B',
                    'driver_code' => $driverB->user->code,
                    'stops' => 2,
                    'orders' => [
                        ['id' => 'ORD-G3', 'stop' => 1, 'customer' => 'C'],
                        ['id' => 'ORD-G4', 'stop' => 2, 'customer' => 'D'],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                    'route' => ['hub_to_first' => '0.3 km', 'return' => '0.3 km'],
                ],
            ],
        ]);

        $this->assertArrayHasKey('group', $result);
        $this->assertCount(2, $result['batches']);

        $group = DeliveryBatchGroup::where('code', $result['group']['id'])->first();
        $this->assertNotNull($group);
        $this->assertSame(2, $group->batch_count);
        $this->assertSame(4, $group->order_count);
        $this->assertSame(2, $group->overflow_count);

        $batchA = DeliveryBatch::where('code', 'BT-G-A')->first();
        $batchB = DeliveryBatch::where('code', 'BT-G-B')->first();
        $this->assertSame(DeliveryBatch::STATUS_ASSIGNED, $batchA->status);
        $this->assertSame($batchA->id, $driverA->fresh()->current_batch_id);
        $this->assertSame($batchB->id, $driverB->fresh()->current_batch_id);

        // Overflow was only counted — orders not in the payload stay pending.
        $this->assertSame(Order::STATUS_ASSIGNED, Order::where('code', 'ORD-G1')->value('status'));
    }

    public function test_rejects_duplicate_driver_codes_in_same_group(): void
    {
        $store = $this->makeStore('STR-G2');
        $driver = $this->makeStoreDriver($store, 'DRV-DUP');

        $this->expectException(ValidationException::class);

        app(BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'batches' => [
                [
                    'id' => 'BT-DUP-1',
                    'driver_code' => $driver->user->code,
                    'orders' => [['id' => 'X1', 'stop' => 1, 'customer' => 'A']],
                ],
                [
                    'id' => 'BT-DUP-2',
                    'driver_code' => $driver->user->code,
                    'orders' => [['id' => 'X2', 'stop' => 1, 'customer' => 'B']],
                ],
            ],
        ]);
    }

    public function test_reassign_clears_previous_driver_and_locks_in_progress(): void
    {
        $store = $this->makeStore('STR-G3');
        $driverA = $this->makeStoreDriver($store, 'DRV-RA');
        $driverB = $this->makeStoreDriver($store, 'DRV-RB');
        Order::withoutEvents(fn () => $this->makeOrder($store, 'ORD-R1', 8.5010, 76.9510));

        app(BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'batches' => [[
                'id' => 'BT-REASSIGN',
                'driver_code' => $driverA->user->code,
                'orders' => [['id' => 'ORD-R1', 'stop' => 1, 'customer' => 'A']],
                'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
            ]],
        ]);

        $batch = DeliveryBatch::where('code', 'BT-REASSIGN')->first();
        $this->assertSame($batch->id, $driverA->fresh()->current_batch_id);

        app(BatchService::class)->assignStoreDriver('BT-REASSIGN', $driverB->user->code);

        $this->assertNull($driverA->fresh()->current_batch_id);
        $this->assertSame($batch->id, $driverB->fresh()->current_batch_id);
        $this->assertSame($driverB->id, $batch->fresh()->driver_id);

        $batch->update(['status' => DeliveryBatch::STATUS_IN_PROGRESS]);

        try {
            app(BatchService::class)->assignStoreDriver('BT-REASSIGN', $driverA->user->code);
            $this->fail('Expected in_progress batch to reject reassignment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('batch', $e->errors());
        }
    }

    public function test_optimizer_caps_children_by_available_drivers_and_leaves_overflow_pending(): void
    {
        $store = $this->makeStore('STR-CAP');
        $d1 = $this->makeStoreDriver($store, 'DRV-C1');
        $d2 = $this->makeStoreDriver($store, 'DRV-C2');
        $d3 = $this->makeStoreDriver($store, 'DRV-C3');

        $orders = Order::withoutEvents(function () use ($store) {
            $list = collect();
            for ($i = 1; $i <= 12; $i++) {
                $list->push($this->makeOrder(
                    $store,
                    'ORD-CAP-'.$i,
                    8.5000 + ($i * 0.0008),
                    76.9500 + ($i * 0.0004)
                ));
            }

            return $list;
        });

        $result = app(\App\Services\BatchRouteOptimizerService::class)->optimizeForStore(
            $store,
            $orders,
            collect([$d1, $d2, $d3]),
            ['orders_per_batch' => 3, 'max_distance_km' => 50, 'max_route_minutes' => 180]
        );

        $this->assertCount(3, $result['batches']);
        $fitted = collect($result['batches'])->sum(fn ($b) => count($b['orders']));
        $this->assertLessThanOrEqual(9, $fitted);
        $this->assertGreaterThanOrEqual(3, count($result['overflow']));
        $this->assertSame(12, $fitted + count($result['overflow']));
    }

    public function test_move_order_between_editable_batches_and_reorder_stops(): void
    {
        $store = $this->makeStore('STR-MV');
        $driverA = $this->makeStoreDriver($store, 'DRV-MVA');
        $driverB = $this->makeStoreDriver($store, 'DRV-MVB');

        Order::withoutEvents(fn () => collect([
            $this->makeOrder($store, 'ORD-MV1', 8.5010, 76.9510),
            $this->makeOrder($store, 'ORD-MV2', 8.5012, 76.9512),
            $this->makeOrder($store, 'ORD-MV3', 8.5250, 76.9500),
        ]));

        app(BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'batches' => [
                [
                    'id' => 'BT-MV-A',
                    'driver_code' => $driverA->user->code,
                    'orders' => [
                        ['id' => 'ORD-MV1', 'stop' => 1, 'customer' => 'A', 'value' => 10],
                        ['id' => 'ORD-MV2', 'stop' => 2, 'customer' => 'B', 'value' => 20],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                ],
                [
                    'id' => 'BT-MV-B',
                    'driver_code' => $driverB->user->code,
                    'orders' => [
                        ['id' => 'ORD-MV3', 'stop' => 1, 'customer' => 'C', 'value' => 30],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                ],
            ],
        ]);

        $moved = app(BatchService::class)->moveOrder('ORD-MV2', 'BT-MV-A', 'BT-MV-B');
        $this->assertSame(1, $moved['from']['stops']);
        $this->assertSame(2, $moved['to']['stops']);
        $this->assertSame([1], collect($moved['from']['orders'])->pluck('stop')->all());
        $this->assertSame([1, 2], collect($moved['to']['orders'])->pluck('stop')->all());
        $this->assertSame(['ORD-MV1'], collect($moved['from']['orders'])->pluck('id')->all());
        $this->assertSame(['ORD-MV3', 'ORD-MV2'], collect($moved['to']['orders'])->sortBy('stop')->pluck('id')->values()->all());
        $this->assertSame($driverB->id, Order::where('code', 'ORD-MV2')->value('driver_id'));

        // DB rows must also be contiguous after the move.
        $this->assertSame(
            [1],
            \App\Models\DeliveryBatchStop::where('delivery_batch_id', DeliveryBatch::where('code', 'BT-MV-A')->value('id'))
                ->orderBy('stop')->pluck('stop')->all()
        );
        $this->assertSame(
            [1, 2],
            \App\Models\DeliveryBatchStop::where('delivery_batch_id', DeliveryBatch::where('code', 'BT-MV-B')->value('id'))
                ->orderBy('stop')->pluck('stop')->all()
        );

        $reordered = app(BatchService::class)->reorderStops('BT-MV-B', ['ORD-MV2', 'ORD-MV3']);
        $this->assertSame(['ORD-MV2', 'ORD-MV3'], collect($reordered['orders'])->pluck('id')->all());
        $this->assertSame([1, 2], collect($reordered['orders'])->pluck('stop')->all());

        DeliveryBatch::where('code', 'BT-MV-B')->update(['status' => DeliveryBatch::STATUS_IN_PROGRESS]);

        try {
            app(BatchService::class)->moveOrder('ORD-MV1', 'BT-MV-A', 'BT-MV-B');
            $this->fail('Expected move into in_progress batch to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('batch', $e->errors());
        }
    }

    public function test_delete_child_batch_returns_orders_to_pending_and_frees_driver(): void
    {
        $store = $this->makeStore('STR-DEL');
        $driverA = $this->makeStoreDriver($store, 'DRV-DELA');
        $driverB = $this->makeStoreDriver($store, 'DRV-DELB');

        Order::withoutEvents(fn () => collect([
            $this->makeOrder($store, 'ORD-DEL1', 8.5010, 76.9510),
            $this->makeOrder($store, 'ORD-DEL2', 8.5012, 76.9512),
            $this->makeOrder($store, 'ORD-DEL3', 8.5250, 76.9500),
        ]));

        $created = app(BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'batches' => [
                [
                    'id' => 'BT-DEL-A',
                    'driver_code' => $driverA->user->code,
                    'orders' => [
                        ['id' => 'ORD-DEL1', 'stop' => 1, 'customer' => 'A'],
                        ['id' => 'ORD-DEL2', 'stop' => 2, 'customer' => 'B'],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                ],
                [
                    'id' => 'BT-DEL-B',
                    'driver_code' => $driverB->user->code,
                    'orders' => [
                        ['id' => 'ORD-DEL3', 'stop' => 1, 'customer' => 'C'],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                ],
            ],
        ]);

        $groupCode = $created['group']['id'];

        $result = app(BatchService::class)->deleteBatch('BT-DEL-A');
        $this->assertFalse($result['group_deleted']);
        $this->assertSame($groupCode, $result['group_id']);
        $this->assertNull(DeliveryBatch::where('code', 'BT-DEL-A')->first());
        $this->assertNotNull(DeliveryBatch::where('code', 'BT-DEL-B')->first());
        $this->assertNotNull(DeliveryBatchGroup::where('code', $groupCode)->first());

        $this->assertSame(Order::STATUS_PENDING, Order::where('code', 'ORD-DEL1')->value('status'));
        $this->assertNull(Order::where('code', 'ORD-DEL1')->value('delivery_batch_id'));
        $this->assertNull(Order::where('code', 'ORD-DEL1')->value('driver_id'));
        $this->assertNull($driverA->fresh()->current_batch_id);

        // Last child deletes the parent group.
        $last = app(BatchService::class)->deleteBatch('BT-DEL-B');
        $this->assertTrue($last['group_deleted']);
        $this->assertNull(DeliveryBatchGroup::where('code', $groupCode)->first());
        $this->assertSame(Order::STATUS_PENDING, Order::where('code', 'ORD-DEL3')->value('status'));
        $this->assertNull($driverB->fresh()->current_batch_id);
    }

    public function test_delete_batch_group_removes_all_children(): void
    {
        $store = $this->makeStore('STR-DG');
        $driverA = $this->makeStoreDriver($store, 'DRV-DGA');
        $driverB = $this->makeStoreDriver($store, 'DRV-DGB');

        Order::withoutEvents(fn () => collect([
            $this->makeOrder($store, 'ORD-DG1', 8.5010, 76.9510),
            $this->makeOrder($store, 'ORD-DG2', 8.5250, 76.9500),
        ]));

        $created = app(BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'batches' => [
                [
                    'id' => 'BT-DG-A',
                    'driver_code' => $driverA->user->code,
                    'orders' => [
                        ['id' => 'ORD-DG1', 'stop' => 1, 'customer' => 'A'],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                ],
                [
                    'id' => 'BT-DG-B',
                    'driver_code' => $driverB->user->code,
                    'orders' => [
                        ['id' => 'ORD-DG2', 'stop' => 1, 'customer' => 'B'],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                ],
            ],
        ]);

        $groupCode = $created['group']['id'];
        $result = app(BatchService::class)->deleteBatchGroup($groupCode);

        $this->assertSame(2, $result['batches_deleted']);
        $this->assertNull(DeliveryBatchGroup::where('code', $groupCode)->first());
        $this->assertSame(0, DeliveryBatch::whereIn('code', ['BT-DG-A', 'BT-DG-B'])->count());
        $this->assertSame(Order::STATUS_PENDING, Order::where('code', 'ORD-DG1')->value('status'));
        $this->assertSame(Order::STATUS_PENDING, Order::where('code', 'ORD-DG2')->value('status'));
        $this->assertNull($driverA->fresh()->current_batch_id);
        $this->assertNull($driverB->fresh()->current_batch_id);
    }

    public function test_cannot_delete_batch_after_delivery_started(): void
    {
        $store = $this->makeStore('STR-DL');
        $driver = $this->makeStoreDriver($store, 'DRV-DL');

        Order::withoutEvents(fn () => $this->makeOrder($store, 'ORD-DL1', 8.5010, 76.9510));

        $created = app(BatchService::class)->storeGeneratedBatches([
            'store_id' => $store->code,
            'batches' => [
                [
                    'id' => 'BT-DL-A',
                    'driver_code' => $driver->user->code,
                    'orders' => [
                        ['id' => 'ORD-DL1', 'stop' => 1, 'customer' => 'A'],
                    ],
                    'hub' => ['lat' => $store->lat, 'lng' => $store->lng, 'name' => $store->name],
                ],
            ],
        ]);

        DeliveryBatch::where('code', 'BT-DL-A')->update(['status' => DeliveryBatch::STATUS_IN_PROGRESS]);

        try {
            app(BatchService::class)->deleteBatch('BT-DL-A');
            $this->fail('Expected delete of in_progress batch to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('batch', $e->errors());
        }

        try {
            app(BatchService::class)->deleteBatchGroup($created['group']['id']);
            $this->fail('Expected delete of group with in_progress child to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('group', $e->errors());
        }
    }

    private function makeStore(string $code): Store
    {
        return Store::create([
            'code' => $code, 'name' => $code.' Store',
            'lat' => 8.5000, 'lng' => 76.9500, 'status' => 'Active',
        ]);
    }

    private function makeStoreDriver(Store $store, string $code): Driver
    {
        $user = User::create([
            'name' => $code, 'mobile' => '+91 91000 '.substr(md5($code), 0, 5),
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

        return $driver->load('user');
    }

    private function makeOrder(Store $store, string $code, float $lat, float $lng): Order
    {
        return Order::create([
            'code' => $code, 'store_id' => $store->id, 'customer' => 'Customer '.$code,
            'address' => 'Test address', 'value' => 40, 'lat' => $lat, 'lng' => $lng,
            'status' => Order::STATUS_PENDING, 'delivery' => 'waiting',
        ]);
    }
}
