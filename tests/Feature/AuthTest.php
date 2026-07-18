<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests are redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that a user can log in with a mobile number.
     */
    public function test_user_can_login_with_mobile(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'mobile' => '9876543210',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'login' => '9876543210',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard-analytics'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that an admin (or user) can log in with an email.
     */
    public function test_admin_can_login_with_email(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'mobile' => '9876543210',
            'email' => 'admin@kenland.in',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login' => 'admin@kenland.in',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard-analytics'));
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * Test that login fails with incorrect password.
     */
    public function test_login_fails_with_incorrect_password(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'mobile' => '9876543210',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

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

        $response->assertRedirect(route('dashboard-analytics'));
        $this->assertDatabaseHas('users', [
            'mobile' => '1234567890',
            'role' => 'user',
        ]);
        $this->assertAuthenticated();
    }

    /**
     * Test admin dashboard permissions.
     */
    public function test_only_admin_can_access_admin_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'mobile' => '9876543210',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $user = User::create([
            'name' => 'Regular User',
            'mobile' => '1234567890',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Non-logged in redirects to login
        $this->get(route('admin-dashboard'))->assertRedirect(route('login'));

        // Admin can access
        $this->actingAs($admin)->get(route('admin-dashboard'))->assertStatus(200);

        // Regular user gets 403 Forbidden
        $this->actingAs($user)->get(route('admin-dashboard'))->assertStatus(403);
    }

    /**
     * Test user dashboard permissions.
     */
    public function test_only_user_can_access_user_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'mobile' => '9876543210',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $user = User::create([
            'name' => 'Regular User',
            'mobile' => '1234567890',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Non-logged in redirects to login
        $this->get(route('user-dashboard'))->assertRedirect(route('login'));

        // User can access
        $this->actingAs($user)->get(route('user-dashboard'))->assertStatus(200);

        // Admin gets 403 Forbidden
        $this->actingAs($admin)->get(route('user-dashboard'))->assertStatus(403);
    }
}
