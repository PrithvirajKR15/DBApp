@php
$isNavbar = false;
$ordersData = include resource_path('views/content/pages/orders-data.php');
$metrics = $ordersData['metrics'];
$stores = $ordersData['stores'];
$areas = $ordersData['areas'];
$slots = $ordersData['slots'];
$orders = $ordersData['orders'];
$nearbyDrivers = $ordersData['nearby_drivers'];

$deliveryLabels = [
    'new' => 'New',
    'waiting' => 'Waiting',
    'assigned' => 'Assigned',
    'accepted' => 'Accepted',
    'ready' => 'Ready Pickup',
    'out' => 'Out Delivery',
    'delivered' => 'Delivered',
];
$prepLabels = [
    'not_started' => 'Not Started',
    'packing' => 'Packing',
    'ready' => 'Ready',
];
$paymentLabels = [
    'online' => 'Online',
    'cod' => 'COD',
    'apple' => 'Apple Pay',
];
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Orders')
@section('page-title', 'Orders & Delivery')

@section('content')
<style>
    .btn-primary-orange {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
        color: #ffffff !important;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }
    .btn-primary-orange:hover,
    .btn-primary-orange:focus,
    .btn-primary-orange:active {
        background-color: #e06b00 !important;
        border-color: #e06b00 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.2) !important;
    }
    .btn-primary-orange:disabled {
        background-color: #ffb366 !important;
        border-color: #ffb366 !important;
        opacity: 0.7;
    }

    .summary-card {
        border-radius: 12px;
        background-color: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
        border: 1px solid #e0e2e7;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    .summary-card.active-metric {
        border-color: #ff7a00;
        box-shadow: 0 0 0 1px rgba(255, 122, 0, 0.25);
    }
    .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .btn-pill {
        border-radius: 20px !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        padding: 6px 16px !important;
        border: 1px solid #e0e2e7 !important;
        background-color: #ffffff !important;
        color: #566a7f !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-pill:hover {
        background-color: #f8f9fa !important;
        border-color: #d1d3e2 !important;
    }
    .btn-pill.active {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(255, 122, 0, 0.2) !important;
    }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        background: #fff0e6;
        color: #ff7a00;
        font-size: 0.78rem;
        font-weight: 600;
    }

    #orders-table th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #8592a3;
        letter-spacing: 0.4px;
        border-bottom: 1px solid #e0e2e7;
        white-space: nowrap;
        vertical-align: middle;
    }
    #orders-table td {
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
        font-size: 0.88rem;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(255, 122, 0, 0.02) !important;
    }
    .table-hover tbody tr.selected-row {
        background-color: rgba(255, 122, 0, 0.06) !important;
    }

    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .status-chip .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }
    .chip-new { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
    .chip-new .dot { background: #3b82f6; }
    .chip-waiting { background: rgba(255, 171, 0, 0.12); color: #ffab00; }
    .chip-waiting .dot { background: #ffab00; }
    .chip-assigned { background: rgba(105, 108, 255, 0.12); color: #696cff; }
    .chip-assigned .dot { background: #696cff; }
    .chip-accepted { background: rgba(0, 207, 232, 0.12); color: #00cfe8; }
    .chip-accepted .dot { background: #00cfe8; }
    .chip-ready { background: rgba(40, 199, 111, 0.12); color: #28c76f; }
    .chip-ready .dot { background: #28c76f; }
    .chip-out { background: rgba(234, 84, 85, 0.12); color: #ea5455; }
    .chip-out .dot { background: #ea5455; }
    .chip-delivered { background: rgba(133, 146, 163, 0.12); color: #8592a3; }
    .chip-delivered .dot { background: #8592a3; }

    .prep-wrap { min-width: 90px; }
    .prep-bar {
        height: 4px;
        border-radius: 4px;
        background: #eef0f3;
        margin-top: 4px;
        overflow: hidden;
    }
    .prep-bar > span {
        display: block;
        height: 100%;
        border-radius: 4px;
    }
    .prep-ready { color: #28c76f; font-weight: 600; font-size: 0.78rem; }
    .prep-packing { color: #3b82f6; font-weight: 600; font-size: 0.78rem; }
    .prep-not_started { color: #8592a3; font-weight: 600; font-size: 0.78rem; }

    .payment-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #566a7f;
    }

    .urgent-tag {
        color: #ea5455;
        font-size: 0.72rem;
        font-weight: 700;
        margin-top: 2px;
        display: inline-block;
    }

    .bulk-bar {
        display: none;
        border-radius: 12px;
        background: #fff0e6;
        border: 1px solid rgba(255, 122, 0, 0.25);
    }
    .bulk-bar.visible { display: flex; }

    .driver-pick-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
    }
    .driver-pick-card:hover { border-color: #ffb366; }
    .driver-pick-card.selected {
        border-color: #ff7a00;
        background: rgba(255, 122, 0, 0.03);
        box-shadow: 0 0 0 1px rgba(255, 122, 0, 0.2);
    }
    .driver-pick-card .rec-badge {
        background: #ff7a00;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        padding: 3px 8px;
        border-radius: 4px;
    }
    .driver-pick-check {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 2px solid #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: transparent;
        transition: all 0.15s ease;
    }
    .driver-pick-card.selected .driver-pick-check {
        border-color: #ff7a00;
        background: #ff7a00;
        color: #fff;
    }

    .broadcast-target {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .broadcast-target:hover { border-color: #ffb366; }
    .broadcast-target.selected {
        border-color: #ff7a00;
        background: rgba(255, 122, 0, 0.03);
    }
    .broadcast-target .form-check-input:checked {
        background-color: #ff7a00;
        border-color: #ff7a00;
    }

    .modal-header-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background-color: #fff0e6;
        color: #ff7a00;
        border-radius: 8px;
        font-size: 1.3rem;
    }
</style>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h3 class="mb-1 fw-bold text-body" style="font-size: 1.6rem; font-family: 'Public Sans', sans-serif;">Active Operations</h3>
        <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.85rem;">
            <i class="bx bx-refresh" style="font-size: 1rem;"></i>
            <span>Last synced: <span class="fw-semibold text-body" id="last-synced">Just now</span></span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" id="btn-bulk-broadcast" style="border-radius: 8px; display: none;">
            <i class="bx bx-broadcast"></i>
            <span>Broadcast Selected</span>
        </button>
        <a href="{{ url('/operations/delivery-batches/generate') }}" class="btn btn-primary-orange d-flex align-items-center gap-2" style="padding: 10px 18px; border-radius: 8px;">
            <i class="bx bx-plus" style="font-size: 1.15rem;"></i>
            <span>Create Delivery Batches</span>
        </a>
    </div>
</div>

<!-- Status Metric Cards -->
<div class="row g-3 mb-4" id="metric-cards">
    @php
    $metricDefs = [
        ['key' => 'waiting', 'label' => 'Waiting Assign', 'icon' => 'bx-time-five', 'bg' => 'rgba(255,171,0,0.12)', 'color' => '#ffab00'],
        ['key' => 'assigned', 'label' => 'Assigned', 'icon' => 'bx-user-check', 'bg' => 'rgba(105,108,255,0.12)', 'color' => '#696cff'],
        ['key' => 'accepted', 'label' => 'Accepted', 'icon' => 'bx-check', 'bg' => 'rgba(0,207,232,0.12)', 'color' => '#00cfe8'],
        ['key' => 'ready', 'label' => 'Ready Pickup', 'icon' => 'bx-package', 'bg' => 'rgba(40,199,111,0.12)', 'color' => '#28c76f'],
        ['key' => 'out', 'label' => 'Out Delivery', 'icon' => 'bx-trip', 'bg' => 'rgba(234,84,85,0.12)', 'color' => '#ea5455'],
        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'bx-check-double', 'bg' => 'rgba(133,146,163,0.12)', 'color' => '#8592a3'],
    ];
    @endphp
    @foreach ($metricDefs as $m)
    <div class="col-6 col-md-4 col-xl-3 col-xxl">
        <div class="summary-card card shadow-none h-100 metric-filter" data-delivery="{{ $m['key'] }}">
            <div class="card-body d-flex align-items-center gap-2 p-3">
                <div class="metric-icon" style="background: {{ $m['bg'] }}; color: {{ $m['color'] }};">
                    <i class="bx {{ $m['icon'] }}"></i>
                </div>
                <div class="min-w-0">
                    <small class="text-muted d-block text-truncate" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">{{ $m['label'] }}</small>
                    <h4 class="mb-0 fw-bold text-body" style="font-size: 1.35rem; line-height: 1.2;">
                        @if ($m['key'] === 'waiting')
                            {{ $metrics['waiting'] + $metrics['new'] }}
                        @else
                            {{ $metrics[$m['key']] }}
                        @endif
                    </h4>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Top Filters -->
<div class="card shadow-none border mb-3" style="border-radius: 12px;">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-lg-3">
                <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #e0e2e7 !important; border-radius: 8px !important;">
                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent ps-1" id="search-orders" placeholder="Order, name, phone..." style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="filter-date" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="today" selected>Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="7d">Last 7 days</option>
                    <option value="custom">Custom range</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="filter-store" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                    <option value="{{ $store['id'] }}">{{ $store['name'] }} ({{ $store['count'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="filter-delivery" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Delivery Status</option>
                    @foreach ($deliveryLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="filter-prep" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Prep Status</option>
                    @foreach ($prepLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-1">
                <button type="button" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1" id="toggle-more-filters" style="border-radius: 8px; height: 38px; border-color: #e0e2e7; color: #566a7f; font-size: 0.85rem;">
                    <i class="bx bx-slider-alt"></i>
                    <span class="d-none d-xl-inline">More</span>
                </button>
            </div>
        </div>

        <div class="row g-2 mt-2 d-none" id="more-filters-row">
            <div class="col-6 col-md-3">
                <select class="form-select" id="filter-payment" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Payment Types</option>
                    @foreach ($paymentLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select" id="filter-area" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Delivery Areas</option>
                    @foreach ($areas as $area)
                    <option value="{{ $area }}">{{ $area }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select" id="filter-slot" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Delivery Slots</option>
                    @foreach ($slots as $slot)
                    <option value="{{ $slot }}">{{ $slot }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select" id="filter-driver-type" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Driver Types</option>
                    <option value="store">Store Drivers</option>
                    <option value="zone">Zone Drivers</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Bulk selection bar -->
<div class="bulk-bar align-items-center justify-content-between gap-3 px-3 py-2 mb-3" id="bulk-bar">
    <div class="d-flex align-items-center gap-2">
        <span class="filter-chip"><i class="bx bx-check"></i> <span id="selected-count">0</span> selected</span>
        <span class="text-muted" style="font-size: 0.85rem;">Broadcast to nearby zone drivers</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-selection" style="border-radius: 6px;">Clear</button>
        <button type="button" class="btn btn-sm btn-primary-orange" id="bulk-broadcast-btn" style="border-radius: 6px;">
            <i class="bx bx-broadcast me-1"></i>Broadcast
        </button>
    </div>
</div>

<!-- Table toolbar -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted" style="font-size: 0.88rem;"><span class="fw-semibold text-body" id="orders-count">{{ count($orders) }}</span> orders</span>
        <div class="d-flex align-items-center gap-2 flex-wrap" id="view-tabs">
            <button type="button" class="btn btn-pill view-tab active" data-view="all">All</button>
            <button type="button" class="btn btn-pill view-tab" data-view="urgent">Urgent</button>
            <button type="button" class="btn btn-pill view-tab" data-view="unassigned">Unassigned</button>
            <button type="button" class="btn btn-pill view-tab" data-view="in_transit">In Transit</button>
            <button type="button" class="btn btn-pill view-tab" data-view="delivered">Delivered</button>
        </div>
    </div>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle border" type="button" data-bs-toggle="dropdown" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 14px; font-size: 0.88rem; height: 38px; color: #566a7f;">
            Sort: Slot (Earliest)
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item active" href="javascript:void(0);">Slot (Earliest)</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);">Slot (Latest)</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);">Value (High–Low)</a></li>
            <li><a class="dropdown-item" href="javascript:void(0);">Newest First</a></li>
        </ul>
    </div>
</div>

<!-- Orders Table -->
<div class="card shadow-none border" style="border-radius: 12px; overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="orders-table">
            <thead>
                <tr class="table-light">
                    <th style="width: 40px;">
                        <input type="checkbox" class="form-check-input" id="select-all-orders" style="cursor: pointer;">
                    </th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Store</th>
                    <th>Slot</th>
                    <th>Value</th>
                    <th>Payment</th>
                    <th>Prep</th>
                    <th>Delivery</th>
                    <th>Driver</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
                @foreach ($orders as $order)
                @php
                    $search = strtolower($order['id'].' '.$order['customer'].' '.$order['phone'].' '.$order['area'].' '.$order['store']);
                    $views = implode(',', $order['view']);
                    $driverType = $order['driver'] ? 'assigned' : 'unassigned';
                @endphp
                <tr class="order-row"
                    data-search="{{ $search }}"
                    data-store="{{ $order['store_id'] }}"
                    data-store-name="{{ $order['store'] }}"
                    data-delivery="{{ $order['delivery'] }}"
                    data-prep="{{ $order['prep'] }}"
                    data-payment="{{ $order['payment'] }}"
                    data-area="{{ $order['area'] }}"
                    data-slot="{{ $order['slot'] }}"
                    data-views="{{ $views }}"
                    data-driver-type="{{ $driverType }}"
                    data-request-pending="{{ !empty($order['request_pending']) ? '1' : '0' }}"
                    data-order-id="{{ $order['id'] }}"
                    data-customer="{{ $order['customer'] }}"
                    data-value="{{ $order['value'] }}"
                    data-area-label="{{ $order['area'] }}">
                    <td>
                        <input type="checkbox" class="form-check-input order-check" style="cursor: pointer;">
                    </td>
                    <td>
                        <div class="fw-semibold text-body">{{ $order['id'] }}</div>
                        <small class="text-muted">{{ $order['placed_at'] }}</small>
                    </td>
                    <td>
                        <div class="fw-semibold text-body">{{ $order['customer'] }}</div>
                        <small class="text-muted d-block">{{ $order['phone'] }}</small>
                        <small class="text-muted"><i class="bx bx-map-pin" style="font-size: 0.85rem;"></i> {{ $order['area'] }}</small>
                    </td>
                    <td><span class="text-body">{{ $order['store'] }}</span></td>
                    <td>
                        <div class="fw-semibold text-body" style="font-size: 0.85rem;">{{ $order['slot'] }}</div>
                        <small class="text-muted">{{ $order['slot_label'] }}</small>
                        @if ($order['urgent'])
                        <div class="urgent-tag"><i class="bx bx-error-circle"></i> Urgent!</div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold text-body">${{ number_format($order['value'], 2) }}</div>
                        <small class="text-muted">{{ $order['items'] }} items</small>
                    </td>
                    <td>
                        <span class="payment-chip">
                            @if ($order['payment'] === 'online')
                                <i class="bx bx-credit-card"></i>
                            @elseif ($order['payment'] === 'cod')
                                <i class="bx bx-money"></i>
                            @else
                                <i class="bx bxl-apple"></i>
                            @endif
                            {{ $paymentLabels[$order['payment']] }}
                        </span>
                    </td>
                    <td>
                        <div class="prep-wrap">
                            <div class="prep-{{ $order['prep'] }}">{{ $prepLabels[$order['prep']] }}</div>
                            <div class="prep-bar">
                                <span style="width: {{ $order['prep_pct'] }}%; background: {{ $order['prep'] === 'ready' ? '#28c76f' : ($order['prep'] === 'packing' ? '#3b82f6' : '#cbd5e1') }};"></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="status-chip chip-{{ $order['delivery'] }}">
                            <span class="dot"></span>{{ $deliveryLabels[$order['delivery']] }}
                        </span>
                    </td>
                    <td>
                        @if ($order['driver'])
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-xs" style="width: 28px; height: 28px;">
                                <img src="{{ asset('assets/img/avatars/'.$order['driver']['avatar']) }}" alt="" class="rounded-circle">
                            </div>
                            <div>
                                <div class="fw-semibold text-body" style="font-size: 0.85rem;">{{ $order['driver']['name'] }}</div>
                                <small class="text-muted">{{ $order['driver']['id'] }}@if($order['driver']['eta']) · ETA {{ $order['driver']['eta'] }}@endif</small>
                            </div>
                        </div>
                        @elseif (!empty($order['request_pending']))
                        <span class="text-muted" style="font-size: 0.85rem;"><i class="bx bx-broadcast me-1" style="color: #ff7a00;"></i>Request pending</span>
                        @else
                        <span class="text-muted" style="font-size: 0.85rem;"><i class="bx bx-user-x me-1"></i>Unassigned</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex align-items-center justify-content-end gap-1 flex-nowrap">
                            <a href="{{ $order['delivery'] === 'delivered' ? url('/operations/orders/'.$order['id'].'/completed') : url('/operations/orders/'.$order['id']) }}" class="btn btn-sm btn-outline-secondary btn-details" style="border-radius: 6px; font-size: 0.78rem;">Details</a>
                            @if ($order['delivery'] === 'delivered')
                            <a href="{{ url('/operations/orders/'.$order['id'].'/completed') }}" class="btn btn-sm btn-outline-success" style="border-radius: 6px; font-size: 0.78rem;">
                                <i class="bx bx-check me-1"></i>Receipt
                            </a>
                            @elseif (!$order['driver'] && in_array($order['delivery'], ['new', 'waiting', 'ready', 'accepted']))
                            <button type="button" class="btn btn-sm btn-primary-orange btn-assign" style="border-radius: 6px; font-size: 0.78rem;">Assign</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-broadcast-one" style="border-radius: 6px; font-size: 0.78rem;" title="Broadcast to zone drivers">
                                <i class="bx bx-broadcast"></i>
                            </button>
                            @elseif ($order['delivery'] === 'out')
                            <a href="{{ url('/live-map?driver=' . urlencode($order['driver']['name'])) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; font-size: 0.78rem;">
                                <span class="d-inline-block rounded-circle bg-danger me-1" style="width: 6px; height: 6px;"></span>Live
                            </a>
                            @elseif ($order['driver'])
                            <a href="{{ url('/live-map?driver=' . urlencode($order['driver']['name'])) }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; font-size: 0.78rem;">Track</a>
                            @endif
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if ($order['driver'] && $order['delivery'] !== 'delivered')
                                        <li><a class="dropdown-item btn-assign" href="javascript:void(0);"><i class="bx bx-user-plus me-2"></i>Reassign Driver</a></li>
                                        <li><a class="dropdown-item btn-broadcast-one" href="javascript:void(0);"><i class="bx bx-broadcast me-2"></i>Broadcast</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    @elseif (!$order['driver'] && $order['delivery'] !== 'delivered' && !in_array($order['delivery'], ['new', 'waiting', 'ready', 'accepted']))
                                        <li><a class="dropdown-item btn-assign" href="javascript:void(0);"><i class="bx bx-user-plus me-2"></i>Assign Driver</a></li>
                                        <li><a class="dropdown-item btn-broadcast-one" href="javascript:void(0);"><i class="bx bx-broadcast me-2"></i>Broadcast</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    @endif
                                    <li><a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-printer me-2"></i>Print Slip</a></li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Choose Driver Modal -->
<div class="modal fade" id="chooseDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon"><i class="bx bx-user-plus"></i></div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Assign Store Driver</h5>
                        <small class="text-muted" id="assign-modal-subtitle">Select a store driver for this order</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <input type="hidden" id="assign-order-id">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <small class="text-muted fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Store Drivers (<span id="driver-list-count">0</span>)</small>
                    <div class="input-group input-group-merge border rounded" style="width: 240px; border-radius: 8px !important; border-color: #e0e2e7 !important;">
                        <span class="input-group-text border-0 bg-transparent"><i class="bx bx-search text-muted"></i></span>
                        <input type="text" class="form-control border-0 bg-transparent" id="search-drivers-modal" placeholder="Search drivers..." style="box-shadow: none; font-size: 0.88rem;">
                    </div>
                </div>
                <div class="d-flex flex-column gap-2" id="driver-pick-list" style="max-height: 340px; overflow-y: auto;">
                    @foreach ($nearbyDrivers as $driver)
                    <div class="driver-pick-card"
                         data-driver-id="{{ $driver['id'] }}"
                         data-driver-name="{{ $driver['name'] }}"
                         data-avatar="{{ $driver['avatar'] }}"
                         data-driver-type="{{ $driver['type'] }}"
                         data-store-id="{{ $driver['store_id'] ?? '' }}"
                         data-search="{{ strtolower($driver['name'].' '.$driver['id'].' '.$driver['vehicle']) }}">
                        <div class="d-flex align-items-start gap-3">
                            <div class="avatar" style="width: 44px; height: 44px;">
                                <img src="{{ asset('assets/img/avatars/'.$driver['avatar']) }}" class="rounded-circle" alt="">
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold text-body">{{ $driver['name'] }}</span>
                                    @if ($driver['recommended'])
                                    <span class="rec-badge"><i class="bx bxs-star" style="font-size: 0.7rem;"></i> RECOMMENDED</span>
                                    @endif
                                </div>
                                <small class="text-muted">ID: {{ $driver['id'] }} · <i class="bx bx-car"></i> {{ $driver['vehicle'] }}</small>
                                <div class="row g-2 mt-2" style="font-size: 0.82rem;">
                                    <div class="col-4">
                                        <div class="text-muted" style="font-size: 0.7rem;">Status</div>
                                        <div class="fw-semibold" style="color: {{ $driver['status'] === 'available' ? '#ff7a00' : '#3b82f6' }};">
                                            {{ $driver['status'] === 'available' ? 'Available' : 'On Delivery' }}
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted" style="font-size: 0.7rem;">Current Load</div>
                                        <div class="fw-semibold text-body">{{ $driver['load'] }} Order{{ $driver['load'] === 1 ? '' : 's' }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted" style="font-size: 0.7rem;">Distance</div>
                                        <div class="fw-semibold text-body">{{ $driver['distance'] }} ({{ $driver['eta'] }})</div>
                                    </div>
                                </div>
                                @if ($driver['note'])
                                <div class="mt-2 text-muted" style="font-size: 0.78rem; color: #ff7a00 !important;">
                                    <i class="bx bx-info-circle"></i> {{ $driver['note'] }}
                                </div>
                                @endif
                            </div>
                            <div class="driver-pick-check"><i class="bx bx-check" style="font-size: 0.85rem;"></i></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="text-muted text-center py-4 d-none" id="driver-empty-state">
                    <i class="bx bx-user-x d-block mb-2" style="font-size: 1.5rem;"></i>
                    No store drivers are available.
                </div>
                <div class="mt-3">
                    <label class="form-label fw-semibold text-body" style="font-size: 0.88rem;">Dispatch Instructions (Optional)</label>
                    <input type="text" class="form-control" id="dispatch-notes" placeholder="e.g. Prefer rear entrance, fragile items..." style="border-radius: 8px;">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
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
                        <small class="text-muted">Send to nearby zone drivers</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="border rounded-3 p-3 mb-3" style="border-color: #e0e2e7 !important; position: relative;">
                    <span class="status-chip chip-ready position-absolute" style="top: 12px; right: 12px;"><span class="dot"></span>Ready for Pickup</span>
                    <div class="fw-bold text-body mb-2" id="broadcast-order-id">#ORD-0000</div>
                    <div class="row g-2" style="font-size: 0.88rem;">
                        <div class="col-6">
                            <div class="text-muted" style="font-size: 0.72rem;">Customer</div>
                            <div class="fw-semibold" id="broadcast-customer">—</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size: 0.72rem;">Value</div>
                            <div class="fw-semibold" id="broadcast-value">—</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted" style="font-size: 0.72rem;">Delivery Area</div>
                            <div class="fw-semibold"><i class="bx bx-map-pin text-muted"></i> <span id="broadcast-area">—</span></div>
                        </div>
                    </div>
                </div>

                <label class="form-label fw-semibold text-body mb-2">Broadcast To</label>
                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="broadcast-target selected" data-target="zone">
                        <div class="d-flex align-items-center gap-3">
                            <div class="metric-icon" style="background: rgba(255,122,0,0.12); color: #ff7a00;"><i class="bx bx-map"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-body">Zone Drivers Nearby</div>
                                <small class="text-muted">~12 available zone drivers near this order</small>
                            </div>
                            <input class="form-check-input" type="checkbox" checked style="width: 1.1em; height: 1.1em;">
                        </div>
                    </div>
                </div>

                <label class="form-label fw-semibold text-body">Broadcast Duration</label>
                <select class="form-select mb-3" id="broadcast-duration" style="border-radius: 8px;">
                    <option value="3">3 Minutes</option>
                    <option value="5" selected>5 Minutes</option>
                    <option value="10">10 Minutes</option>
                    <option value="15">15 Minutes</option>
                </select>

                <div class="alert alert-primary d-flex align-items-start gap-2 mb-0" style="background: rgba(255,122,0,0.08); border-color: rgba(255,122,0,0.2); color: #566a7f; border-radius: 10px;">
                    <i class="bx bx-info-circle mt-1" style="color: #ff7a00;"></i>
                    <span style="font-size: 0.85rem;">Broadcast sends a delivery request to nearby zone drivers (like assigning, but to multiple at once). The order keeps its current status (e.g. Waiting) until a driver accepts — then it becomes Accepted.</span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
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
    const chooseDriverModal = new bootstrap.Modal(document.getElementById('chooseDriverModal'));
    const broadcastModal = new bootstrap.Modal(document.getElementById('broadcastModal'));
    let activeView = 'all';
    let activeMetricDelivery = '';
    let currentAssignRow = null;
    let currentAssignStoreId = null;

    function filterDriversForAssign(storeId) {
        const q = (document.getElementById('search-drivers-modal').value || '').toLowerCase();
        let visible = 0;
        let firstVisible = null;

        document.querySelectorAll('.driver-pick-card').forEach(card => {
            const isStoreDriver = card.dataset.driverType === 'store';
            const matchesStore = card.dataset.storeId === storeId;
            const matchSearch = !q || (card.dataset.search || '').includes(q);
            const show = isStoreDriver && matchesStore && matchSearch;

            card.style.display = show ? '' : 'none';
            card.classList.remove('selected');
            if (show) {
                visible++;
                if (!firstVisible) firstVisible = card;
            }
        });

        if (firstVisible) {
            firstVisible.classList.add('selected');
        }

        document.getElementById('driver-list-count').textContent = visible;
        document.getElementById('driver-empty-state').classList.toggle('d-none', visible > 0);
        document.getElementById('confirm-assign-btn').disabled = visible === 0;
    }

    // More filters toggle
    document.getElementById('toggle-more-filters').addEventListener('click', function () {
        document.getElementById('more-filters-row').classList.toggle('d-none');
    });

    // View tabs
    document.querySelectorAll('.view-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.view-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeView = this.dataset.view;
            filterOrders();
        });
    });

    // Metric card click → filter by delivery status
    document.querySelectorAll('.metric-filter').forEach(card => {
        card.addEventListener('click', function () {
            const key = this.dataset.delivery;
            if (activeMetricDelivery === key) {
                activeMetricDelivery = '';
                this.classList.remove('active-metric');
                document.getElementById('filter-delivery').value = '';
            } else {
                document.querySelectorAll('.metric-filter').forEach(c => c.classList.remove('active-metric'));
                this.classList.add('active-metric');
                activeMetricDelivery = key;
                document.getElementById('filter-delivery').value = key;
            }
            filterOrders();
        });
    });

    // Filter inputs
    ['search-orders', 'filter-date', 'filter-store', 'filter-delivery', 'filter-prep', 'filter-payment', 'filter-area', 'filter-slot', 'filter-driver-type'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', filterOrders);
        if (el) el.addEventListener('change', filterOrders);
    });

    const filterDeliveryEl = document.getElementById('filter-delivery');
    if (filterDeliveryEl) {
        filterDeliveryEl.addEventListener('change', function () {
            const val = this.value;
            activeMetricDelivery = val;
            document.querySelectorAll('.metric-filter').forEach(c => {
                if (c.dataset.delivery === val) {
                    c.classList.add('active-metric');
                } else {
                    c.classList.remove('active-metric');
                }
            });
        });
    }

    function filterOrders() {
        const q = (document.getElementById('search-orders').value || '').toLowerCase();
        const store = document.getElementById('filter-store').value;
        const delivery = document.getElementById('filter-delivery').value || activeMetricDelivery;
        const prep = document.getElementById('filter-prep').value;
        const payment = document.getElementById('filter-payment').value;
        const area = document.getElementById('filter-area').value;
        const slot = document.getElementById('filter-slot').value;
        const driverType = document.getElementById('filter-driver-type').value;
        let visible = 0;

        document.querySelectorAll('.order-row').forEach(row => {
            const views = (row.dataset.views || '').split(',').filter(Boolean);
            const matchSearch = !q || (row.dataset.search || '').includes(q);
            const matchStore = !store || row.dataset.store === store;
            const matchDelivery = !delivery || 
                (delivery === 'waiting' 
                    ? (row.dataset.delivery === 'waiting' || row.dataset.delivery === 'new') 
                    : row.dataset.delivery === delivery);
            const matchPrep = !prep || row.dataset.prep === prep;
            const matchPayment = !payment || row.dataset.payment === payment;
            const matchArea = !area || row.dataset.area === area;
            const matchSlot = !slot || row.dataset.slot === slot;
            const matchDriverType = !driverType ||
                (driverType === 'unassigned' && row.dataset.driverType === 'unassigned') ||
                (driverType !== 'unassigned' && row.dataset.driverType === 'assigned');
            const matchView = activeView === 'all'
                || (activeView === 'delivered' && row.dataset.delivery === 'delivered')
                || (activeView === 'in_transit' && (row.dataset.delivery === 'out' || views.includes('in_transit')))
                || views.includes(activeView);

            const show = matchSearch && matchStore && matchDelivery && matchPrep && matchPayment && matchArea && matchSlot && matchDriverType && matchView;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('orders-count').textContent = visible;
    }

    // Selection
    function updateSelectionUI() {
        const checked = document.querySelectorAll('.order-check:checked');
        const count = checked.length;
        const bar = document.getElementById('bulk-bar');
        const bulkBtn = document.getElementById('btn-bulk-broadcast');
        document.getElementById('selected-count').textContent = count;
        if (count > 0) {
            bar.classList.add('visible');
            bulkBtn.style.display = 'inline-flex';
        } else {
            bar.classList.remove('visible');
            bulkBtn.style.display = 'none';
        }
        document.querySelectorAll('.order-row').forEach(row => {
            row.classList.toggle('selected-row', row.querySelector('.order-check')?.checked);
        });
    }

    document.getElementById('select-all-orders').addEventListener('change', function () {
        document.querySelectorAll('.order-row').forEach(row => {
            if (row.style.display === 'none') return;
            const cb = row.querySelector('.order-check');
            if (cb) cb.checked = this.checked;
        });
        updateSelectionUI();
    });

    document.getElementById('orders-tbody').addEventListener('change', function (e) {
        if (e.target.classList.contains('order-check')) updateSelectionUI();
    });

    document.getElementById('clear-selection').addEventListener('click', function () {
        document.querySelectorAll('.order-check').forEach(cb => cb.checked = false);
        document.getElementById('select-all-orders').checked = false;
        updateSelectionUI();
    });

    // Assign driver
    document.getElementById('orders-tbody').addEventListener('click', function (e) {
        const assignBtn = e.target.closest('.btn-assign');
        const broadcastBtn = e.target.closest('.btn-broadcast-one');
        const row = e.target.closest('.order-row');
        if (!row) return;

        if (assignBtn) {
            e.preventDefault();
            currentAssignRow = row;
            currentAssignStoreId = row.dataset.store;
            document.getElementById('assign-order-id').value = row.dataset.orderId;
            document.getElementById('search-drivers-modal').value = '';
            document.getElementById('assign-modal-subtitle').textContent =
                'Select a store driver for ' + (row.dataset.storeName || 'this order');
            filterDriversForAssign(currentAssignStoreId);
            chooseDriverModal.show();
        }
        if (broadcastBtn) {
            e.preventDefault();
            openBroadcast(row);
        }
    });

    document.getElementById('bulk-broadcast-btn').addEventListener('click', openBulkBroadcast);
    document.getElementById('btn-bulk-broadcast').addEventListener('click', openBulkBroadcast);

    function openBulkBroadcast() {
        const first = document.querySelector('.order-check:checked')?.closest('.order-row');
        if (first) openBroadcast(first, true);
    }

    function openBroadcast(row, isBulk = false) {
        const count = document.querySelectorAll('.order-check:checked').length;
        document.getElementById('broadcast-order-id').textContent = isBulk && count > 1
            ? count + ' selected orders'
            : '#' + row.dataset.orderId;
        document.getElementById('broadcast-customer').textContent = isBulk && count > 1 ? 'Multiple customers' : row.dataset.customer;
        document.getElementById('broadcast-value').textContent = isBulk && count > 1 ? '—' : '$' + parseFloat(row.dataset.value).toFixed(2);
        document.getElementById('broadcast-area').textContent = row.dataset.areaLabel;
        broadcastModal.show();
    }

    // Driver pick cards
    document.getElementById('driver-pick-list').addEventListener('click', function (e) {
        const card = e.target.closest('.driver-pick-card');
        if (!card) return;
        document.querySelectorAll('.driver-pick-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
    });

    document.getElementById('search-drivers-modal').addEventListener('input', function () {
        if (currentAssignStoreId) {
            filterDriversForAssign(currentAssignStoreId);
        }
    });

    document.getElementById('confirm-assign-btn').addEventListener('click', function () {
        const selected = document.querySelector('.driver-pick-card.selected');
        if (!selected || !currentAssignRow) return;

        const wasRequest = currentAssignRow.dataset.requestPending === '1';
        const name = selected.dataset.driverName;
        const id = selected.dataset.driverId;
        const avatar = selected.dataset.avatar;
        const driverCell = currentAssignRow.children[9];
        const deliveryCell = currentAssignRow.children[8];
        const actionsCell = currentAssignRow.children[10];

        driverCell.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-xs" style="width: 28px; height: 28px;">
                    <img src="{{ asset('assets/img/avatars/') }}/${avatar}" alt="" class="rounded-circle">
                </div>
                <div>
                    <div class="fw-semibold text-body" style="font-size: 0.85rem;">${name}</div>
                    <small class="text-muted">${id}</small>
                </div>
            </div>`;

        // Request/broadcast accept → Accepted; direct assign → Assigned
        if (wasRequest) {
            deliveryCell.innerHTML = `<span class="status-chip chip-accepted"><span class="dot"></span>Accepted</span>`;
            currentAssignRow.dataset.delivery = 'accepted';
        } else {
            deliveryCell.innerHTML = `<span class="status-chip chip-assigned"><span class="dot"></span>Assigned</span>`;
            currentAssignRow.dataset.delivery = 'assigned';
        }
        currentAssignRow.dataset.requestPending = '0';
        currentAssignRow.dataset.driverType = 'assigned';
            actionsCell.innerHTML = `
                <div class="d-flex align-items-center justify-content-end gap-1 flex-nowrap">
                    <a href="/operations/orders/${currentAssignRow.dataset.orderId}" class="btn btn-sm btn-outline-secondary btn-details" style="border-radius: 6px; font-size: 0.78rem;">Details</a>
                    <a href="/live-map?driver=${encodeURIComponent(name)}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; font-size: 0.78rem;">Track</a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item btn-assign" href="javascript:void(0);"><i class="bx bx-user-plus me-2"></i>Reassign Driver</a></li>
                            <li><a class="dropdown-item btn-broadcast-one" href="javascript:void(0);"><i class="bx bx-broadcast me-2"></i>Broadcast</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-printer me-2"></i>Print Slip</a></li>
                        </ul>
                    </div>
                </div>`;

        chooseDriverModal.hide();
        filterOrders();
    });

    // Broadcast targets toggle
    document.querySelectorAll('.broadcast-target').forEach(card => {
        card.addEventListener('click', function (e) {
            if (e.target.classList.contains('form-check-input')) return;
            const cb = this.querySelector('.form-check-input');
            cb.checked = !cb.checked;
            this.classList.toggle('selected', cb.checked);
        });
        card.querySelector('.form-check-input').addEventListener('change', function () {
            card.classList.toggle('selected', this.checked);
        });
    });

    document.getElementById('start-broadcast-btn').addEventListener('click', function () {
        const targets = [...document.querySelectorAll('.broadcast-target.selected')].map(t => t.dataset.target);
        if (!targets.length) {
            alert('Select at least one driver group to broadcast.');
            return;
        }
        broadcastModal.hide();

        // Broadcast is a request method — keep current delivery status (e.g. Waiting)
        const rows = [...document.querySelectorAll('.order-check:checked')].map(cb => cb.closest('.order-row')).filter(Boolean);
        const targetsRows = rows.length
            ? rows
            : [document.querySelector('.order-row[data-delivery="waiting"], .order-row[data-delivery="new"], .order-row[data-delivery="ready"]')].filter(Boolean);

        targetsRows.forEach(row => {
            // If brand new, treat broadcast like a request while waiting for assignment
            if (row.dataset.delivery === 'new') {
                row.dataset.delivery = 'waiting';
                row.children[8].innerHTML = `<span class="status-chip chip-waiting"><span class="dot"></span>Waiting</span>`;
            }
            // Do not change status to "broadcast" — only mark request pending on driver cell
            row.dataset.requestPending = '1';
            const driverCell = row.children[9];
            if (driverCell && !row.querySelector('.avatar')) {
                driverCell.innerHTML = `<span class="text-muted" style="font-size: 0.85rem;"><i class="bx bx-broadcast me-1" style="color: #ff7a00;"></i>Request pending</span>`;
            }
        });

        document.querySelectorAll('.order-check').forEach(cb => cb.checked = false);
        document.getElementById('select-all-orders').checked = false;
        updateSelectionUI();
        filterOrders();
    });

    filterOrders();
});
</script>
@endsection
