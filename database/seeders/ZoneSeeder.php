<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        // Trivandrum (Thiruvananthapuram), Kerala — locality zones.
        // `region` groups localities for the live-map filter.
        $zones = [
            ['code' => 'pattom', 'name' => 'Pattom', 'region' => 'north'],
            ['code' => 'kesavadasapuram', 'name' => 'Kesavadasapuram', 'region' => 'north'],
            ['code' => 'ulloor', 'name' => 'Ulloor', 'region' => 'north'],
            ['code' => 'murinjapalam', 'name' => 'Murinjapalam', 'region' => 'north'],
            ['code' => 'kowdiar', 'name' => 'Kowdiar', 'region' => 'central'],
            ['code' => 'palayam', 'name' => 'Palayam', 'region' => 'central'],
            ['code' => 'thampanoor', 'name' => 'Thampanoor', 'region' => 'central'],
            ['code' => 'vellayambalam', 'name' => 'Vellayambalam', 'region' => 'central'],
            ['code' => 'statue', 'name' => 'Statue', 'region' => 'central'],
            ['code' => 'sasthamangalam', 'name' => 'Sasthamangalam', 'region' => 'east'],
            ['code' => 'technopark', 'name' => 'Technopark', 'region' => 'east'],
            ['code' => 'peroorkada', 'name' => 'Peroorkada', 'region' => 'east'],
            ['code' => 'medical-college', 'name' => 'Medical College', 'region' => 'west'],
            ['code' => 'kazhakkoottam', 'name' => 'Kazhakkoottam', 'region' => 'west'],
            ['code' => 'east-fort', 'name' => 'East Fort', 'region' => 'south'],
            ['code' => 'vizhinjam', 'name' => 'Vizhinjam', 'region' => 'south'],
            ['code' => 'kovalam', 'name' => 'Kovalam', 'region' => 'south'],
        ];

        foreach ($zones as $zone) {
            Zone::updateOrCreate(['code' => $zone['code']], $zone);
        }
    }
}
