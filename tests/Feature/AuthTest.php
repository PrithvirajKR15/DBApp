<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $roleSlug, string $mobile, ?string $email = null): User
    {
        return User::create([
            'name' => ucfirst(str_replace('_', ' ', $roleSlug)) . ' Test',
            'mobile' => $mobile,
            'email' => $email,
            'password' => bcrypt('password'),
            'role_id' => Role::findBySlug($roleSlug)->id,
        ]);
    }

    /**
     * Test that guests are redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that a user can log in with a mobile number and lands on the user dashboard.
     */
    public function test_user_can_login_with_mobile(): void
    {
        $user = $this->makeUser('user', '9876543210');

        $response = $this->post('/login', [
            'login' => '9876543210',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->followRedirects($response)->assertOk();
        $this->get(route('dashboard'))->assertRedirect(route('user-dashboard'));
    }

    /**
     * Test that an admin can log in with an email and lands on the admin dashboard.
     */
    public function test_admin_can_login_with_email(): void
    {
        $admin = $this->makeUser('admin', '9876543210', 'admin@kenland.in');

        $response = $this->post('/login', [
            'login' => 'admin@kenland.in',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('dashboard'))->assertRedirect(route('dashboard-analytics'));
    }

    /**
     * Test that a store admin lands on the store dashboard after login.
     */
    public function test_store_admin_lands_on_store_dashboard(): void
    {
        $storeAdmin = $this->makeUser('store_admin', '9123456780', 'storeadmin@kenland.in');

        $response = $this->post('/login', [
            'login' => 'storeadmin@kenland.in',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($storeAdmin);
        $this->get(route('dashboard'))->assertRedirect(route('store-dashboard'));
    }

    /**
     * Test that login fails with incorrect password.
     */
    public function test_login_fails_with_incorrect_password(): void
    {
        $this->makeUser('user', '9876543210');

        $response = $this->post('/login', [
            'login' => '9876543210',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test that a user can register.
     */
    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'mobile' => '1234567890',
            'email' => 'jane@example.com',
            'password' => 'password',
            'role' => 'user',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'mobile' => '1234567890',
            'role_id' => Role::findBySlug('user')->id,
        ]);
        $this->assertAuthenticated();
    }

    /**
     * Test that a store admin can register.
     */
    public function test_store_admin_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Store Owner',
            'mobile' => '9988776655',
            'password' => 'password',
            'role' => 'store_admin',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'mobile' => '9988776655',
            'role_id' => Role::findBySlug('store_admin')->id,
        ]);
        $this->assertAuthenticated();
    }

    /**
     * Test that registration rejects roles not present in the roles table.
     */
    public function test_registration_rejects_invalid_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Hacker',
            'mobile' => '1112223334',
            'password' => 'password',
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }

    /**
     * Test admin dashboard permissions.
     */
    public function test_only_admin_can_access_admin_pages(): void
    {
        $admin = $this->makeUser('admin', '9876543210');
        $storeAdmin = $this->makeUser('store_admin', '9123456780');
        $user = $this->makeUser('user', '1234567890');

        // Non-logged in redirects to login
        $this->get(route('live-map'))->assertRedirect(route('login'));

        // Admin can access
        $this->actingAs($admin)->get(route('dashboard-analytics'))->assertStatus(200);
        $this->actingAs($admin)->get(route('live-map'))->assertStatus(200);

        // Others get 403 Forbidden on admin pages, and redirected off the admin dashboard
        $this->actingAs($storeAdmin)->get(route('live-map'))->assertStatus(403);
        $this->actingAs($storeAdmin)->get(route('dashboard-analytics'))->assertRedirect(route('store-dashboard'));
        $this->actingAs($user)->get(route('live-map'))->assertStatus(403);
        $this->actingAs($user)->get(route('dashboard-analytics'))->assertRedirect(route('user-dashboard'));
    }

    /**
     * Test store dashboard permissions.
     */
    public function test_only_store_admin_can_access_store_dashboard(): void
    {
        $admin = $this->makeUser('admin', '9876543210');
        $storeAdmin = $this->makeUser('store_admin', '9123456780');
        $user = $this->makeUser('user', '1234567890');

        $this->get(route('store-dashboard'))->assertRedirect(route('login'));

        $this->actingAs($storeAdmin)->get(route('store-dashboard'))->assertStatus(200);
        $this->actingAs($admin)->get(route('store-dashboard'))->assertStatus(403);
        $this->actingAs($user)->get(route('store-dashboard'))->assertStatus(403);
    }

    /**
     * Test user dashboard permissions.
     */
    public function test_only_user_can_access_user_dashboard(): void
    {
        $admin = $this->makeUser('admin', '9876543210');
        $storeAdmin = $this->makeUser('store_admin', '9123456780');
        $user = $this->makeUser('user', '1234567890');

        $this->get(route('user-dashboard'))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('user-dashboard'))->assertStatus(200);
        $this->actingAs($admin)->get(route('user-dashboard'))->assertStatus(403);
        $this->actingAs($storeAdmin)->get(route('user-dashboard'))->assertStatus(403);
    }
}
