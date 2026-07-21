@php
$isNavbar = true;
$ordersData = $ordersData ?? ['orders' => [], 'nearby_drivers' => []];
$orders = $ordersData['orders'] ?? [];
$order = $order ?? collect($orders)->first(function ($o) use ($orderId) {
    return strcasecmp($o['id'], $orderId) === 0 || strcasecmp($o['id'], urldecode($orderId)) === 0;
});
$order = $order ?? ($orders[0] ?? null);
abort_unless($order, 404);

$fromBatch = !empty($order['batch_id']) || request()->query('from') === 'batch';

$nearbyDrivers = array_values(array_filter($ordersData['nearby_drivers'] ?? [], function ($driver) use ($order) {
    return ($driver['type'] ?? '') === 'store'
        && ($driver['store_id'] ?? '') === ($order['store_id'] ?? '');
}));

$deliveryLabels = [
    'new' => 'New Order',
    'waiting' => 'Waiting for Assignment',
    'assigned' => 'Assigned',
    'accepted' => 'Accepted',
    'ready' => 'Ready for Pickup',
    'out' => 'Out for Delivery',
    'delivered' => 'Delivered',
];
$prepLabels = [
    'not_started' => 'Not Started',
    'packing' => 'Packing',
    'ready' => 'Ready for Pickup',
];
$paymentLabels = [
    'online' => 'Online — Paid',
    'cod' => 'Cash on Delivery',
    'apple' => 'Apple Pay — Paid',
];

$detail = [
    'customer_id' => 'CUS-2048',
    'vip' => true,
    'phone_alt' => '+1 555-0199',
    'avatar' => '6.png',
    'landmark' => 'Blue gate next to the bakery — use side entrance',
    'instructions' => 'Ring bell twice, leave at side door if no answer. Fragile items in the order.',
    'preferred_time' => $order['slot'] . ' ' . ($order['slot_label'] ?? 'Today'),
    'placed_full' => 'Jul 16, 2026 · ' . $order['placed_at'],
    'packages' => '3 Bags · 1 Heavy',
    'distance' => '4.2 km',
    'eta' => '~18 min',
    'weight' => '~8.4 kg',
    'card_last4' => '4321',
    'order_value' => max($order['value'], 142.50),
];

$products = [
    ['name' => 'Organic Bananas', 'category' => 'Fresh Produce', 'sku' => 'PRD-10021', 'qty' => '3', 'unit' => 'bunch', 'price' => 4.50, 'status' => 'Picked', 'icon' => 'bx-leaf'],
    ['name' => 'Whole Milk', 'category' => 'Dairy', 'sku' => 'PRD-20411', 'qty' => '2', 'unit' => 'gallon', 'price' => 7.98, 'status' => 'Picked', 'icon' => 'bx-coffee'],
    ['name' => 'Sourdough Loaf', 'category' => 'Bakery', 'sku' => 'PRD-30802', 'qty' => '1', 'unit' => 'loaf', 'price' => 5.49, 'status' => 'Picked', 'icon' => 'bx-basket'],
    ['name' => 'Free-Range Eggs', 'category' => 'Dairy', 'sku' => 'PRD-20488', 'qty' => '1', 'unit' => 'dozen', 'price' => 6.25, 'status' => 'Picked', 'icon' => 'bx-circle'],
    ['name' => 'Avocados', 'category' => 'Fresh Produce', 'sku' => 'PRD-10055', 'qty' => '4', 'unit' => 'each', 'price' => 7.60, 'status' => 'Picked', 'icon' => 'bx-leaf'],
    ['name' => 'Chicken Breast', 'category' => 'Meat', 'sku' => 'PRD-50110', 'qty' => '2', 'unit' => 'lb', 'price' => 14.80, 'status' => 'Picked', 'icon' => 'bx-dish'],
    ['name' => 'Sparkling Water', 'category' => 'Beverages', 'sku' => 'PRD-70201', 'qty' => '1', 'unit' => 'case', 'price' => 9.99, 'status' => 'Picked', 'icon' => 'bx-droplet'],
    ['name' => 'Pasta Penne', 'category' => 'Pantry', 'sku' => 'PRD-80344', 'qty' => '2', 'unit' => 'box', 'price' => 3.98, 'status' => 'Picked', 'icon' => 'bx-package'],
];

