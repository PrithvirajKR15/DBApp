<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            // region is used by the live-map zone filter (manhattan / brooklyn)
            ['code' => 'manhattan-core', 'name' => 'Manhattan Core', 'region' => 'manhattan'],
            ['code' => 'downtown-zone', 'name' => 'Downtown Zone', 'region' => 'manhattan'],
            ['code' => 'northwest-district', 'name' => 'Northwest District', 'region' => 'manhattan'],
            ['code' => 'brooklyn-zone', 'name' => 'Brooklyn Zone', 'region' => 'brooklyn'],
            ['code' => 'uptown-area', 'name' => 'Uptown Area', 'region' => null],
            ['code' => 'east-side', 'name' => 'East Side', 'region' => null],
            ['code' => 'midtown', 'name' => 'Midtown', 'region' => null],
            ['code' => 'southeast-hub', 'name' => 'Southeast Hub', 'region' => null],
            ['code' => 'west-end', 'name' => 'West End', 'region' => null],
        ];

        foreach ($zones as $zone) {
            Zone::updateOrCreate(['name' => $zone['name']], $zone);
        }
    }
}
