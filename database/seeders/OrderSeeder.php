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

            $delivery = $o['delivery'] ?? null;

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
                    'delivery' => $delivery,
                    'status' => $this->statusFromLegacyDelivery($delivery, in_array('batched', $views, true)),
                    'eta' => $o['driver']['eta'] ?? null,
                    'lat' => $o['lat'] ?? null,
                    'lng' => $o['lng'] ?? null,
                    'views' => $views,
                    'locality' => $o['locality'] ?? null,
                    'zone_key' => $o['zone_key'] ?? null,
                ]
            );
        }

        // Live-map orders (out for delivery in Trivandrum) — not shown on the operations board.
        $liveMap = [
            [
                'code' => 'ORD-8924',
                'store' => 'downtown',
                'driver' => 'DRV-9001',
                'customer' => 'Anjali Nair',
                'area' => 'Pattom',
                'address' => '12 Pattom Lane — Ring bell',
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
                'lat' => 8.5094,
                'lng' => 76.9547,
            ],
            [
                'code' => 'ORD-8925',
                'store' => 'uptown',
                'driver' => 'DRV-9002',
                'customer' => 'Ravi Menon',
                'area' => 'Kowdiar',
                'address' => '22 Kowdiar Palace Rd',
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
                'lat' => 8.5089,
                'lng' => 76.9652,
            ],
            [
                'code' => 'ORD-8930',
                'store' => 'westside',
                'driver' => 'DRV-9003',
                'customer' => 'Meera Pillai',
                'area' => 'Kowdiar',
                'address' => '90 Market St, Palayam',
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
                'lat' => 8.5065,
                'lng' => 76.9540,
            ],
            [
                'code' => 'ORD-8932',
                'store' => 'harbor',
                'driver' => 'DRV-9006',
                'customer' => 'Suresh Kumar',
                'area' => 'Technopark',
                'address' => '14 Sasthamangalam',
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
                'lat' => 8.5156,
                'lng' => 76.9721,
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
                    'status' => $this->statusFromLegacyDelivery($o['delivery'] ?? null, false),
                    'eta' => $o['eta'] ?? null,
                    'distance_km' => $o['distance_km'] ?? null,
                    'lat' => $o['lat'] ?? null,
                    'lng' => $o['lng'] ?? null,
                    'views' => null,
                ]
            );
        }
    }

    /**
     * Same legacy `delivery` -> canonical `status` mapping used by the
     * 2026_07_23_000005 migration's backfill, applied to newly-seeded rows
     * so demo data doesn't all look "pending" (and doesn't spuriously
     * trigger the dispatch flow for orders that are actually already
     * resolved).
     */
    private function statusFromLegacyDelivery(?string $delivery, bool $batched): string
    {
        $map = [
            'waiting' => Order::STATUS_PENDING,
            'assigned' => Order::STATUS_ASSIGNED,
            'out' => Order::STATUS_ASSIGNED,
            'transit' => Order::STATUS_ASSIGNED,
            'delivered' => Order::STATUS_DELIVERED,
            'failed' => Order::STATUS_CANCELLED,
            'cancelled' => Order::STATUS_CANCELLED,
        ];

        $status = $map[strtolower((string) $delivery)] ?? Order::STATUS_PENDING;

        return $status === Order::STATUS_PENDING && $batched ? Order::STATUS_BATCHED : $status;
    }
}
