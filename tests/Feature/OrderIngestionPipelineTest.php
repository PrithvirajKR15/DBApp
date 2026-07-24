<?php

namespace Tests\Feature;

use App\Jobs\BroadcastOrderJob;
use App\Models\BroadcastOffer;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Order;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use App\Services\BroadcastDispatchService;
use App\Services\OrderIngestionService;
use App\Services\StoreOrderAssignmentService;
use App\Services\StoreSyncService;
use App\Services\ZoneCoverageService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderIngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config([
            'services.partner.api_token' => 'test-partner-token',
            'services.google.geocoding_enabled' => false,
            'services.google.geocoding_fallback' => true,
        ]);
    }

    public function test_partner_can_sync_store_with_serviceable_pincodes(): void
    {
        $this->makeZone('statue', 'Statue', ['695001', '695002']);

        $response = $this->withToken('test-partner-token')
            ->postJson('/api/v1/stores/sync', [
                'external_store_id' => 'STORE_99',
                'name' => 'MG Road Hub',
                'latitude' => 8.5241,
                'longitude' => 76.9366,
                'serviceable_pincodes' => ['695001', '695002'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('store.external_store_id', 'STORE_99')
            ->assertJsonPath('store.serviceable_pincodes.0', '695001');

        $this->assertDatabaseHas('stores', ['external_store_id' => 'STORE_99']);
        $this->assertDatabaseHas('store_pincodes', ['pincode' => '695001']);
        $this->assertDatabaseHas('store_zones', [
            'zone_id' => Zone::where('code', 'statue')->value('id'),
        ]);
    }

    public function test_order_sync_saves_store_from_payload_and_geocodes_address(): void
    {
        app(StoreSyncService::class)->sync([
            'external_store_id' => 'STORE_99',
            'name' => 'MG Road Hub',
            'latitude' => 8.5241,
            'longitude' => 76.9366,
            'serviceable_pincodes' => ['695001'],
        ]);

        $response = $this->withToken('test-partner-token')
            ->postJson('/api/v1/orders/sync', [
                'external_order_id' => 'ORD_12345',
                'store_id' => 'STORE_99',
                'customer_name' => 'John Doe',
                'customer_phone' => '+919876543210',
                'full_address' => 'Flat 4B, Emerald Heights, MG Road',
                'pincode' => '695001',
                'items' => [
                    ['code' => 'SKU-100', 'name' => 'Basmati Rice 1kg', 'qty' => 2, 'price' => 120.5],
                    ['code' => 'SKU-200', 'name' => 'Milk 500ml', 'quantity' => 1, 'price' => 30],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('order.external_order_id', 'ORD_12345')
            ->assertJsonPath('order.store_id', 'STORE_99')
            ->assertJsonPath('order.geocode_status', 'success')
            ->assertJsonPath('order.status', Order::STATUS_PENDING)
            ->assertJsonPath('order.item_count', 3)
            ->assertJsonPath('order.items.0.code', 'SKU-100');

        $order = Order::where('external_order_id', 'ORD_12345')->first();
        $this->assertNotNull($order->lat);
        $this->assertNotNull($order->lng);
        $this->assertSame('695001', $order->pincode);
        $this->assertNull($order->driver_id);
        $this->assertSame(3, (int) $order->items);
        $this->assertCount(2, $order->line_items);
        $this->assertEquals(271.0, (float) $order->value);
        $this->assertNotNull($order->detail);
        $this->assertGreaterThanOrEqual(8, $order->timelineSteps()->count());
    }

    public function test_ready_for_pickup_broadcasts_to_idle_third_party_drivers_in_matching_zone(): void
    {
        Queue::fake();

        $zoneMatch = $this->makeZone('statue', 'Statue', ['695001']);
        $zoneOther = $this->makeZone('kovalam', 'Kovalam', ['695527']);

        app(StoreSyncService::class)->sync([
            'external_store_id' => 'STORE_99',
            'name' => 'Hub',
            'serviceable_pincodes' => ['695001'],
        ]);

        $matching = $this->makeThirdParty('DRV-MATCH', Driver::DISPATCH_IDLE, $zoneMatch);
        $otherZone = $this->makeThirdParty('DRV-OTHER', Driver::DISPATCH_IDLE, $zoneOther);
        $busy = $this->makeThirdParty('DRV-BUSY', Driver::DISPATCH_BUSY, $zoneMatch);

        $order = app(OrderIngestionService::class)->sync([
            'external_order_id' => 'ORD_READY',
            'store_id' => 'STORE_99',
            'customer_name' => 'Jane',
            'customer_phone' => '+919999999999',
            'full_address' => 'Somewhere',
            'pincode' => '695001',
        ]);

        $this->withToken('test-partner-token')
            ->postJson("/api/v1/orders/{$order->id}/ready-for-pickup")
            ->assertOk()
            ->assertJsonPath('order.status', Order::STATUS_READY_FOR_PICKUP);

        Queue::assertPushed(BroadcastOrderJob::class);

        app(BroadcastDispatchService::class)->broadcast($order->fresh());

        $this->assertDatabaseHas('broadcast_offers', [
            'order_id' => $order->id,
            'driver_id' => $matching->id,
            'status' => BroadcastOffer::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('broadcast_offers', [
            'order_id' => $order->id,
            'driver_id' => $otherZone->id,
        ]);
        $this->assertDatabaseMissing('broadcast_offers', [
            'order_id' => $order->id,
            'driver_id' => $busy->id,
        ]);
    }

    public function test_accept_endpoint_is_atomic_first_driver_wins(): void
    {
        $zone = $this->makeZone('statue', 'Statue', ['695001']);

        app(StoreSyncService::class)->sync([
            'external_store_id' => 'STORE_99',
            'name' => 'Hub',
            'serviceable_pincodes' => ['695001'],
        ]);

        $driver1 = $this->makeThirdParty('DRV-A1', Driver::DISPATCH_IDLE, $zone);
        $driver2 = $this->makeThirdParty('DRV-A2', Driver::DISPATCH_IDLE, $zone);

        $order = app(OrderIngestionService::class)->sync([
            'external_order_id' => 'ORD_RACE',
            'store_id' => 'STORE_99',
            'customer_name' => 'Race',
            'customer_phone' => '+911111111111',
            'full_address' => 'Addr',
            'pincode' => '695001',
        ]);

        app(BroadcastDispatchService::class)->broadcast($order->fresh());

        Sanctum::actingAs($driver1->user);
        $this->postJson("/api/v1/orders/{$order->id}/accept")
            ->assertOk()
            ->assertJsonPath('order.status', Order::STATUS_ASSIGNED);

        Sanctum::actingAs($driver2->user);
        $this->postJson("/api/v1/orders/{$order->id}/accept")
            ->assertStatus(409);

        $this->assertSame($driver1->id, $order->fresh()->driver_id);
        $this->assertSame(Driver::DISPATCH_BUSY, $driver1->fresh()->dispatch_status);
    }

    public function test_store_manager_can_assign_store_driver_directly(): void
    {
        $store = app(StoreSyncService::class)->sync([
            'external_store_id' => 'STORE_99',
            'name' => 'Hub',
            'serviceable_pincodes' => ['695001'],
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'mobile' => '+91 90000 11111',
            'password' => 'x',
            'role_id' => Role::findBySlug('admin')->id,
            'status' => 'Active',
        ]);

        $driver = $this->makeStoreDriver($store, 'DRV-STORE');

        $order = app(OrderIngestionService::class)->sync([
            'external_order_id' => 'ORD_ASSIGN',
            'store_id' => 'STORE_99',
            'customer_name' => 'Cust',
            'customer_phone' => '+912222222222',
            'full_address' => 'Addr',
            'pincode' => '695001',
        ]);

        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/admin/orders/{$order->id}/assign-driver", [
            'driver_id' => $driver->id,
        ])->assertOk()
            ->assertJsonPath('order.driver_id', $driver->id)
            ->assertJsonPath('order.status', Order::STATUS_ASSIGNED);

        $this->assertSame(Order::ASSIGNMENT_STORE_BATCH, $order->fresh()->assignment_type);
        $this->assertSame(Driver::DISPATCH_BUSY, $driver->fresh()->dispatch_status);
    }

    public function test_store_assignment_rejects_third_party_driver(): void
    {
        $zone = $this->makeZone('statue', 'Statue', ['695001']);

        app(StoreSyncService::class)->sync([
            'external_store_id' => 'STORE_99',
            'name' => 'Hub',
            'serviceable_pincodes' => ['695001'],
        ]);

        $tp = $this->makeThirdParty('DRV-TP', Driver::DISPATCH_IDLE, $zone);
        $order = app(OrderIngestionService::class)->sync([
            'external_order_id' => 'ORD_BAD',
            'store_id' => 'STORE_99',
            'customer_name' => 'Cust',
            'customer_phone' => '+913333333333',
            'full_address' => 'Addr',
            'pincode' => '695001',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(StoreOrderAssignmentService::class)->assign($order, $tp);
    }

    /**
     * @param  list<string>  $pincodes
     */
    private function makeZone(string $code, string $name, array $pincodes): Zone
    {
        $zone = Zone::create([
            'code' => $code,
            'name' => $name,
            'region' => 'central',
        ]);

        app(ZoneCoverageService::class)->syncZonePincodes($zone, $pincodes);

        return $zone->fresh();
    }

    private function makeStoreDriver(Store $store, string $code): Driver
    {
        $user = User::create([
            'name' => $code,
            'mobile' => '+91 90000 '.substr(md5($code), 0, 5),
            'email' => strtolower($code).'@test.com',
            'password' => 'x',
            'role_id' => Role::findBySlug('user')->id,
            'code' => $code,
            'status' => 'Active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_type' => Driver::TYPE_STORE,
            'store_id' => $store->id,
            'availability' => 'Online',
            'dispatch_status' => Driver::DISPATCH_IDLE,
            'joined_at' => now(),
        ]);

        DriverAssignment::create([
            'driver_id' => $driver->id,
            'store_id' => $store->id,
            'zone_id' => null,
            'type' => 'store',
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        return $driver;
    }

    private function makeThirdParty(string $code, string $dispatchStatus, Zone $zone): Driver
    {
        $user = User::create([
            'name' => $code,
            'mobile' => '+91 90001 '.substr(md5($code), 0, 5),
            'email' => strtolower($code).'@test.com',
            'password' => 'x',
            'role_id' => Role::findBySlug('user')->id,
            'code' => $code,
            'status' => 'Active',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_type' => Driver::TYPE_THIRD_PARTY,
            'availability' => $dispatchStatus === Driver::DISPATCH_OFFLINE ? 'Offline' : 'Online',
            'dispatch_status' => $dispatchStatus,
            'joined_at' => now(),
            'service_areas' => [],
        ]);

        DriverAssignment::create([
            'driver_id' => $driver->id,
            'store_id' => null,
            'zone_id' => $zone->id,
            'type' => 'zone',
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        return $driver;
    }
}
