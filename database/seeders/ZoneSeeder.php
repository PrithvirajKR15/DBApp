<?php

namespace Database\Seeders;

use App\Models\Zone;
use App\Services\ZoneCoverageService;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        // Trivandrum (Thiruvananthapuram), Kerala — locality zones.
        // `region` groups localities for the live-map filter.
        // `pincodes` map postal codes → areas so drivers pick zones, not pins.
        // `lat`/`lng` are zone centroids used as map fallbacks (and copied onto
        // zone_pincodes when pins are synced).
        $zones = [
            // North
            ['code' => 'pattom', 'name' => 'Pattom', 'region' => 'north', 'lat' => 8.5241, 'lng' => 76.9366, 'pincodes' => ['695004', '695014']],
            ['code' => 'kesavadasapuram', 'name' => 'Kesavadasapuram', 'region' => 'north', 'lat' => 8.5360, 'lng' => 76.9360, 'pincodes' => ['695004', '695010']],
            ['code' => 'ulloor', 'name' => 'Ulloor', 'region' => 'north', 'lat' => 8.5370, 'lng' => 76.9250, 'pincodes' => ['695011', '695012']],
            ['code' => 'murinjapalam', 'name' => 'Murinjapalam', 'region' => 'north', 'lat' => 8.5300, 'lng' => 76.9450, 'pincodes' => ['695011', '695035']],

            // Central
            ['code' => 'kowdiar', 'name' => 'Kowdiar', 'region' => 'central', 'lat' => 8.5089, 'lng' => 76.9652, 'pincodes' => ['695003', '695010']],
            ['code' => 'palayam', 'name' => 'Palayam', 'region' => 'central', 'lat' => 8.5065, 'lng' => 76.9540, 'pincodes' => ['695033', '695001', '695034']],
            ['code' => 'thampanoor', 'name' => 'Thampanoor', 'region' => 'central', 'lat' => 8.4870, 'lng' => 76.9520, 'pincodes' => ['695001', '695014']],
            ['code' => 'vellayambalam', 'name' => 'Vellayambalam', 'region' => 'central', 'lat' => 8.5100, 'lng' => 76.9600, 'pincodes' => ['695010', '695003']],
            ['code' => 'statue', 'name' => 'Statue', 'region' => 'central', 'lat' => 8.4875, 'lng' => 76.9525, 'pincodes' => ['695001', '695023']],

            // East
            ['code' => 'sasthamangalam', 'name' => 'Sasthamangalam', 'region' => 'east', 'lat' => 8.5156, 'lng' => 76.9721, 'pincodes' => ['695010', '695013']],
            ['code' => 'technopark', 'name' => 'Technopark', 'region' => 'east', 'lat' => 8.5580, 'lng' => 76.8810, 'pincodes' => ['695581', '695582']],
            ['code' => 'peroorkada', 'name' => 'Peroorkada', 'region' => 'east', 'lat' => 8.5450, 'lng' => 76.9650, 'pincodes' => ['695005', '695006']],

            // West
            ['code' => 'medical-college', 'name' => 'Medical College', 'region' => 'west', 'lat' => 8.5235, 'lng' => 76.9280, 'pincodes' => ['695011', '695012']],
            ['code' => 'kazhakkoottam', 'name' => 'Kazhakkoottam', 'region' => 'west', 'lat' => 8.5680, 'lng' => 76.8700, 'pincodes' => ['695582', '695581']],

            // South
            ['code' => 'east-fort', 'name' => 'East Fort', 'region' => 'south', 'lat' => 8.4830, 'lng' => 76.9475, 'pincodes' => ['695023', '695001', '695024']],
            ['code' => 'vizhinjam', 'name' => 'Vizhinjam', 'region' => 'south', 'lat' => 8.3780, 'lng' => 76.9910, 'pincodes' => ['695521', '695526']],
            ['code' => 'kovalam', 'name' => 'Kovalam', 'region' => 'south', 'lat' => 8.4000, 'lng' => 76.9780, 'pincodes' => ['695527', '695521']],
        ];

        $coverage = app(ZoneCoverageService::class);

        foreach ($zones as $zoneData) {
            $pincodes = $zoneData['pincodes'];
            unset($zoneData['pincodes']);

            $zone = Zone::updateOrCreate(['code' => $zoneData['code']], $zoneData);
            $coverage->syncZonePincodes($zone, $pincodes);
        }
    }
}
