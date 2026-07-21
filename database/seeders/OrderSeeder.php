<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $storesByCode = Store::pluck('id', 'code');
        $driversByCode = Driver::with('user')->get()
            ->filter(fn (Driver $d) => $d->user?->code)
            ->mapWithKeys(fn (Driver $d) => [$d->user->code => $d->id]);

        $board = include database_path('seeders/data/orders.php');

        foreach ($board['orders'] ?? [] as $o) {
            $views = $o['view'] ?? [];
            if (!empty($o['request_pending'])) {
                $views[] = 'request_pending';
            }

            $driverCode = $o['driver']['id'] ?? null;

            Order::updateOrCreate(
                ['code' => $o['id']],
                [
                    'store_id' => $storesByCode[$o['store_id'] ?? ''] ?? null,
                    'driver_id' => $driverCode ? ($driversByCode[$driverCode] ?? null) : null,
                    'customer' => $o['customer'],
                    'phone' => $o['phone'] ?? null,
                    'area' => $o['area'] ?? null,
                    'address' => $o['address'] ?? null,
                    'slot' => $o['slot'] ?? null,
                    'slot_label' => $o['slot_label'] ?? null,
                    'placed_at' => $o['placed_at'] ?? null,
                    'urgent' => (bool) ($o['urgent'] ?? false),
                    'value' => (float) ($o['value'] ?? 0),
                    'items' => (int) ($o['items'] ?? 0),
                    'payment' => $o['payment'] ?? null,
                    'prep' => $o['prep'] ?? null,
                    'prep_pct' => (int) ($o['prep_pct'] ?? 0),
                    'delivery' => $o['delivery'] ?? null,
                    'eta' => $o['driver']['eta'] ?? null,
                    'lat' => $o['lat'] ?? null,
                    'lng' => $o['lng'] ?? null,
                    'views' => $views,
                    'locality' => $o['locality'] ?? null,
                    'zone_key' => $o['zone_key'] ?? null,
                ]
            );
        }

        // Live-map orders (out for delivery) — not shown on the operations board.
        $liveMap = [
            [
                'code' => 'ORD-8924',
                'store' => 'downtown',
                'driver' => 'DRV-9001',
                'customer' => 'Olivia Bennett',
                'area' => 'Manhattan Core',
                'address' => '123 Main St',
                'slot' => 'Now',
                'slot_label' => 'Today',
                'value' => 56.20,
                'items' => 4,
                'payment' => 'online',
                'prep' => 'ready',
                'prep_pct' => 100,
                'delivery' => 'out',
                'eta' => '12 mins',
                'distance_km' => 2.4,
                'lat' => 40.7139,
                'lng' => -74.0035,
            ],
            [
                'code' => 'ORD-8925',
                'store' => 'uptown',
                'driver' => 'DRV-9002',
                'customer' => 'Noah Carter',
                'area' => 'Manhattan Core',
                'address' => '789 Broadway St',
                'slot' => 'Now',
                'slot_label' => 'Today',
                'value' => 41.00,
                'items' => 3,
                'payment' => 'online',
                'prep' => 'ready',
                'prep_pct' => 100,
                'delivery' => 'out',
                'eta' => '5 mins',
                'distance_km' => 1.1,
                'lat' => 40.7268,
                'lng' => -74.0121,
            ],
            [
                'code' => 'ORD-8930',
                'store' => 'westside',
                'driver' => 'DRV-9003',
                'customer' => 'Ava Mitchell',
                'area' => 'Manhattan Core',
                'address' => '456 Elm St',
                'slot' => 'Now',
                'slot_label' => 'Today',
                'value' => 88.75,
                'items' => 7,
                'payment' => 'online',
                'prep' => 'ready',
                'prep_pct' => 100,
                'delivery' => 'out',
                'eta' => '20 mins',
                'distance_km' => 3.7,
                'lat' => 40.7069,
                'lng' => -73.9931,
            ],
            [
                'code' => 'ORD-8932',
                'store' => 'harbor',
                'driver' => 'DRV-9006',
                'customer' => 'Liam Foster',
                'area' => 'Manhattan Core',
                'address' => '101 Pine St',
                'slot' => 'Now',
                'slot_label' => 'Today',
                'value' => 33.40,
                'items' => 2,
                'payment' => 'online',
                'prep' => 'ready',
                'prep_pct' => 100,
                'delivery' => 'out',
                'eta' => '3 mins',
                'distance_km' => 0.8,
                'lat' => 40.7121,
                'lng' => -74.0108,
            ],
        ];

        foreach ($liveMap as $o) {
            Order::updateOrCreate(
                ['code' => $o['code']],
                [
                    'store_id' => $storesByCode[$o['store']] ?? null,
                    'driver_id' => isset($o['driver']) ? ($driversByCode[$o['driver']] ?? null) : null,
                    'customer' => $o['customer'],
                    'phone' => null,
                    'area' => $o['area'] ?? null,
                    'address' => $o['address'] ?? null,
                    'slot' => $o['slot'] ?? null,
                    'slot_label' => $o['slot_label'] ?? null,
                    'placed_at' => null,
                    'urgent' => false,
                    'value' => (float) ($o['value'] ?? 0),
                    'items' => (int) ($o['items'] ?? 0),
                    'payment' => $o['payment'] ?? null,
                    'prep' => $o['prep'] ?? null,
                    'prep_pct' => (int) ($o['prep_pct'] ?? 0),
                    'delivery' => $o['delivery'] ?? null,
                    'eta' => $o['eta'] ?? null,
                    'distance_km' => $o['distance_km'] ?? null,
                    'lat' => $o['lat'] ?? null,
                    'lng' => $o['lng'] ?? null,
                    'views' => null,
                ]
            );
        }
    }
}
