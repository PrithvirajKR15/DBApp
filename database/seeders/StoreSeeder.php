<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    /**
     * Seed stores together with a store-admin user account for each one.
     */
    public function run(): void
    {
        $storeAdminRoleId = Role::findBySlug('store_admin')->id;

        $stores = [
            ['code' => 'downtown', 'name' => 'Downtown Fresh Market', 'area' => 'Central Zone', 'address' => '18 Center Plaza, Central', 'phone' => '+1 555-0300', 'lat' => 40.7128, 'lng' => -74.0060],
            ['code' => 'uptown',   'name' => 'Uptown Express',        'area' => 'North Zone',   'address' => '71 Pine Court, North',   'phone' => '+1 555-0301', 'lat' => 40.7295, 'lng' => -73.9965],
            ['code' => 'westside', 'name' => 'Westside Grocer',       'area' => 'West Zone',    'address' => '15 Harbor Rd, Westside', 'phone' => '+1 555-0302', 'lat' => 40.7180, 'lng' => -74.0150],
            ['code' => 'harbor',   'name' => 'Harbor Market',         'area' => 'South Zone',   'address' => '402 Oak Lane, South',    'phone' => '+1 555-0303', 'lat' => 40.7050, 'lng' => -74.0020],
            ['code' => 'amanora',  'name' => 'Amanora Mall Store',    'area' => 'East Zone',    'address' => 'Amanora Town Centre, Pune, MH', 'phone' => '+91 20 5550 0304', 'lat' => 18.5169, 'lng' => 73.9350],
            ['code' => 'mgroad',   'name' => 'MG Road Express',       'area' => 'Central Zone', 'address' => 'MG Road, Bengaluru, KA',        'phone' => '+91 80 5550 0305', 'lat' => 12.9757, 'lng' => 77.6068],
            ['code' => 'baner',    'name' => 'Baner Delivery Hub',    'area' => 'West Zone',    'address' => 'Baner Road, Pune, MH',          'phone' => '+91 20 5550 0306', 'lat' => 18.5590, 'lng' => 73.7868],
            ['code' => 'koregaon', 'name' => 'Koregaon Park Store',   'area' => 'East Zone',    'address' => 'Koregaon Park, Pune, MH',       'phone' => '+91 20 5550 0307', 'lat' => 18.5362, 'lng' => 73.8939],
        ];

        foreach ($stores as $data) {
            $admin = User::updateOrCreate(
                ['email' => $data['code'] . '.store@kenland.in'],
                [
                    'name' => $data['name'] . ' Admin',
                    'mobile' => '9' . str_pad((string) crc32($data['code']) % 1000000000, 9, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password@123'),
                    'role_id' => $storeAdminRoleId,
                ]
            );

            Store::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['user_id' => $admin->id, 'status' => 'active'])
            );
        }
    }
}
