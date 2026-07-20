<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call(RoleSeeder::class);

    User::updateOrCreate([
        'email' => 'admin@kenland.in',
    ], [
        'name' => 'Admin User',
        'mobile' => '9876543210',
        'password' => Hash::make('password@123'),
        'role_id' => Role::findBySlug('admin')->id,
    ]);

    User::updateOrCreate([
        'email' => 'storeadmin@kenland.in',
    ], [
        'name' => 'Store Admin User',
        'mobile' => '9123456780',
        'password' => Hash::make('password@123'),
        'role_id' => Role::findBySlug('store_admin')->id,
    ]);

    User::updateOrCreate([
        'email' => 'user@kenland.in',
    ], [
        'name' => 'Regular User',
        'mobile' => '1234567890',
        'password' => Hash::make('password@123'),
        'role_id' => Role::findBySlug('user')->id,
    ]);

    $this->call([
        StoreSeeder::class,
        ZoneSeeder::class,
        DriverSeeder::class,
        OrderSeeder::class,
    ]);
  }
}