$timelineSteps = [
    ['key' => 'placed', 'label' => 'Order Placed', 'time' => $order['placed_at'], 'done' => true],
    ['key' => 'picking', 'label' => 'Picking Products', 'time' => '08:28 AM', 'done' => true],
    ['key' => 'packing', 'label' => 'Packing', 'time' => '08:45 AM', 'done' => in_array($order['prep'], ['packing', 'ready'])],
    ['key' => 'ready', 'label' => 'Ready for Pickup', 'time' => $order['prep'] === 'ready' ? '09:02 AM' : null, 'done' => $order['prep'] === 'ready', 'current' => $order['prep'] === 'ready' && in_array($order['delivery'], ['new', 'waiting', 'ready'])],
    ['key' => 'assigned', 'label' => 'Driver Assigned', 'time' => $order['driver'] ? '—' : null, 'done' => (bool) $order['driver'] || in_array($order['delivery'], ['assigned', 'accepted', 'out', 'delivered'])],
    ['key' => 'picked_up', 'label' => 'Picked Up', 'time' => null, 'done' => in_array($order['delivery'], ['out', 'delivered'])],
    ['key' => 'out', 'label' => 'Out for Delivery', 'time' => null, 'done' => in_array($order['delivery'], ['out', 'delivered']), 'current' => $order['delivery'] === 'out'],
    ['key' => 'delivered', 'label' => 'Delivered', 'time' => null, 'done' => $order['delivery'] === 'delivered', 'current' => $order['delivery'] === 'delivered'],
];

// Mark current step if none set
$hasCurrent = collect($timelineSteps)->contains(fn ($s) => !empty($s['current']));
if (!$hasCurrent) {
    foreach ($timelineSteps as $i => $step) {
        if (!$step['done']) {
            $timelineSteps[$i]['current'] = true;
            break;
        }
    }
}

$hasDriver = !empty($order['driver']);
$canAssign = !$hasDriver && in_array($order['delivery'], ['new', 'waiting', 'ready', 'accepted']);
$canBroadcast = $canAssign;

$areaCoords = [
    'North Zone' => [40.7829, -73.9654],
    'Central Zone' => [40.7580, -73.9855],
    'West Zone' => [40.7465, -74.0014],
    'East Zone' => [40.7282, -73.9942],
    'South Zone' => [40.7061, -74.0087],
];
$mapLatLng = $areaCoords[$order['area']] ?? [40.7580, -73.9855];
$storeLatLng = [$mapLatLng[0] + 0.012, $mapLatLng[1] - 0.008];
$mapsUrl = 'https://www.openstreetmap.org/?mlat=' . $mapLatLng[0] . '&mlon=' . $mapLatLng[1] . '#map=15/' . $mapLatLng[0] . '/' . $mapLatLng[1];
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Order ' . $order['id'])
@section('page-title', 'Order Details')

