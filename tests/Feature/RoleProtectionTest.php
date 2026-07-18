<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class RoleProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $systemRoles = Role::where('is_system', true)->get();
        $this->assertNotEmpty($systemRoles);

        foreach ($systemRoles as $role) {
            try {
                $role->delete();
                $this->fail("Expected deleting system role '{$role->slug}' to throw.");
            } catch (LogicException) {
                // expected
            }
        }

        $this->assertSame($systemRoles->count(), Role::where('is_system', true)->count());
    }

    public function test_system_role_slug_cannot_be_changed(): void
    {
        $admin = Role::findBySlug('admin');

        $this->expectException(LogicException::class);
        $admin->update(['slug' => 'super_admin']);
    }

    public function test_system_flag_cannot_be_removed(): void
    {
        $admin = Role::findBySlug('admin');

        $this->expectException(LogicException::class);
        $admin->update(['is_system' => false]);
    }

    public function test_system_role_name_and_home_route_can_be_changed(): void
    {
        $storeAdmin = Role::findBySlug('store_admin');
        $storeAdmin->update([
            'name' => 'Store Manager',
            'home_route' => 'store-dashboard',
        ]);

        $this->assertDatabaseHas('roles', [
            'slug' => 'store_admin',
            'name' => 'Store Manager',
            'is_system' => true,
        ]);
    }

    public function test_custom_roles_can_be_edited_and_deleted(): void
    {
        $role = Role::create([
            'name' => 'Auditor',
            'slug' => 'auditor',
            'home_route' => 'user-dashboard',
            'is_system' => false,
        ]);

        $role->update(['slug' => 'auditor_v2', 'name' => 'Auditor V2']);
        $this->assertDatabaseHas('roles', ['slug' => 'auditor_v2']);

        $role->delete();
        $this->assertDatabaseMissing('roles', ['slug' => 'auditor_v2']);
    }
}
