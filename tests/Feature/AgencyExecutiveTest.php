<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AgencyBranch;
use App\Models\AgencyExecutive;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Zone;
use App\Services\AgencyService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgencyExecutiveTest extends TestCase
{
    use RefreshDatabase;

    private AgencyService $agencies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->agencies = app(AgencyService::class);
    }

    public function test_admin_created_agency_is_active(): void
    {
        $admin = $this->makeUser('admin');
        $agency = $this->agencies->createAgency($admin, [
            'name' => 'Rapido HQ',
            'city' => 'Trivandrum',
        ]);

        $this->assertSame(Agency::STATUS_ACTIVE, $agency->status);
        $this->assertNotNull($agency->approved_at);
    }

    public function test_store_admin_created_agency_is_pending_until_approved(): void
    {
        [$storeAdmin, $store, $zone] = $this->makeStoreAdminWithZone();

        $agency = $this->agencies->createAgency($storeAdmin, [
            'name' => 'Uber Local',
            'city' => 'Trivandrum',
        ]);

        $this->assertSame(Agency::STATUS_PENDING, $agency->status);
        $this->assertSame($store->id, $agency->store_id);

        $admin = $this->makeUser('admin');
        $this->agencies->approve($admin, $agency);

        $this->assertSame(Agency::STATUS_ACTIVE, $agency->fresh()->status);
    }

    public function test_store_admin_cannot_edit_agency_created_by_another_user(): void
    {
        [$storeAdminA] = $this->makeStoreAdminWithZone('A');
        [$storeAdminB] = $this->makeStoreAdminWithZone('B');

        $agency = $this->agencies->createAgency($storeAdminA, ['name' => 'Agency A']);

        $this->assertFalse($this->agencies->canEditAgency($storeAdminB, $agency));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->agencies->updateAgency($storeAdminB, $agency, ['name' => 'Hacked']);
    }

    public function test_store_admin_cannot_use_zone_outside_store_zones(): void
    {
        [$storeAdmin, $store, $zone] = $this->makeStoreAdminWithZone();
        $otherZone = Zone::create(['code' => 'other', 'name' => 'Other Zone', 'region' => 'south']);

        $agency = $this->agencies->createAgency($storeAdmin, ['name' => 'Agency']);
        $admin = $this->makeUser('admin');
        $this->agencies->approve($admin, $agency);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->agencies->createBranch($storeAdmin, $agency, [
            'zone_ids' => [$otherZone->id],
            'name' => 'Bad Hub',
            'cost_per_km' => 10,
            'minimum_order_charge' => 40,
        ]);
    }

    public function test_store_admin_can_list_agencies_in_store_zones_and_create_executive(): void
    {
        [$storeAdmin, $store, $zone] = $this->makeStoreAdminWithZone();
        $admin = $this->makeUser('admin');

        $agency = $this->agencies->createAgency($admin, ['name' => 'Shared Agency']);
        $branch = $this->agencies->createBranch($admin, $agency, [
            'zone_ids' => [$zone->id],
            'name' => 'Shared Hub',
            'cost_per_km' => 12,
            'minimum_order_charge' => 50,
        ]);

        $this->assertTrue($branch->coversZone($zone->id));

        $visible = $this->agencies->visibleAgenciesQuery($storeAdmin)->pluck('id');
        $this->assertTrue($visible->contains($agency->id));

        $executive = $this->agencies->createExecutive($storeAdmin, $agency, [
            'name' => 'Zone Exec',
            'email' => 'exec@example.com',
            'mobile' => '9876501234',
        ], [$branch->id]);

        $this->assertSame('executive', $executive->user->role->slug);
        $this->assertTrue($executive->branches->contains('id', $branch->id));
    }

    public function test_hub_can_include_multiple_zones_for_one_executive(): void
    {
        $admin = $this->makeUser('admin');
        $zoneA = Zone::create(['code' => 'za', 'name' => 'Zone A', 'region' => 'north']);
        $zoneB = Zone::create(['code' => 'zb', 'name' => 'Zone B', 'region' => 'south']);

        $agency = $this->agencies->createAgency($admin, ['name' => 'Fleet Co']);
        $hub = $this->agencies->createBranch($admin, $agency, [
            'zone_ids' => [$zoneA->id, $zoneB->id],
            'name' => 'Trivandrum Hub',
            'cost_per_km' => 10,
            'minimum_order_charge' => 40,
        ]);

        $this->assertCount(2, $hub->zones);
        $this->assertTrue($hub->coversZone($zoneA->id));
        $this->assertTrue($hub->coversZone($zoneB->id));

        $executive = $this->agencies->createExecutive($admin, $agency, [
            'name' => 'Exec A',
            'email' => 'execa@example.com',
            'mobile' => '9876509999',
        ], [$hub->id]);

        $zoneIds = $this->agencies->zonesForUser($executive->user)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$zoneA->id, $zoneB->id], $zoneIds);

        $this->actingAs($executive->user)
            ->get(route('executive-dashboard'))
            ->assertOk();

        $this->actingAs($executive->user)
            ->get(route('fleet-drivers-zone'))
            ->assertForbidden();
    }

    public function test_agency_approve_reject_http_endpoints(): void
    {
        [$storeAdmin] = $this->makeStoreAdminWithZone();
        $admin = $this->makeUser('admin');

        $agency = $this->agencies->createAgency($storeAdmin, ['name' => 'Pending Co']);

        $this->actingAs($admin)
            ->postJson(route('agencies.approve', $agency->id))
            ->assertOk()
            ->assertJsonPath('agency.status', 'active');

        $agency2 = $this->agencies->createAgency($storeAdmin, ['name' => 'Reject Co']);
        $this->actingAs($admin)
            ->postJson(route('agencies.reject', $agency2->id), ['rejection_reason' => 'Incomplete'])
            ->assertOk()
            ->assertJsonPath('agency.status', 'rejected');
    }

    public function test_admin_can_view_executive_drivers_page_with_pagination(): void
    {
        $admin = $this->makeUser('admin');
        $zone = Zone::create(['code' => 'ze' . uniqid(), 'name' => 'Exec Zone', 'region' => 'north']);

        $agency = $this->agencies->createAgency($admin, ['name' => 'Exec Drivers Co']);
        $hub = $this->agencies->createBranch($admin, $agency, [
            'zone_ids' => [$zone->id],
            'name' => 'Exec Hub',
            'cost_per_km' => 10,
            'minimum_order_charge' => 40,
        ]);
        $executive = $this->agencies->createExecutive($admin, $agency, [
            'name' => 'Driver Viewer',
            'email' => 'driver.viewer' . uniqid() . '@example.com',
            'mobile' => '9876512345',
        ], [$hub->id]);

        $this->actingAs($admin)
            ->get(route('agencies.executives.show', $executive->id))
            ->assertOk()
            ->assertSee('Drivers');

        $this->actingAs($admin)
            ->getJson(route('agencies.executives.drivers', $executive->id) . '?page=1&per_page=10')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['drivers', 'meta' => ['current_page', 'last_page', 'total']]);

        $this->actingAs($admin)
            ->getJson(route('agencies.list') . '?per_page=5&page=1')
            ->assertOk()
            ->assertJsonStructure(['agencies', 'meta' => ['current_page', 'per_page', 'total']]);
    }

    private function makeUser(string $roleSlug, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($roleSlug) . ' User',
            'email' => $roleSlug . uniqid() . '@example.com',
            'mobile' => '9' . random_int(100000000, 999999999),
            'password' => Hash::make('password'),
            'role_id' => Role::findBySlug($roleSlug)->id,
            'status' => 'Active',
        ], $extra));
    }

    /**
     * @return array{0: User, 1: Store, 2: Zone}
     */
    private function makeStoreAdminWithZone(string $suffix = '1'): array
    {
        $zone = Zone::create([
            'code' => 'z' . strtolower($suffix) . uniqid(),
            'name' => 'Zone ' . $suffix,
            'region' => 'north',
        ]);

        $storeAdmin = $this->makeUser('store_admin');
        $store = Store::create([
            'code' => 's' . strtolower($suffix) . uniqid(),
            'name' => 'Store ' . $suffix,
            'user_id' => $storeAdmin->id,
            'area' => $zone->name,
            'status' => 'active',
            'lat' => 8.5,
            'lng' => 76.9,
        ]);
        $storeAdmin->update(['store_id' => $store->id]);
        $store->zones()->attach($zone->id);

        return [$storeAdmin->fresh(), $store->fresh('zones'), $zone];
    }
}
