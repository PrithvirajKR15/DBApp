<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    /**
     * Seed Trivandrum stores together with a store-admin user for each one.
     */
    public function run(): void
    {
        $storeAdminRoleId = Role::findBySlug('store_admin')->id;

        $stores = [
            ['code' => 'downtown', 'name' => 'Pattom Fresh Hub', 'area' => 'Pattom', 'address' => 'Pattom Palace Rd, Thiruvananthapuram, Kerala 695004', 'phone' => '+91 471 555 0300', 'lat' => 8.5241, 'lng' => 76.9366],
            ['code' => 'uptown', 'name' => 'Palayam Express', 'area' => 'Palayam', 'address' => 'Palayam Market Rd, Thiruvananthapuram, Kerala 695033', 'phone' => '+91 471 555 0301', 'lat' => 8.5065, 'lng' => 76.9540],
            ['code' => 'westside', 'name' => 'Medical College Mart', 'area' => 'Medical College', 'address' => 'Medical College Jn, Thiruvananthapuram, Kerala 695011', 'phone' => '+91 471 555 0302', 'lat' => 8.5235, 'lng' => 76.9280],
            ['code' => 'harbor', 'name' => 'East Fort Market', 'area' => 'East Fort', 'address' => 'East Fort, Chalai, Thiruvananthapuram, Kerala 695023', 'phone' => '+91 471 555 0303', 'lat' => 8.4830, 'lng' => 76.9475],
            ['code' => 'eastside', 'name' => 'Technopark Fresh Hub', 'area' => 'Technopark', 'address' => 'Technopark Phase 1, Kazhakkoottam, Kerala 695581', 'phone' => '+91 471 555 0308', 'lat' => 8.5580, 'lng' => 76.8810],
            ['code' => 'central', 'name' => 'Kowdiar Central Store', 'area' => 'Kowdiar', 'address' => 'Kowdiar Palace Rd, Thiruvananthapuram, Kerala 695003', 'phone' => '+91 471 555 0309', 'lat' => 8.5089, 'lng' => 76.9652],
            ['code' => 'ulloor', 'name' => 'Ulloor Mini Mart', 'area' => 'Ulloor', 'address' => 'Ulloor-Akkulam Rd, Thiruvananthapuram, Kerala 695011', 'phone' => '+91 471 555 0304', 'lat' => 8.5370, 'lng' => 76.9250],
            ['code' => 'kazhakkoottam', 'name' => 'Kazhakkoottam Hub', 'area' => 'Kazhakkoottam', 'address' => 'NH 66, Kazhakkoottam, Thiruvananthapuram, Kerala 695582', 'phone' => '+91 471 555 0305', 'lat' => 8.5680, 'lng' => 76.8700],
            ['code' => 'sasthamangalam', 'name' => 'Sasthamangalam Store', 'area' => 'Sasthamangalam', 'address' => 'Sasthamangalam Rd, Thiruvananthapuram, Kerala 695010', 'phone' => '+91 471 555 0306', 'lat' => 8.5156, 'lng' => 76.9721],
            ['code' => 'vizhinjam', 'name' => 'Vizhinjam Beach Store', 'area' => 'Vizhinjam', 'address' => 'Vizhinjam Harbour Rd, Thiruvananthapuram, Kerala 695521', 'phone' => '+91 471 555 0307', 'lat' => 8.3780, 'lng' => 76.9910],
        ];

        foreach ($stores as $data) {
            $admin = User::updateOrCreate(
                ['email' => $data['code'] . '.store@kenland.in'],
                [
                    'name' => $data['name'] . ' Admin',
                    'mobile' => '9' . str_pad((string) (crc32($data['code']) % 1000000000), 9, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password@123'),
                    'role_id' => $storeAdminRoleId,
                ]
            );

            Store::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['user_id' => $admin->id, 'status' => 'active'])
            );

            $store = Store::where('code', $data['code'])->first();
            if ($store) {
                $admin->update(['store_id' => $store->id]);
            }
        }
    }
}
