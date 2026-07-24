<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class StoreZoneSeeder extends Seeder
{
    /**
     * Link each store to the zone matching its area name (demo data).
     */
    public function run(): void
    {
        $zonesByName = Zone::query()->get()->keyBy(fn (Zone $z) => strtolower($z->name));

        foreach (Store::query()->get() as $store) {
            $area = strtolower(trim((string) $store->area));
            $zone = $zonesByName->get($area);

            if (! $zone) {
                continue;
            }

            $store->zones()->syncWithoutDetaching([$zone->id]);
        }
    }
}
