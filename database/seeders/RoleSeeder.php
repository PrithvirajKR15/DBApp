<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
  /**
   * Seed the master roles table.
   */
  public function run(): void
  {
    $roles = [
      [
        'slug' => 'admin',
        'name' => 'Admin',
        'home_route' => 'dashboard-analytics',
        'is_system' => true,
      ],
      [
        'slug' => 'store_admin',
        'name' => 'Store Admin',
        'home_route' => 'store-dashboard',
        'is_system' => true,
      ],
      [
        'slug' => 'user',
        'name' => 'User',
        'home_route' => 'user-dashboard',
        'is_system' => true,
      ],
    ];

    foreach ($roles as $role) {
      Role::updateOrCreate(['slug' => $role['slug']], $role);
    }
  }
}
