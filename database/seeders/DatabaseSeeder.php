<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    User::updateOrCreate([
        'email' => 'admin@kenland.in',
    ], [
        'name' => 'Admin User',
        'mobile' => '9876543210',
        'password' => Hash::make('password@123'),
        'role' => 'admin',
    ]);

    User::updateOrCreate([
        'email' => 'user@kenland.in',
    ], [
        'name' => 'Regular User',
        'mobile' => '1234567890',
        'password' => Hash::make('password@123'),
        'role' => 'user',
    ]);
  }
}