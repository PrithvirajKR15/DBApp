<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $avatar = null;

            if (!empty($data['user-avatar-file'])) {

                $avatar = $data['user-avatar-file']->store('users', 'public');

            } 
            // else {

            //     $avatar = strtolower($data['user-gender'] ?? '') === 'female'
            //         ? 'assets/img/avatars/6.png'
            //         : 'assets/img/avatars/7.png';
            // }

            //$password = Str::random(10);

            $user = User::create([
                'name' => trim($data['user-first-name'].' '.$data['user-last-name']),
                'email' => $data['user-email'],
                'mobile' => $data['user-phone'],
                'dob' => $data['user-dob'] ?? null,
                'gender' => $data['user-gender'] ?? null,
                'address' => $data['user-address'] ?? null,
                'store_id' => $data['user-store'] ?? null,
                'image' => $avatar,
                'password' => Hash::make($data['password']),
                'role_id' => $data['user-role'],
                'status' => 'Active',
            ]);
            

            // Spatie Role
            //$user->assignRole($data['user-role']);

            return $user;
        });
    }
}