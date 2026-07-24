<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderTimelineStep;
use App\Models\Store;
use App\Services\OrderTimelineService;
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
        $catalog = include database_path('seeders/data/order_line_items.php');
        $detailsByCode = include database_path('seeders/data/order_details.php');
        $timeline = app(OrderTimelineService::class);

        foreach ($board['orders'] ?? [] as $o) {
            $views = $o['view'] ?? [];
            if (! empty($o['request_pending'])) {
                $views[] = 'request_pending';
            }

            $driverCode = $o['driver']['id'] ?? null;
            $delivery = $o['delivery'] ?? null;
            $extras = $detailsByCode[$o['id']] ?? $this->defaultExtras($o);
            $lineItems = $this->sliceLineItems($catalog, (int) ($extras['line_item_count'] ?? $o['items'] ?? 4));
            $itemCount = $this->countLineItems($lineItems);

            $order = Order::updateOrCreate(
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
                    'items' => $itemCount > 0 ? $itemCount : (int) ($o['items'] ?? 0),
                    'line_items' => $lineItems,
                    'payment' => $o['payment'] ?? null,
                    'prep' => $o['prep'] ?? null,
                    'prep_pct' => (int) ($o['prep_pct'] ?? 0),
                    'delivery' => $delivery,
                    'status' => $this->statusFromLegacyDelivery($delivery, in_array('batched', $views, true)),
                    'eta' => $extras['eta'] ?? $o['driver']['eta'] ?? null,
                    'distance_km' => $extras['distance_km'] ?? null,
                    'lat' => $o['lat'] ?? null,
                    'lng' => $o['lng'] ?? null,
                    'views' => $views,
                    'locality' => $o['locality'] ?? null,
                    'zone_key' => $o['zone_key'] ?? null,
                ]
            );

            $this->seedDetail($order, $extras);
            $this->seedTimeline($timeline, $order, $o['placed_at'] ?? null);
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
            $lineItems = $this->sliceLineItems($catalog, (int) ($o['items'] ?? 3));
            $itemCount = $this->countLineItems($lineItems);

            $order = Order::updateOrCreate(
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
                    'items' => $itemCount,
                    'line_items' => $lineItems,
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

            $this->seedDetail($order, [
                'customer_code' => 'CUS-L'.substr($o['code'], -4),
                'vip' => false,
                'phone_alt' => null,
                'avatar' => '1.png',
                'landmark' => null,
                'instructions' => 'Standard delivery.',
                'packages' => $itemCount.' Items',
                'weight' => null,
                'card_last4' => '1001',
            ]);
            $this->seedTimeline($timeline, $order, '08:00 AM', [
                OrderTimelineStep::KEY_PLACED => '08:00 AM',
                OrderTimelineStep::KEY_PICKING => '08:10 AM',
                OrderTimelineStep::KEY_PACKING => '08:25 AM',
                OrderTimelineStep::KEY_READY => '08:40 AM',
                OrderTimelineStep::KEY_ASSIGNED => '08:45 AM',
                OrderTimelineStep::KEY_PICKED_UP => '08:55 AM',
                OrderTimelineStep::KEY_OUT => '09:00 AM',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    private function seedDetail(Order $order, array $extras): void
    {
        OrderDetail::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_code' => $extras['customer_code'] ?? null,
                'vip' => (bool) ($extras['vip'] ?? false),
                'phone_alt' => $extras['phone_alt'] ?? null,
                'avatar' => $extras['avatar'] ?? '1.png',
                'landmark' => $extras['landmark'] ?? null,
                'instructions' => $extras['instructions'] ?? null,
                'packages' => $extras['packages'] ?? null,
                'weight' => $extras['weight'] ?? null,
                'card_last4' => $extras['card_last4'] ?? null,
            ]
        );
    }

    /**
     * @param  array<string, string|null>  $times
     */
    private function seedTimeline(OrderTimelineService $timeline, Order $order, ?string $placedAt, array $times = []): void
    {
        $order->timelineSteps()->delete();

        $defaults = [
            OrderTimelineStep::KEY_PLACED => $placedAt,
            OrderTimelineStep::KEY_PICKING => '08:28 AM',
            OrderTimelineStep::KEY_PACKING => '08:45 AM',
            OrderTimelineStep::KEY_READY => $order->prep === 'ready' ? '09:02 AM' : null,
            OrderTimelineStep::KEY_ASSIGNED => $order->driver_id ? '—' : null,
        ];

        $timeline->syncFromOrderState($order->fresh(), array_merge($defaults, $times));
    }

    /**
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    private function defaultExtras(array $o): array
    {
        $n = (int) ($o['items'] ?? 4);

        return [
            'customer_code' => 'CUS-'.str_pad((string) (abs(crc32($o['id'])) % 9000 + 1000), 4, '0', STR_PAD_LEFT),
            'vip' => (bool) ($o['urgent'] ?? false),
            'phone_alt' => null,
            'avatar' => ['1.png', '2.png', '3.png', '4.png', '5.png', '6.png'][abs(crc32($o['id'])) % 6],
            'landmark' => null,
            'instructions' => 'Please deliver to the address on file.',
            'packages' => max(1, (int) ceil($n / 3)).' Bags',
            'weight' => '~'.number_format(max(2, $n * 0.9), 1).' kg',
            'card_last4' => ($o['payment'] ?? '') === 'online' ? '4242' : null,
            'distance_km' => 2.5 + (abs(crc32($o['id'])) % 40) / 10,
            'eta' => '~'.(10 + abs(crc32($o['id'])) % 25).' min',
            'line_item_count' => max(2, min(8, $n)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $catalog
     * @return list<array<string, mixed>>
     */
    private function sliceLineItems(array $catalog, int $count): array
    {
        $count = max(1, min(count($catalog), $count));

        return array_values(array_slice($catalog, 0, $count));
    }

    /**
     * @param  list<array{qty?: float|int}>  $items
     */
    private function countLineItems(array $items): int
    {
        return (int) round(array_sum(array_map(
            fn (array $item) => (float) ($item['qty'] ?? 0),
            $items
        )));
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
