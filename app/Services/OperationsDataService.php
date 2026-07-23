<?php

namespace App\Services;

use App\Models\BankTransfer;
use App\Models\BatchHub;
use App\Models\BatchSetting;
use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchGroup;
use App\Models\DeliveryBatchStop;
use App\Models\Driver;
use App\Models\DriverPayoutProfile;
use App\Models\Order;
use App\Models\PlatformOrderEarning;
use App\Models\PlatformTransaction;
use App\Models\Store;
use Illuminate\Support\Str;

class OperationsDataService
{
    /**
     * @return array<string, mixed>
     */
    public function ordersPageData(): array
    {
        $boardOrders = Order::with(['store', 'driver.user'])
            ->whereNotNull('views')
            ->orderByDesc('id')
            ->get();

        $statusKeys = ['new', 'waiting', 'assigned', 'accepted', 'ready', 'out', 'delivered'];
        $metrics = [];
        foreach ($statusKeys as $key) {
            $metrics[$key] = (int) Order::where('delivery', $key)->whereNotNull('views')->count();
        }

        $boardStoreCodes = ['downtown', 'uptown', 'westside', 'harbor'];
        $storesByCode = Store::whereIn('code', $boardStoreCodes)->get()->keyBy('code');
        $stores = collect($boardStoreCodes)
            ->filter(fn ($code) => $storesByCode->has($code))
            ->map(function ($code) use ($storesByCode) {
                $store = $storesByCode[$code];

                return [
                    'id' => $store->code,
                    'name' => $store->name,
                    'count' => (int) Order::where('store_id', $store->id)->whereNotNull('views')->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'metrics' => $metrics,
            'stores' => $stores,
            'areas' => ['North Zone', 'South Zone', 'East Zone', 'West Zone', 'Central Zone'],
            'slots' => ['08:00–10:00', '10:00–12:00', '12:00–14:00', '14:00–16:00', '16:00–18:00', '18:00–20:00'],
            'orders' => $boardOrders->map(fn (Order $order) => $this->shapeBoardOrder($order))->values()->all(),
            'nearby_drivers' => $this->nearbyDrivers(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function batchesPageData(): array
    {
        $settingsRow = BatchSetting::query()->first();
        $settings = $settingsRow ? [
            'orders_per_batch' => (int) $settingsRow->orders_per_batch,
            'accept_minutes' => (int) $settingsRow->accept_minutes,
            'max_distance_km' => (float) $settingsRow->max_distance_km,
            'max_route_minutes' => (int) $settingsRow->max_route_minutes,
            'prefer_store_drivers' => (bool) $settingsRow->prefer_store_drivers,
            'auto_fallback_zone' => (bool) $settingsRow->auto_fallback_zone,
            'slot_window' => $settingsRow->slot_window,
        ] : [
            'orders_per_batch' => 5,
            'accept_minutes' => 5,
            'max_distance_km' => 10,
            'max_route_minutes' => 45,
            'prefer_store_drivers' => true,
            'auto_fallback_zone' => true,
            'slot_window' => '14:00–18:00',
        ];

        $storesByCode = Store::pluck('id', 'code');
        $ordersPerBatch = max(1, (int) ($settings['orders_per_batch'] ?? 5));

        $pendingByStore = Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('assignment_type')
                    ->orWhere('assignment_type', Order::ASSIGNMENT_STORE_BATCH);
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->selectRaw('store_id, COUNT(*) as total')
            ->groupBy('store_id')
            ->pluck('total', 'store_id');

        $driverCountsByStore = Driver::storeDrivers()
            ->whereHas('activeAssignment')
            ->with('activeAssignment')
            ->get()
            ->groupBy(fn (Driver $d) => $d->activeAssignment?->store_id)
            ->map->count();

        $availableDriverCountsByStore = Driver::storeDrivers()
            ->availableForBatch()
            ->whereHas('activeAssignment')
            ->with('activeAssignment')
            ->get()
            ->groupBy(fn (Driver $d) => $d->activeAssignment?->store_id)
            ->map->count();

        $stores = BatchHub::query()
            ->orderBy('id')
            ->get()
            ->map(function (BatchHub $hub) use ($storesByCode, $pendingByStore, $driverCountsByStore, $availableDriverCountsByStore, $ordersPerBatch) {
                $storeId = $storesByCode[$hub->code] ?? null;
                $pending = $storeId ? (int) ($pendingByStore[$storeId] ?? 0) : (int) $hub->pending;
                $drivers = $storeId ? (int) ($driverCountsByStore[$storeId] ?? 0) : (int) $hub->drivers_count;
                $availableDrivers = $storeId ? (int) ($availableDriverCountsByStore[$storeId] ?? 0) : 0;
                $capacity = max(0, $availableDrivers) * $ordersPerBatch;

                return [
                    'id' => $hub->code,
                    'name' => $hub->name,
                    'zone' => $hub->zone,
                    'branch' => $hub->branch,
                    'pending' => $pending,
                    'drivers' => $drivers,
                    'available_drivers' => $availableDrivers,
                    'capacity' => $capacity,
                    'est_batches' => min(
                        $availableDrivers,
                        (int) ceil($pending / max(1, $ordersPerBatch))
                    ),
                    'status' => $hub->status,
                    'slot' => $hub->slot,
                    'color' => $hub->color,
                    'lat' => $hub->lat,
                    'lng' => $hub->lng,
                ];
            })
            ->values()
            ->all();

        $pendingOrders = Order::with('store')
            ->where('status', Order::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('assignment_type')
                    ->orWhere('assignment_type', Order::ASSIGNMENT_STORE_BATCH);
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('id')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->code,
                'store_id' => $order->store?->code,
                'customer' => $order->customer,
                'address' => $order->address,
                'locality' => $order->locality,
                'area' => $order->area,
                'zone_key' => $order->zone_key,
                'lat' => $order->lat !== null ? (float) $order->lat : null,
                'lng' => $order->lng !== null ? (float) $order->lng : null,
                'value' => (float) $order->value,
                'payment' => $order->payment,
                'prep' => $order->prep,
            ])
            ->values()
            ->all();

        $groups = DeliveryBatchGroup::with(['store', 'batches.batchStops', 'batches.store'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->get();

        // Fall back to recent groups if nothing was created today (demo/seed).
        if ($groups->isEmpty()) {
            $groups = DeliveryBatchGroup::with(['store', 'batches.batchStops', 'batches.store'])
                ->orderByDesc('id')
                ->limit(40)
                ->get();
        }

        $shapedGroups = $groups
            ->map(fn (DeliveryBatchGroup $group) => $this->shapeBatchGroup($group))
            ->values()
            ->all();

        $batches = $groups
            ->flatMap(fn (DeliveryBatchGroup $group) => $group->batches)
            ->map(fn (DeliveryBatch $batch) => $this->shapeBatch($batch))
            ->values()
            ->all();

        // Group by store for the index hierarchy.
        $storesByCodeMap = collect($stores)->keyBy('id');
        $groupsByStore = collect($shapedGroups)
            ->groupBy('store_id')
            ->map(function ($storeGroups, $storeCode) use ($storesByCodeMap) {
                $storeMeta = $storesByCodeMap->get($storeCode);

                return [
                    'id' => $storeCode,
                    'name' => $storeMeta['name'] ?? ($storeGroups->first()['store'] ?? $storeCode),
                    'zone' => $storeMeta['zone'] ?? null,
                    'pending' => $storeMeta['pending'] ?? 0,
                    'available_drivers' => $storeMeta['available_drivers'] ?? 0,
                    'groups' => $storeGroups->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'settings' => $settings,
            'stores' => $stores,
            'stores_with_groups' => $groupsByStore,
            'groups' => $shapedGroups,
            'pending_orders' => $pendingOrders,
            'batches' => $batches,
            'drivers' => $this->batchDrivers(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payoutsPageData(): array
    {
        $drivers = DriverPayoutProfile::with(['payouts.orders'])
            ->orderBy('id')
            ->get()
            ->map(function (DriverPayoutProfile $profile) {
                return [
                    'id' => $profile->driver_code,
                    'name' => $profile->name,
                    'phone' => $profile->phone,
                    'type' => $profile->type,
                    'zone' => $profile->zone,
                    'avatar' => $profile->avatar,
                    'lifetime_paid' => (float) $profile->lifetime_paid,
                    'pending_amount' => (float) $profile->pending_amount,
                    'paid_orders' => (int) $profile->paid_orders,
                    'last_payout_at' => optional($profile->last_payout_at)?->format('Y-m-d H:i:s'),
                    'status' => $profile->status,
                    'history' => $profile->payouts->map(function ($payout) {
                        return [
                            'id' => $payout->code,
                            'paid_at' => optional($payout->paid_at)?->format('Y-m-d H:i:s'),
                            'period' => $payout->period,
                            'method' => $payout->method,
                            'reference' => $payout->reference,
                            'status' => $payout->status,
                            'orders' => $payout->orders->map(fn ($order) => [
                                'order_id' => $order->order_code,
                                'delivered_at' => optional($order->delivered_at)?->format('Y-m-d H:i:s'),
                                'delivery_fee' => (float) $order->delivery_fee,
                                'bonus' => (float) $order->bonus,
                                'deduction' => (float) $order->deduction,
                                'net' => (float) $order->net,
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        $transfers = BankTransfer::query()->orderByDesc('requested_date')->get();
        $payouts = $transfers->map(fn (BankTransfer $t) => [
            'id' => $t->code,
            'requested_date' => optional($t->requested_date)?->format('Y-m-d'),
            'bank' => $t->bank,
            'account_ending' => $t->account_ending,
            'amount' => (float) $t->amount,
            'status' => $t->status,
            'settled_date' => optional($t->settled_date)?->format('Y-m-d'),
        ])->values()->all();

        $pendingSum = (float) $transfers->whereIn('status', ['pending', 'processing'])->sum('amount');
        $settledSum = (float) $transfers->where('status', 'settled')->sum('amount');
        $available = max(0, collect($drivers)->sum('pending_amount'));

        return [
            'metrics' => [
                'available_balance' => $available,
                'pending_payouts' => $pendingSum,
                'settled_payouts' => $settledSum,
                'next_settlement' => now()->addDay()->format('F j, Y'),
            ],
            'drivers' => $drivers,
            'payouts' => $payouts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function earningsPageData(): array
    {
        $orderEarnings = PlatformOrderEarning::query()
            ->orderByDesc('earned_at')
            ->get()
            ->map(fn (PlatformOrderEarning $row) => [
                'order_id' => $row->order_code,
                'date' => optional($row->earned_at)?->format('Y-m-d H:i:s'),
                'store' => $row->store_name,
                'customer' => $row->customer,
                'driver' => $row->driver_name,
                'order_amount' => (float) $row->order_amount,
                'delivery_fee' => (float) $row->delivery_fee,
                'refund' => (float) $row->refund,
                'net_earning' => (float) $row->net_earning,
                'status' => $row->status,
            ])
            ->values()
            ->all();

        $transactions = PlatformTransaction::query()
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (PlatformTransaction $txn) => [
                'id' => $txn->code,
                'date' => optional($txn->occurred_at)?->format('Y-m-d H:i:s'),
                'type' => $txn->type,
                'order_id' => $txn->order_code,
                'driver' => $txn->driver_name,
                'amount' => (float) $txn->amount,
                'status' => $txn->status,
            ])
            ->values()
            ->all();

        $gross = (float) collect($orderEarnings)->sum('order_amount');
        $fees = (float) collect($orderEarnings)->sum('delivery_fee');
        $refunds = (float) collect($orderEarnings)->sum('refund');
        $net = (float) collect($orderEarnings)->sum('net_earning');

        return [
            'metrics' => [
                'gross_revenue' => $gross,
                'delivery_fees' => $fees,
                'refunds' => $refunds,
                'net_earnings' => $net,
                'change' => 8.4,
            ],
            'order_earnings' => $orderEarnings,
            'transactions' => $transactions,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrderForDetail(string $code): ?array
    {
        $decoded = urldecode($code);
        $order = Order::with(['store', 'driver.user'])
            ->whereRaw('LOWER(code) = ?', [Str::lower($decoded)])
            ->first();

        if ($order) {
            return $this->shapeBoardOrder($order);
        }

        return $this->findBatchOrder($decoded);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBatchOrder(string $code): ?array
    {
        $decoded = urldecode($code);
        $stop = DeliveryBatchStop::with('batch.store')
            ->whereRaw('LOWER(order_code) = ?', [Str::lower($decoded)])
            ->first();

        if (!$stop) {
            return null;
        }

        $batch = $stop->batch;
        $deliveryMap = [
            'Waiting' => 'waiting',
            'Accepted' => 'accepted',
            'Assigned' => 'assigned',
            'Out Delivery' => 'out',
            'Delivered' => 'delivered',
            'Ready' => 'ready',
            'Packing' => 'packing',
        ];
        $paymentMap = [
            'Paid' => 'online',
            'COD' => 'cod',
            'Apple Pay' => 'apple',
        ];
        $prepMap = [
            'Ready' => 'ready',
            'Packing' => 'packing',
            'Not Started' => 'not_started',
        ];

        $deliveryLabel = $stop->delivery ?? ($batch?->status === 'completed' ? 'Delivered' : 'Waiting');
        $delivery = $deliveryMap[$deliveryLabel] ?? Str::lower(str_replace(' ', '_', $deliveryLabel));

        return [
            'id' => $stop->order_code,
            'placed_at' => '09:00 AM',
            'customer' => $stop->customer,
            'phone' => '+1 555-0100',
            'area' => $batch?->zone_key ? Str::title($batch->zone_key) . ' Zone' : ($batch?->zone ?? ''),
            'address' => preg_replace('/\s*[—-].*$/u', '', (string) $stop->address),
            'store' => $batch?->hub_name ?? $batch?->store?->name,
            'store_id' => $batch?->store?->code ?? 'downtown',
            'slot' => '10:00–12:00',
            'slot_label' => 'Today',
            'urgent' => false,
            'value' => (float) $stop->value,
            'items' => 4,
            'payment' => $paymentMap[$stop->payment] ?? Str::lower((string) $stop->payment),
            'prep' => $prepMap[$stop->prep] ?? 'ready',
            'prep_pct' => ($stop->prep === 'Packing') ? 50 : 100,
            'delivery' => $delivery,
            'driver' => $batch?->driver_code ? [
                'name' => $batch->driver_name,
                'id' => $batch->driver_code,
                'eta' => null,
                'avatar' => $batch->driver_avatar,
            ] : null,
            'view' => [],
            'batch_id' => $batch?->code,
            'locality' => $stop->locality,
            'lat' => $stop->lat,
            'lng' => $stop->lng,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPayoutDriver(string $code): ?array
    {
        $data = $this->payoutsPageData();

        return collect($data['drivers'])->firstWhere('id', $code);
    }

    /**
     * @return array<string, mixed>
     */
    protected function shapeBoardOrder(Order $order): array
    {
        $views = $order->views ?? [];
        $requestPending = in_array('request_pending', $views, true);
        $view = array_values(array_filter($views, fn ($v) => $v !== 'request_pending'));

        $driver = null;
        if ($order->driver?->user) {
            $user = $order->driver->user;
            $driver = [
                'name' => $user->name,
                'id' => $user->code,
                'eta' => $order->eta,
                'avatar' => $user->image ?? null,
            ];
        }

        return [
            'id' => $order->code,
            'placed_at' => $order->placed_at,
            'customer' => $order->customer,
            'phone' => $order->phone,
            'area' => $order->area,
            'address' => $order->address,
            'store' => $order->store?->name,
            'store_id' => $order->store?->code,
            'slot' => $order->slot,
            'slot_label' => $order->slot_label,
            'urgent' => (bool) $order->urgent,
            'value' => (float) $order->value,
            'items' => (int) $order->items,
            'payment' => $order->payment,
            'prep' => $order->prep,
            'prep_pct' => (int) $order->prep_pct,
            'delivery' => $order->delivery,
            'driver' => $driver,
            'view' => $view,
            'request_pending' => $requestPending,
            'locality' => $order->locality,
            'zone_key' => $order->zone_key,
            'lat' => $order->lat,
            'lng' => $order->lng,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeBatchGroup(DeliveryBatchGroup $group): array
    {
        $batches = $group->relationLoaded('batches')
            ? $group->batches
            : $group->batches()->with(['batchStops', 'store'])->get();

        return [
            'id' => $group->code,
            'store_id' => $group->store?->code,
            'store' => $group->store?->name ?? $batches->first()?->hub_name,
            'status' => $group->status,
            'batch_count' => (int) $group->batch_count,
            'order_count' => (int) $group->order_count,
            'overflow_count' => (int) $group->overflow_count,
            'slot_window' => $group->slot_window,
            'created_at' => optional($group->created_at)->toDateTimeString(),
            'deletable' => $batches->every(fn (DeliveryBatch $batch) => $batch->isEditable()),
            'batches' => $batches->map(function (DeliveryBatch $batch) use ($group) {
                $shaped = $this->shapeBatch($batch);
                $shaped['group_id'] = $group->code;

                return $shaped;
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function shapeBatch(DeliveryBatch $batch): array
    {
        $shaped = [
            'id' => $batch->code,
            'group_id' => $batch->relationLoaded('group') ? $batch->group?->code : null,
            'zone' => $batch->zone,
            'zone_key' => $batch->zone_key,
            'route_label' => $batch->route_label,
            'store' => $batch->hub_name ?? $batch->store?->name,
            'store_id' => $batch->store?->code,
            'status' => $batch->status,
            'editable' => $batch->isEditable(),
            'stops' => (int) $batch->stops,
            'distance' => $batch->distance,
            'est_time' => $batch->est_time,
            'value' => (float) $batch->value,
            'driver' => $batch->driver_code ? [
                'name' => $batch->driver_name,
                'id' => $batch->driver_code,
                'avatar' => $batch->driver_avatar,
            ] : null,
            'hub' => ($batch->hub_lat !== null && $batch->hub_lng !== null) ? [
                'lat' => $batch->hub_lat,
                'lng' => $batch->hub_lng,
                'name' => $batch->hub_name,
            ] : null,
            'orders' => $batch->batchStops
                ->sortBy([
                    ['stop', 'asc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(fn (DeliveryBatchStop $stop) => [
                    'stop' => (int) $stop->stop,
                    'id' => $stop->order_code,
                    'customer' => $stop->customer,
                    'address' => $stop->address,
                    'locality' => $stop->locality,
                    'lat' => $stop->lat,
                    'lng' => $stop->lng,
                    'value' => (float) $stop->value,
                    'payment' => $stop->payment,
                    'prep' => $stop->prep,
                    'delivery' => $stop->delivery,
                ])
                ->all(),
            'route' => [
                'hub_to_first' => $batch->route_hub_to_first,
                'return' => $batch->route_return,
            ],
        ];

        // Prefer live stop count from the relation after moves/reorders.
        $shaped['stops'] = count($shaped['orders']);

        return $shaped;
    }

    /**
     * Drivers available for batch generation / assignment UI.
     *
     * @return list<array<string, mixed>>
     */
    protected function batchDrivers(): array
    {
        $assignedLoads = DeliveryBatch::query()
            ->whereIn('status', [
                DeliveryBatch::STATUS_PENDING,
                DeliveryBatch::STATUS_ASSIGNED,
                DeliveryBatch::STATUS_IN_PROGRESS,
            ])
            ->whereNotNull('driver_code')
            ->selectRaw('driver_code, COUNT(*) as total')
            ->groupBy('driver_code')
            ->pluck('total', 'driver_code');

        $hubCoords = BatchHub::query()->get()->keyBy('code');

        // Delivery batches are store-driver only (no zone/individual drivers).
        return Driver::storeDrivers()
            ->with(['user', 'activeAssignment.store', 'latestLocation'])
            ->whereHas('user')
            ->get()
            ->map(function (Driver $driver) use ($assignedLoads, $hubCoords) {
                $user = $driver->user;
                $store = $driver->activeAssignment?->store;
                $location = $driver->latestLocation;
                $hub = $store ? ($hubCoords[$store->code] ?? null) : null;

                $lat = $location?->lat ?? $hub?->lat;
                $lng = $location?->lng ?? $hub?->lng;
                $avatar = $user->image ? basename((string) $user->image) : '1.png';
                $available = strtolower((string) $driver->availability) === 'online'
                    && strtolower((string) ($user->status ?? 'Active')) === 'active'
                    && $driver->current_batch_id === null;

                return [
                    'id' => $user->code,
                    'name' => $user->name,
                    'type' => 'store',
                    'zones' => [],
                    'store' => $store?->name,
                    'store_id' => $store?->code,
                    'vehicle' => $driver->plate_number ?? ($driver->vehicle_type ?? 'Vehicle'),
                    'status' => $available ? 'available' : 'offline',
                    'available' => $available,
                    'busy' => $driver->current_batch_id !== null,
                    'load' => (int) ($assignedLoads[$user->code] ?? 0),
                    'distance' => '—',
                    'eta' => '—',
                    'avatar' => $avatar,
                    'lat' => $lat !== null ? (float) $lat : null,
                    'lng' => $lng !== null ? (float) $lng : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function nearbyDrivers(): array
    {
        return Driver::with(['user', 'activeAssignment.store'])
            ->where('availability', 'Online')
            ->whereHas('user')
            ->limit(12)
            ->get()
            ->map(function (Driver $driver) {
                $user = $driver->user;
                $assignment = $driver->activeAssignment;
                $store = $assignment?->store;

                return [
                    'id' => $user->code,
                    'name' => $user->name,
                    'vehicle' => $driver->plate_number,
                    'vehicle_type' => $driver->vehicle_type,
                    'status' => 'available',
                    'load' => 0,
                    'distance' => null,
                    'eta' => null,
                    'recommended' => false,
                    'note' => null,
                    'avatar' => $user->image ?? null,
                    'type' => $assignment?->type ?? 'zone',
                    'store_id' => $store?->code,
                    'store_name' => $store?->name,
                ];
            })
            ->values()
            ->all();
    }
}