@section('vendor-style')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('vendor-script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection

@section('content')
<style>
    /* Keep page content below fixed navbar; prevent leaflet from stacking above cards */
    .order-detail-page {
        position: relative;
        z-index: 0;
    }
    .btn-primary-orange {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
        color: #ffffff !important;
        font-weight: 600;
    }
    .btn-primary-orange:hover {
        background-color: #e06b00 !important;
        border-color: #e06b00 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.2) !important;
    }
    .back-link {
        color: #566a7f;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .back-link:hover { color: #ff7a00; }
    .detail-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
        position: relative;
        z-index: 1;
    }
    .order-detail-cols .detail-card {
        height: auto;
    }
    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .chip-waiting { background: rgba(255,171,0,0.12); color: #ffab00; }
    .chip-waiting .dot { background: #ffab00; }
    .chip-ready { background: rgba(40,199,111,0.12); color: #28c76f; }
    .chip-ready .dot { background: #28c76f; }
    .chip-assigned { background: rgba(105,108,255,0.12); color: #696cff; }
    .chip-assigned .dot { background: #696cff; }
    .chip-accepted { background: rgba(0,207,232,0.12); color: #00cfe8; }
    .chip-accepted .dot { background: #00cfe8; }
    .chip-out { background: rgba(234,84,85,0.12); color: #ea5455; }
    .chip-out .dot { background: #ea5455; }
    .chip-delivered { background: rgba(133,146,163,0.12); color: #8592a3; }
    .chip-delivered .dot { background: #8592a3; }
    .chip-new { background: rgba(59,130,246,0.12); color: #3b82f6; }
    .chip-new .dot { background: #3b82f6; }

    .customer-hero {
        background: linear-gradient(135deg, #fff0e6 0%, #ffe4cc 100%);
        border-radius: 12px;
        padding: 16px;
        border: 1px solid rgba(255,122,0,0.15);
    }
    .vip-badge {
        background: #ff7a00;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        letter-spacing: 0.4px;
    }
    .instructions-box {
        background: rgba(255,171,0,0.1);
        border: 1px solid rgba(255,171,0,0.25);
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 0.88rem;
        color: #566a7f;
    }
    #delivery-map-section {
        position: relative;
        z-index: 0;
        overflow: hidden;
        clear: both;
        margin-top: 1rem;
    }
    #order-delivery-map-wrap {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e0e2e7;
        height: 360px;
        isolation: isolate;
        z-index: 0;
    }
    #order-delivery-map {
        height: 360px;
        width: 100%;
        z-index: 0 !important;
        background: #eef2f7;
    }
    #order-delivery-map-wrap .leaflet-pane,
    #order-delivery-map-wrap .leaflet-top,
    #order-delivery-map-wrap .leaflet-bottom {
        z-index: auto !important;
    }
    #order-delivery-map-wrap .leaflet-map-pane { z-index: 1 !important; }
    #order-delivery-map-wrap .leaflet-tile-pane { z-index: 1 !important; }
    #order-delivery-map-wrap .leaflet-overlay-pane { z-index: 2 !important; }
    #order-delivery-map-wrap .leaflet-marker-pane { z-index: 3 !important; }
    #order-delivery-map-wrap .leaflet-tooltip-pane { z-index: 4 !important; }
    #order-delivery-map-wrap .leaflet-popup-pane { z-index: 5 !important; }
    #order-delivery-map-wrap .leaflet-control { z-index: 6 !important; }
    .leaflet-popup-content-wrapper {
        border-radius: 10px;
        font-family: 'Public Sans', sans-serif;
    }
    .map-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .summary-tile {
        border: 1px solid #e0e2e7;
        border-radius: 10px;
        padding: 14px;
        background: #fff;
        height: 100%;
    }
    .product-thumb {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #fff0e6;
        color: #ff7a00;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    #products-table th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #8592a3;
        font-weight: 700;
    }
    .picked-badge {
        color: #28c76f;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .mgmt-empty {
        text-align: center;
        padding: 20px 12px;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px dashed #e0e2e7;
    }
    .timeline {
        list-style: none;
        padding: 0;
        margin: 0;
        position: relative;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: #e0e2e7;
    }
    .timeline-item {
        position: relative;
        padding-left: 36px;
        padding-bottom: 18px;
    }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-dot {
        position: absolute;
        left: 4px;
        top: 2px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #e0e2e7;
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px #e0e2e7;
    }
    .timeline-item.done .timeline-dot {
        background: #28c76f;
        box-shadow: 0 0 0 1px #28c76f;
    }
    .timeline-item.current .timeline-dot {
        background: #ff7a00;
        box-shadow: 0 0 0 1px #ff7a00;
    }
    .driver-pick-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        padding: 12px 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .driver-pick-card:hover { border-color: #ffb366; }
    .driver-pick-card.selected {
        border-color: #ff7a00;
        background: rgba(255,122,0,0.03);
    }
    .driver-pick-check {
        width: 22px; height: 22px; border-radius: 50%;
        border: 2px solid #d1d5db;
        display: flex; align-items: center; justify-content: center;
        color: transparent; flex-shrink: 0;
    }
    .driver-pick-card.selected .driver-pick-check {
        border-color: #ff7a00; background: #ff7a00; color: #fff;
    }
    .broadcast-target {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
    }
    .broadcast-target.selected {
        border-color: #ff7a00;
        background: rgba(255,122,0,0.03);
    }
    .broadcast-target .form-check-input:checked {
        background-color: #ff7a00;
        border-color: #ff7a00;
    }
    .modal-header-icon {
        width: 44px; height: 44px;
        background: #fff0e6; color: #ff7a00;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
    }
</style>

<div class="order-detail-page">
<!-- Breadcrumb / Back -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.85rem;">
        @if (!empty($fromBatch) && !empty($order['batch_id']))
        <a href="{{ url('/operations/delivery-batches') }}" class="back-link"><i class="bx bx-arrow-back"></i> Back to Batches</a>
        <span class="mx-1">·</span>
        <span>Delivery Batches</span>
        <i class="bx bx-chevron-right"></i>
        <span class="text-body fw-semibold">Batch #{{ $order['batch_id'] }}</span>
        <i class="bx bx-chevron-right"></i>
        <span class="text-body fw-semibold">{{ $order['id'] }}</span>
        @else
        <a href="{{ url('/operations/orders') }}" class="back-link"><i class="bx bx-arrow-back"></i> Back to Orders</a>
        <span class="mx-1">·</span>
        <span>Orders & Delivery</span>
        <i class="bx bx-chevron-right"></i>
        <span class="text-body fw-semibold">Order Details</span>
        @endif
    </div>
    <a href="{{ !empty($fromBatch) ? url('/operations/delivery-batches') : url('/operations/orders') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 8px; border-color: #e0e2e7;">
        <i class="bx bx-list-ul me-1"></i>{{ !empty($fromBatch) ? 'Back to Batches' : 'Back to Queue' }}
    </a>
</div>

<!-- Order header -->
<div class="detail-card mb-4">
    <div class="p-3 p-md-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <i class="bx bx-file" style="font-size: 1.4rem; color: #ff7a00;"></i>
                    <h3 class="mb-0 fw-bold text-body" style="font-size: 1.45rem; font-family: 'Public Sans', sans-serif;">{{ $order['id'] }}</h3>
                    <span class="status-chip chip-{{ $order['delivery'] }}">
                        <span class="dot"></span>{{ $deliveryLabels[$order['delivery']] ?? ucfirst($order['delivery']) }}
                    </span>
                    <span class="status-chip chip-ready">
                        <span class="dot"></span>{{ $prepLabels[$order['prep']] ?? ucfirst($order['prep']) }}
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 0.88rem;">
                    <span><i class="bx bx-time-five"></i> {{ $order['slot'] }} {{ $order['slot_label'] ?? '' }}</span>
                    <span><i class="bx bx-store"></i> {{ $order['store'] }}</span>
                    <span><i class="bx bx-calendar"></i> {{ $detail['placed_full'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 order-detail-cols">
    <!-- Left: Customer & Delivery -->
    <div class="col-12 col-xl-3">
        <div class="detail-card">
            <div class="p-3">
                <div class="customer-hero mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar" style="width: 52px; height: 52px;">
                            <img src="{{ asset('assets/img/avatars/'.$detail['avatar']) }}" class="rounded-circle" alt="">
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-body">{{ $order['customer'] }}</span>
                                @if ($detail['vip'])
                                <span class="vip-badge">VIP</span>
                                @endif
                            </div>
                            <small class="text-muted">{{ $detail['customer_id'] }}</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="text-muted fw-semibold text-uppercase" style="letter-spacing: 0.4px; font-size: 0.7rem;">Mobile</small>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="border-radius: 6px; font-size: 0.75rem;"><i class="bx bx-phone"></i> Call</button>
                    </div>
                    <div class="fw-semibold text-body">{{ $order['phone'] }}</div>
                </div>
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="text-muted fw-semibold text-uppercase" style="letter-spacing: 0.4px; font-size: 0.7rem;">Alternate</small>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="border-radius: 6px; font-size: 0.75rem;"><i class="bx bx-phone"></i> Call</button>
                    </div>
                    <div class="fw-semibold text-body">{{ $detail['phone_alt'] }}</div>
                </div>

                <hr>
                <h6 class="fw-bold text-body mb-2">Delivery Address</h6>
                <p class="mb-1 text-body" style="font-size: 0.9rem;">{{ $order['address'] }}</p>
                <small class="text-muted d-block mb-2"><i class="bx bx-map-pin"></i> {{ $order['area'] }}</small>
                <small class="text-muted d-block mb-3"><strong>Landmark:</strong> {{ $detail['landmark'] }}</small>
                <a href="#delivery-map-section" class="btn btn-sm btn-outline-secondary w-100 mb-3" style="border-radius: 8px; border-color: #e0e2e7; color: #566a7f;">
                    <i class="bx bx-map me-1" style="color: #ff7a00;"></i>View location on map below
                </a>

                <h6 class="fw-bold text-body mb-2">Delivery Instructions</h6>
                <div class="instructions-box mb-3">{{ $detail['instructions'] }}</div>

                <h6 class="fw-bold text-body mb-1">Preferred Time</h6>
                <p class="text-muted mb-3" style="font-size: 0.9rem;"><i class="bx bx-time"></i> {{ $detail['preferred_time'] }}</p>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary" style="border-radius: 8px;" {{ !in_array($order['delivery'], ['out', 'assigned', 'accepted']) ? 'disabled' : '' }}>
                        <i class="bx bx-map me-1"></i>Track Delivery
                    </button>
                    <button type="button" class="btn btn-outline-secondary" style="border-radius: 8px;">
                        <i class="bx bx-printer me-1"></i>Print Slip
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Center: Products -->
    <div class="col-12 col-xl-5">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="summary-tile">
                    <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Order Value</small>
                    <div class="fw-bold mt-1" style="font-size: 1.35rem; color: #28c76f;">${{ number_format($detail['order_value'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-tile">
                    <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Payment</small>
                    <div class="fw-bold text-body mt-1" style="font-size: 0.95rem;">{{ $paymentLabels[$order['payment']] ?? $order['payment'] }}</div>
                    @if ($order['payment'] === 'online')
                    <small class="text-muted">Visa ···· {{ $detail['card_last4'] }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-tile">
                    <small class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Packages</small>
                    <div class="fw-bold text-body mt-1" style="font-size: 0.95rem;">{{ $detail['packages'] }}</div>
                    <small class="text-muted">{{ $order['items'] }} line items</small>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold text-body">Ordered Products</h6>
                <span class="picked-badge"><i class="bx bx-check-circle"></i> All Picked</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="products-table">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $p)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="product-thumb"><i class="bx {{ $p['icon'] }}"></i></div>
                                    <div>
                                        <div class="fw-semibold text-body" style="font-size: 0.88rem;">{{ $p['name'] }}</div>
                                        <small class="text-muted">{{ $p['category'] }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size: 0.85rem;">{{ $p['sku'] }}</td>
                            <td class="fw-semibold">{{ $p['qty'] }}</td>
                            <td class="text-muted">{{ $p['unit'] }}</td>
                            <td class="fw-semibold">${{ number_format($p['price'], 2) }}</td>
                            <td><span class="picked-badge"><i class="bx bx-check"></i> {{ $p['status'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top d-flex align-items-center justify-content-between">
                <span class="text-muted fw-semibold">Order Total</span>
                <span class="fw-bold text-body" style="font-size: 1.15rem;">${{ number_format($detail['order_value'], 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Right: Delivery Management -->
    <div class="col-12 col-xl-4">
        <div class="detail-card mb-3">
            <div class="p-3">
                <h6 class="fw-bold text-body mb-3">Delivery Management</h6>

                @if ($hasDriver)
                <div class="d-flex align-items-center gap-3 p-3 mb-3 rounded-3" style="background: rgba(40,199,111,0.06); border: 1px solid rgba(40,199,111,0.2);">
                    <div class="avatar" style="width: 44px; height: 44px;">
                        <img src="{{ asset('assets/img/avatars/'.$order['driver']['avatar']) }}" class="rounded-circle" alt="">
                    </div>
                    <div>
                        <div class="fw-bold text-body">{{ $order['driver']['name'] }}</div>
                        <small class="text-muted">{{ $order['driver']['id'] }}@if(!empty($order['driver']['eta'])) · ETA {{ $order['driver']['eta'] }}@endif</small>
                    </div>
                </div>
                @else
                <div class="mgmt-empty mb-3">
                    <i class="bx bx-user-x" style="font-size: 2rem; color: #cbd5e1;"></i>
                    <div class="fw-semibold text-body mt-2">No Driver Assigned</div>
                    <small class="text-muted">Order is ready. Assign a driver to proceed.</small>
                </div>
                @endif

                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-primary-orange" id="btn-assign-store" style="border-radius: 8px;">
                        <i class="bx bx-user-plus me-1"></i>{{ $hasDriver ? 'Re-Assign Driver' : 'Assign Store Driver' }}
                    </button>
                    @if ($hasDriver)
                    <button type="button" class="btn btn-outline-danger" id="btn-cancel-assign" style="border-radius: 8px;">
                        <i class="bx bx-x me-1"></i>Cancel Assignment
                    </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" id="btn-broadcast-zone" style="border-radius: 8px; border-color: #ff7a00; color: #ff7a00;" {{ $hasDriver ? 'disabled' : '' }}>
                        <i class="bx bx-broadcast me-1"></i>Broadcast to Zone Drivers
                    </button>
                </div>

                <div class="pt-2 border-top">
                    <small class="text-muted fw-semibold text-uppercase d-block mb-2" style="font-size: 0.7rem; letter-spacing: 0.4px;">Delivery Info</small>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.88rem;">
                        <span class="text-muted">Store</span><span class="fw-semibold text-body">{{ $order['store'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.88rem;">
                        <span class="text-muted">Slot</span><span class="fw-semibold text-body">{{ $order['slot'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.88rem;">
                        <span class="text-muted">Distance</span><span class="fw-semibold text-body">{{ $detail['distance'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.88rem;">
                        <span class="text-muted">Est. Time</span><span class="fw-semibold text-body">{{ $detail['eta'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size: 0.88rem;">
                        <span class="text-muted">Weight</span><span class="fw-semibold text-body">{{ $detail['weight'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="p-3">
                <h6 class="fw-bold text-body mb-3">Activity Timeline</h6>
                <ul class="timeline">
                    @foreach ($timelineSteps as $step)
                    <li class="timeline-item {{ !empty($step['done']) ? 'done' : '' }} {{ !empty($step['current']) ? 'current' : '' }}">
                        <div class="timeline-dot"></div>
                        <div class="fw-semibold text-body" style="font-size: 0.9rem;">{{ $step['label'] }}</div>
                        @if (!empty($step['time']))
                        <small class="text-muted">{{ $step['time'] }}</small>
                        @elseif (!empty($step['current']))
                        <small style="color: #ff7a00;">In progress</small>
                        @else
                        <small class="text-muted">Pending</small>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Location Map (below) -->
<div class="detail-card mb-4" id="delivery-map-section">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="mb-0 fw-bold text-body"><i class="bx bx-map" style="color: #ff7a00;"></i> Delivery Location</h6>
            <small class="text-muted">{{ $order['address'] }} · {{ $order['area'] }}</small>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <small class="text-muted d-flex align-items-center gap-1"><span class="map-legend-dot" style="background:#ff7a00;"></span> Drop-off</small>
            <small class="text-muted d-flex align-items-center gap-1"><span class="map-legend-dot" style="background:#696cff;"></span> Store</small>
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary-orange" style="border-radius: 8px;">
                Open in Maps <i class="bx bx-link-external"></i>
            </a>
        </div>
    </div>
    <div class="p-2 p-md-3">
        <div id="order-delivery-map-wrap">
            <div id="order-delivery-map"></div>
        </div>
    </div>
</div>
</div><!-- /.order-detail-page -->

<!-- Choose Driver Modal -->
<div class="modal fade" id="chooseDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon"><i class="bx bx-user-plus"></i></div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Assign Store Driver</h5>
                        <small class="text-muted">Store drivers for {{ $order['store'] }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="d-flex flex-column gap-2" id="driver-pick-list" style="max-height: 360px; overflow-y: auto;">
                    @foreach ($nearbyDrivers as $i => $driver)
                    <div class="driver-pick-card {{ $i === 0 ? 'selected' : '' }}"
                         data-driver-id="{{ $driver['id'] }}"
                         data-driver-name="{{ $driver['name'] }}"
                         data-avatar="{{ $driver['avatar'] }}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar" style="width: 40px; height: 40px;">
                                <img src="{{ asset('assets/img/avatars/'.$driver['avatar']) }}" class="rounded-circle" alt="">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-body">{{ $driver['name'] }}</span>
                                </div>
                                <small class="text-muted">{{ $driver['id'] }} · {{ $driver['vehicle'] }} · {{ $driver['distance'] }}</small>
                            </div>
                            <div class="driver-pick-check"><i class="bx bx-check" style="font-size: 0.85rem;"></i></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <button type="button" class="btn btn-primary-orange" id="confirm-assign-btn" style="border-radius: 8px;">
                    <i class="bx bx-check me-1"></i>Assign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Broadcast Modal -->
<div class="modal fade" id="broadcastModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon"><i class="bx bx-broadcast"></i></div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Broadcast Order</h5>
                        <small class="text-muted">Send request to nearby zone drivers</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="border rounded-3 p-3 mb-3" style="border-color: #e0e2e7 !important;">
                    <div class="fw-bold text-body mb-1">#{{ $order['id'] }}</div>
                    <div class="text-muted" style="font-size: 0.88rem;">{{ $order['customer'] }} · ${{ number_format($order['value'], 2) }}</div>
                    <div class="text-muted" style="font-size: 0.88rem;"><i class="bx bx-map-pin"></i> {{ $order['area'] }}</div>
                </div>
                <label class="form-label fw-semibold">Broadcast To</label>
                <div class="broadcast-target selected mb-3" data-target="zone">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,122,0,0.12);color:#ff7a00;display:flex;align-items:center;justify-content:center;"><i class="bx bx-map"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Zone Drivers Nearby</div>
                            <small class="text-muted">Available drivers in {{ $order['area'] }}</small>
                        </div>
                        <input class="form-check-input" type="checkbox" checked>
                    </div>
                </div>
                <label class="form-label fw-semibold">Broadcast Duration</label>
                <select class="form-select mb-3" style="border-radius: 8px;">
                    <option>3 Minutes</option>
                    <option selected>5 Minutes</option>
                    <option>10 Minutes</option>
                    <option>15 Minutes</option>
                </select>
                <div class="alert mb-0" style="background: rgba(255,122,0,0.08); border-color: rgba(255,122,0,0.2); color: #566a7f; border-radius: 10px; font-size: 0.85rem;">
                    <i class="bx bx-info-circle" style="color: #ff7a00;"></i>
                    Status stays <strong>Waiting</strong> until a driver accepts — then it becomes <strong>Accepted</strong>.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <button type="button" class="btn btn-primary-orange" id="start-broadcast-btn" style="border-radius: 8px;">
                    <i class="bx bx-broadcast me-1"></i>Start Broadcast
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const assignModal = new bootstrap.Modal(document.getElementById('chooseDriverModal'));
    const broadcastModal = new bootstrap.Modal(document.getElementById('broadcastModal'));

    document.getElementById('btn-assign-store')?.addEventListener('click', () => assignModal.show());
    document.getElementById('btn-broadcast-zone')?.addEventListener('click', () => broadcastModal.show());

    document.getElementById('driver-pick-list')?.addEventListener('click', function (e) {
        const card = e.target.closest('.driver-pick-card');
        if (!card) return;
        this.querySelectorAll('.driver-pick-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
    });

    document.getElementById('confirm-assign-btn')?.addEventListener('click', function () {
        assignModal.hide();
        window.location.reload();
    });

    document.getElementById('start-broadcast-btn')?.addEventListener('click', function () {
        broadcastModal.hide();
        alert('Request sent to nearby zone drivers. Order status remains Waiting until accepted.');
    });

    document.getElementById('btn-cancel-assign')?.addEventListener('click', function () {
        if (confirm('Cancel the current driver assignment?')) {
            window.location.reload();
        }
    });
});
</script>
@endsection

@section('page-script')
<script>
(function () {
    const dropoff = @json($mapLatLng);
    const store = @json($storeLatLng);
    const address = @json($order['address']);
    const storeName = @json($order['store']);
    const area = @json($order['area']);

    function initOrderMap() {
        if (typeof L === 'undefined') {
            setTimeout(initOrderMap, 50);
            return;
        }
        const el = document.getElementById('order-delivery-map');
        if (!el || el.dataset.ready) return;
        el.dataset.ready = '1';

        const map = L.map('order-delivery-map', {
            zoomControl: true,
            scrollWheelZoom: false
        }).setView(dropoff, 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        const dropIcon = L.divIcon({
            className: '',
            html: '<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:#ff7a00;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(255,122,0,0.4);"></div>',
            iconSize: [28, 28],
            iconAnchor: [14, 28]
        });
        const storeIcon = L.divIcon({
            className: '',
            html: '<div style="width:26px;height:26px;border-radius:8px;background:#696cff;border:3px solid #fff;box-shadow:0 2px 8px rgba(105,108,255,0.35);display:flex;align-items:center;justify-content:center;"><span style="color:#fff;font-size:12px;font-weight:700;transform:none;">S</span></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        L.marker(dropoff, { icon: dropIcon })
            .addTo(map)
            .bindPopup('<strong>Drop-off</strong><br>' + address + '<br><span style="color:#8592a3">' + area + '</span>');

        L.marker(store, { icon: storeIcon })
            .addTo(map)
            .bindPopup('<strong>Store</strong><br>' + storeName);

        L.polyline([store, dropoff], {
            color: '#ff7a00',
            weight: 3,
            opacity: 0.75,
            dashArray: '8, 8'
        }).addTo(map);

        map.fitBounds(L.latLngBounds([store, dropoff]).pad(0.35));

        // Leaflet needs invalidateSize when container was hidden/late-rendered
        setTimeout(function () { map.invalidateSize(); }, 200);
        setTimeout(function () { map.invalidateSize(); }, 600);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrderMap);
    } else {
        initOrderMap();
    }
})();
</script>
@endsection
