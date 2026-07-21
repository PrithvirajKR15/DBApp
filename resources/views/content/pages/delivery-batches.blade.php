@php
$isNavbar = false;
$data = $data ?? [];
$batches = $data['batches'] ?? [];
$batchDrivers = $data['drivers'] ?? [];
$pendingCount = count(array_filter($batches, fn ($b) => $b['status'] === 'pending'));
$acceptedCount = count(array_filter($batches, fn ($b) => $b['status'] === 'accepted'));
$assignedCount = count(array_filter($batches, fn ($b) => $b['status'] === 'assigned'));
$transitCount = count(array_filter($batches, fn ($b) => $b['status'] === 'in_transit'));
$completedCount = count(array_filter($batches, fn ($b) => $b['status'] === 'completed'));
$statusMeta = [
    'pending' => ['label' => 'Waiting', 'class' => 'chip-waiting'],
    'accepted' => ['label' => 'Accepted', 'class' => 'chip-accepted'],
    'assigned' => ['label' => 'Assigned', 'class' => 'chip-assigned'],
    'in_transit' => ['label' => 'In Transit', 'class' => 'chip-out'],
    'completed' => ['label' => 'Completed', 'class' => 'chip-completed'],
];
$deliveryChipClass = [
    'Waiting' => 'chip-waiting',
    'Accepted' => 'chip-accepted',
    'Assigned' => 'chip-assigned',
    'Out Delivery' => 'chip-out',
    'Delivered' => 'chip-completed',
];
$storeById = collect($data['stores'] ?? [])->keyBy('id');
foreach ($batches as &$batch) {
    if (empty($batch['hub']) && !empty($batch['store_id']) && isset($storeById[$batch['store_id']])) {
        $s = $storeById[$batch['store_id']];
        $batch['hub'] = ['lat' => $s['lat'], 'lng' => $s['lng'], 'name' => $s['name']];
    }
}
unset($batch);
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Delivery Batches')
@section('page-title', 'Delivery Batches')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="{{ asset('assets/css/batch-routes-map.css') }}" />
<style>
    .btn-primary-orange {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
        color: #ffffff !important;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }
    .btn-primary-orange:hover,
    .btn-primary-orange:focus {
        background-color: #e06b00 !important;
        border-color: #e06b00 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.2) !important;
    }
    .btn-pill {
        border-radius: 20px !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        padding: 6px 16px !important;
        border: 1px solid #e0e2e7 !important;
        background-color: #ffffff !important;
        color: #566a7f !important;
    }
    .btn-pill.active {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(255, 122, 0, 0.2) !important;
    }
    .batch-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
        transition: box-shadow 0.2s ease;
    }
    .batch-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.04); }
    .batch-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .chip-waiting { background: rgba(255,171,0,0.12); color: #ffab00; }
    .chip-waiting .dot { background: #ffab00; }
    .chip-accepted { background: rgba(0,207,232,0.12); color: #00cfe8; }
    .chip-accepted .dot { background: #00cfe8; }
    .chip-assigned { background: rgba(105,108,255,0.12); color: #696cff; }
    .chip-assigned .dot { background: #696cff; }
    .chip-out { background: rgba(234,84,85,0.12); color: #ea5455; }
    .chip-out .dot { background: #ea5455; }
    .chip-completed { background: rgba(40,199,111,0.12); color: #28c76f; }
    .chip-completed .dot { background: #28c76f; }
    .metric-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.4px; color: #8592a3; font-weight: 600; }
    .metric-value { font-size: 0.95rem; font-weight: 700; color: #32475c; }
    .batch-detail { display: none; border-top: 1px solid #f1f3f5; }
    .batch-card.expanded .batch-detail { display: block; }
    .batch-card.expanded .chevron-icon { transform: rotate(180deg); }
    .chevron-icon { transition: transform 0.2s ease; }
    .stop-badge {
        width: 26px; height: 26px; border-radius: 50%;
        background: #32475c; color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700;
    }
    .route-preview {
        background: linear-gradient(145deg, #f8fafc 0%, #eef2f7 100%);
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        min-height: 220px;
        position: relative;
        overflow: hidden;
    }
    .route-preview::before {
        content: '';
        position: absolute;
        inset: 20% 15%;
        border: 2px dashed #cbd5e1;
        border-radius: 40% 60% 50% 50%;
        opacity: 0.7;
    }
    .route-dot {
        position: absolute;
        width: 10px; height: 10px;
        border-radius: 50%;
        background: #ff7a00;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px rgba(255,122,0,0.3);
    }
    .prep-ready { color: #28c76f; font-weight: 600; font-size: 0.78rem; }
    .prep-packing { color: #ffab00; font-weight: 600; font-size: 0.78rem; }

    .assign-tab {
        border: none;
        border-bottom: 2px solid transparent;
        background: transparent;
        padding: 10px 4px;
        color: #64748b;
        font-weight: 500;
        font-size: 0.88rem;
        cursor: pointer;
    }
    .assign-tab.active {
        color: #ff7a00;
        border-bottom-color: #ff7a00;
        font-weight: 600;
    }
    .driver-pick-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        padding: 12px 14px;
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
    .driver-pick-check {
        width: 22px; height: 22px; border-radius: 50%;
        border: 2px solid #d1d5db;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; color: transparent;
    }
    .driver-pick-card.selected .driver-pick-check {
        border-color: #ff7a00; background: #ff7a00; color: #fff;
    }
    .driver-pick-check.square { border-radius: 6px; }
    .assign-hint {
        background: rgba(255, 122, 0, 0.06);
        border: 1px solid rgba(255, 122, 0, 0.18);
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 0.82rem;
        color: #566a7f;
    }
</style>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h3 class="mb-1 fw-bold text-body" style="font-size: 1.6rem; font-family: 'Public Sans', sans-serif;">Delivery Batch Review</h3>
        <p class="mb-0 text-muted" style="font-size: 0.9rem;">Review routes, spot collisions between assigned drivers, and move orders before delivery starts.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" id="btn-scroll-collision" style="border-radius: 8px; border-color: #e0e2e7; color: #566a7f;">
            <i class="bx bx-map-alt"></i>
            <span>Driver Route Map</span>
        </button>
        <a href="{{ url('/operations/delivery-batches/settings') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2" style="border-radius: 8px; border-color: #e0e2e7; color: #566a7f;">
            <i class="bx bx-cog"></i>
            <span>Configuration</span>
        </a>
        <a href="{{ url('/operations/delivery-batches/generate') }}" class="btn btn-primary-orange d-flex align-items-center gap-2" style="padding: 10px 18px; border-radius: 8px;">
            <i class="bx bx-refresh"></i>
            <span>Generate Batches</span>
        </a>
    </div>
</div>

<!-- Top Filters -->
<div class="card shadow-none border mb-3" style="border-radius: 12px;">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #e0e2e7 !important; border-radius: 8px !important;">
                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent" id="search-batches" placeholder="Search batches, route, store..." style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="filter-store" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Stores</option>
                    <option>Downtown SuperHub</option>
                    <option>Uptown Express Hub</option>
                    <option>Westside Grocer Hub</option>
                    <option>Harbor Market Hub</option>
                    <option>Eastside Fresh Hub</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="filter-zone" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Routes</option>
                    <option>North</option>
                    <option>Central</option>
                    <option>West</option>
                    <option>East</option>
                    <option>South</option>
                </select>
            </div>
            <div class="col-12 col-lg-5">
                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end" id="batch-tabs">
                    <button type="button" class="btn btn-pill batch-tab active" data-status="pending">Waiting ({{ $pendingCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="accepted">Accepted ({{ $acceptedCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="assigned">Assigned ({{ $assignedCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="in_transit">In Transit ({{ $transitCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="completed">Completed ({{ $completedCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="all">All</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-success d-none mb-3" id="generated-banner" style="border-radius: 10px; font-size: 0.88rem;">
    <i class="bx bx-check-circle me-1"></i>
    <span id="generated-banner-text">New batches generated using distance-based route optimization.</span>
</div>

<!-- Assigned drivers route collision overview -->
<div class="collision-panel mb-4" id="assigned-drivers-panel">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 p-3 border-bottom">
        <div>
            <h5 class="mb-1 fw-bold text-body" style="font-size: 1.1rem;">
                <i class="bx bx-map-alt me-1" style="color:#696cff;"></i>
                Assigned Drivers — Route Overview
            </h5>
            <p class="mb-0 text-muted" style="font-size: 0.88rem;">
                Drivers who are assigned but have not started delivery. Reorder stop numbers (1, 2, 3…) within a route, or move an order to another driver when routes collide.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-primary rounded-pill" id="collision-driver-count">0 drivers</span>
            <select class="form-select form-select-sm" id="collision-store-filter" style="width: auto; border-radius: 8px; min-width: 180px;">
                <option value="">All stores</option>
                <option value="downtown" selected>Downtown SuperHub</option>
                <option value="uptown">Uptown Express Hub</option>
                <option value="westside">Westside Grocer Hub</option>
                <option value="harbor">Harbor Market Hub</option>
                <option value="eastside">Eastside Fresh Hub</option>
            </select>
        </div>
    </div>
    <div class="p-3">
        <div class="collision-banner mb-3" id="collision-pick-banner">
            <i class="bx bx-info-circle me-1" style="color:#696cff;"></i>
            Use <strong>↑ ↓</strong> to change delivery order within a driver’s route, or the transfer icon to <strong>move</strong> an order to another driver.
        </div>
        <div class="alert alert-warning d-none mb-3 py-2" id="collision-picked-bar" style="border-radius: 10px; font-size: 0.88rem;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span id="collision-picked-text">Order picked</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="collision-cancel-pick" style="border-radius: 6px;">Cancel</button>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-8">
                <div id="assigned-drivers-map" style="height: 480px;"></div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold text-body mb-0">Drivers & stops</h6>
                    <small class="text-muted" id="collision-hint">↑↓ reorder · transfer to move</small>
                </div>
                <div class="d-flex flex-column gap-2" id="collision-driver-list" style="max-height: 440px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Move order modal -->
<div class="modal fade" id="moveOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Move Order to Another Driver</h5>
                    <small class="text-muted" id="move-order-summary">—</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="text-muted mb-3" style="font-size: 0.88rem;">Choose a driver who is assigned but has not started. The order will be added to their route and removed from the current driver.</p>
                <div class="d-flex flex-column gap-2" id="move-target-list"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <button type="button" class="btn btn-primary-orange" id="confirm-move-order" style="border-radius: 8px;" disabled>
                    <i class="bx bx-transfer me-1"></i>Confirm Move
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Batch List -->
<div class="d-flex flex-column gap-3" id="batch-list">
    @foreach ($batches as $i => $batch)
    @php
        $meta = $statusMeta[$batch['status']];
        $iconBg = match($batch['status']) {
            'pending' => 'rgba(255,171,0,0.12)',
            'accepted' => 'rgba(0,207,232,0.12)',
            'assigned' => 'rgba(105,108,255,0.12)',
            'in_transit' => 'rgba(234,84,85,0.12)',
            'completed' => 'rgba(40,199,111,0.12)',
            default => 'rgba(133,146,163,0.12)',
        };
        $iconColor = match($batch['status']) {
            'pending' => '#ffab00',
            'accepted' => '#00cfe8',
            'assigned' => '#696cff',
            'in_transit' => '#ea5455',
            'completed' => '#28c76f',
            default => '#8592a3',
        };
    @endphp
    @php
        $batchMapId = 'batch-map-' . preg_replace('/[^a-zA-Z0-9]/', '-', $batch['id']);
        $batchMapPayload = [
            'hub' => $batch['hub'] ?? null,
            'batch' => [
                'id' => $batch['id'],
                'route_label' => $batch['route_label'] ?? $batch['zone'],
                'zone' => $batch['zone'],
                'orders' => $batch['orders'],
                'suggested_driver' => $batch['suggested_driver'] ?? null,
            ],
        ];
        $gmapsWaypoints = collect($batch['orders'] ?? [])
            ->filter(fn ($o) => !empty($o['lat']) && !empty($o['lng']))
            ->sortBy('stop')
            ->map(fn ($o) => $o['lat'].','.$o['lng'])
            ->values()
            ->all();
        $gmapsUrl = !empty($batch['hub']['lat']) && count($gmapsWaypoints)
            ? 'https://www.google.com/maps/dir/?api=1&origin='.$batch['hub']['lat'].','.$batch['hub']['lng'].'&destination='.$batch['hub']['lat'].','.$batch['hub']['lng'].'&waypoints='.implode('|', $gmapsWaypoints).'&travelmode=driving'
            : '#';
    @endphp
    <div class="batch-card {{ $i === 0 ? 'expanded' : '' }}"
         data-status="{{ $batch['status'] }}"
         data-search="{{ strtolower($batch['id'].' '.$batch['zone'].' '.$batch['store']) }}"
         data-store="{{ $batch['store'] }}"
         data-zone="{{ $batch['zone'] }}"
         data-zone-key="{{ $batch['zone_key'] }}"
         data-store-id="{{ $batch['store_id'] ?? '' }}"
         data-batch-id="{{ $batch['id'] }}"
         data-batch-map='@json($batchMapPayload)'
         data-map-id="{{ $batchMapId }}">
        <div class="p-3 d-flex align-items-center gap-3 flex-wrap batch-summary" style="cursor: pointer;">
            <div class="batch-icon" style="background: {{ $iconBg }}; color: {{ $iconColor }};">
                <i class="bx {{ $batch['status'] === 'completed' ? 'bx-check-double' : 'bx-package' }}"></i>
            </div>
            <div class="flex-grow-1 min-w-0" style="min-width: 180px;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-bold text-body">Batch #{{ $batch['id'] }}</span>
                    <span class="status-chip {{ $meta['class'] }}"><span class="dot"></span>{{ $meta['label'] }}</span>
                </div>
                <small class="text-muted"><i class="bx bx-map-pin"></i> {{ $batch['zone'] }} · {{ $batch['store'] }}</small>
                @if (!empty($batch['completed_at']))
                <div style="font-size: 0.78rem; color: #28c76f; margin-top: 2px;">
                    <i class="bx bx-check-circle"></i> Completed {{ $batch['completed_at'] }}
                </div>
                @endif
            </div>
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="text-center">
                    <div class="metric-label">Orders</div>
                    <div class="metric-value">{{ $batch['stops'] }} Stops</div>
                </div>
                <div class="text-center">
                    <div class="metric-label">Distance</div>
                    <div class="metric-value">{{ $batch['distance'] }}</div>
                </div>
                <div class="text-center">
                    <div class="metric-label">{{ $batch['status'] === 'completed' ? 'Actual Time' : 'Est. Time' }}</div>
                    <div class="metric-value">{{ $batch['status'] === 'completed' ? ($batch['actual_time'] ?? $batch['est_time']) : $batch['est_time'] }}</div>
                </div>
                <div class="text-center">
                    <div class="metric-label">Total Value</div>
                    <div class="metric-value">${{ number_format($batch['value'], 2) }}</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                @if ($batch['driver'])
                <div class="d-flex align-items-center gap-2 me-2">
                    <div class="avatar avatar-xs" style="width: 28px; height: 28px;">
                        <img src="{{ asset('assets/img/avatars/'.$batch['driver']['avatar']) }}" class="rounded-circle" alt="">
                    </div>
                    <small class="fw-semibold text-body">{{ $batch['driver']['name'] }}</small>
                </div>
                @endif
                <button type="button" class="btn btn-sm btn-outline-secondary btn-view-details" style="border-radius: 6px;">View Details</button>
                @if ($batch['status'] === 'pending')
                <button type="button" class="btn btn-sm btn-primary-orange btn-assign-batch" style="border-radius: 6px;">Assign Driver</button>
                @elseif ($batch['status'] === 'completed')
                <button type="button" class="btn btn-sm btn-outline-success" style="border-radius: 6px; pointer-events: none;">
                    <i class="bx bx-check me-1"></i>Delivered
                </button>
                @endif
                <button type="button" class="btn btn-sm btn-icon btn-text-secondary toggle-batch">
                    <i class="bx bx-chevron-down chevron-icon" style="font-size: 1.25rem;"></i>
                </button>
            </div>
        </div>

        <div class="batch-detail px-3 pb-3">
            @if ($batch['status'] === 'completed')
            <div class="d-flex flex-wrap gap-3 mb-3 p-3 rounded-3" style="background: rgba(40,199,111,0.06); border: 1px solid rgba(40,199,111,0.18);">
                <div>
                    <div class="metric-label">Completed</div>
                    <div class="fw-semibold text-body">{{ $batch['completed_at'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="metric-label">Driver</div>
                    <div class="fw-semibold text-body">{{ $batch['driver']['name'] ?? '—' }} ({{ $batch['driver']['id'] ?? '' }})</div>
                </div>
                <div>
                    <div class="metric-label">Est. vs Actual</div>
                    <div class="fw-semibold text-body">{{ $batch['est_time'] }} → {{ $batch['actual_time'] ?? $batch['est_time'] }}</div>
                </div>
                <div>
                    <div class="metric-label">Stops Delivered</div>
                    <div class="fw-semibold text-body">{{ $batch['stops'] }} / {{ $batch['stops'] }}</div>
                </div>
            </div>
            @endif
            <div class="row g-3 pt-1">
                <div class="col-lg-7">
                    <h6 class="fw-bold text-body mb-3">{{ $batch['status'] === 'completed' ? 'Delivered Orders' : 'Orders in Batch' }}</h6>
                    <div class="table-responsive border rounded-3" style="border-color: #e0e2e7 !important;">
                        <table class="table table-sm mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Stop</th>
                                    <th>Order & Customer</th>
                                    <th>Address</th>
                                    <th>Value</th>
                                    <th>Payment</th>
                                    <th>Delivery</th>
                                    @if ($batch['status'] === 'completed')
                                    <th>Delivered At</th>
                                    @endif
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($batch['orders'] as $order)
                                @php
                                    $orderDelivery = $order['delivery'] ?? ($batch['status'] === 'completed' ? 'Delivered' : ($order['prep'] ?? 'Waiting'));
                                    $chipClass = $deliveryChipClass[$orderDelivery] ?? 'chip-waiting';
                                    $orderDetailUrl = $orderDelivery === 'Delivered'
                                        ? url('/operations/orders/'.$order['id'].'/completed').'?from=batch'
                                        : url('/operations/orders/'.$order['id']).'?from=batch';
                                @endphp
                                <tr class="batch-order-row" style="cursor: pointer;" onclick="window.location='{{ $orderDetailUrl }}'">
                                    <td><span class="stop-badge" @if($batch['status'] === 'completed') style="background:#28c76f;" @endif>{{ $order['stop'] }}</span></td>
                                    <td>
                                        <a href="{{ $orderDetailUrl }}" class="fw-semibold text-decoration-none" style="color: #ff7a00;" onclick="event.stopPropagation();">#{{ $order['id'] }}</a>
                                        <small class="text-muted d-block">{{ $order['customer'] }}</small>
                                    </td>
                                    <td class="text-muted" style="max-width: 180px;">{{ $order['address'] }}</td>
                                    <td class="fw-semibold">${{ number_format($order['value'], 2) }}</td>
                                    <td>{{ $order['payment'] }}</td>
                                    <td>
                                        <span class="status-chip {{ $chipClass }}"><span class="dot"></span>{{ $orderDelivery }}</span>
                                    </td>
                                    @if ($batch['status'] === 'completed')
                                    <td class="text-muted">{{ $order['delivered_at'] ?? '—' }}</td>
                                    @endif
                                    <td class="text-end">
                                        <a href="{{ $orderDetailUrl }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px; font-size: 0.75rem;" onclick="event.stopPropagation();">Details</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold text-body mb-0">{{ $batch['status'] === 'completed' ? 'Completed Route' : 'Route Map' }}</h6>
                        <a href="{{ $gmapsUrl }}" target="_blank" rel="noopener" class="text-decoration-none btn-open-gmaps" style="color: #ff7a00; font-size: 0.85rem; font-weight: 600;{{ $gmapsUrl === '#' ? ' pointer-events:none; opacity:0.5;' : '' }}">
                            Open in Maps <i class="bx bx-link-external"></i>
                        </a>
                    </div>
                    <div id="{{ $batchMapId }}" class="batch-detail-map-wrap batch-routes-map-wrap mb-2"></div>
                    <div class="d-flex justify-content-between" style="font-size: 0.82rem;">
                        <small class="text-muted"><i class="bx bx-trip"></i> Hub → Stop 1: {{ $batch['route']['hub_to_first'] }}</small>
                        <small class="text-muted"><i class="bx bx-undo"></i> Return: {{ $batch['route']['return'] }}</small>
                    </div>
                    <small class="text-muted d-block mt-2">
                        @if ($batch['status'] === 'completed')
                            All stops in this batch were delivered successfully.
                        @else
                            Dashed lines show optimized stop sequence from the store hub.
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="d-flex align-items-center justify-content-between mt-4 flex-wrap gap-2">
    <span class="text-muted" style="font-size: 0.88rem;" id="batch-showing">Showing {{ count($batches) }} batches</span>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" disabled style="border-radius: 6px;">Previous</button>
        <button class="btn btn-outline-secondary btn-sm" style="border-radius: 6px;">Next</button>
    </div>
</div>

<!-- Assign Driver Modal -->
<div class="modal fade" id="assignBatchDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Assign Driver to Batch</h5>
                    <small class="text-muted">Drivers ranked by proximity to route · <span class="fw-semibold text-body" id="assign-zone-label">—</span></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.4px;">Batch</div>
                        <div class="fw-bold text-body" id="assign-batch-id">—</div>
                    </div>
                    <div class="input-group input-group-merge border rounded" style="width: 220px; border-radius: 8px !important; border-color: #e0e2e7 !important;">
                        <span class="input-group-text border-0 bg-transparent"><i class="bx bx-search text-muted"></i></span>
                        <input type="text" class="form-control border-0 bg-transparent" id="search-batch-drivers" placeholder="Search drivers..." style="box-shadow: none; font-size: 0.88rem;">
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3" style="border-bottom: 1px solid #e2e8f0;">
                    <button type="button" class="assign-tab active" data-tab="store">
                        Store Drivers <span class="badge rounded-pill bg-label-warning ms-1" id="store-count-badge">0</span>
                    </button>
                    <button type="button" class="assign-tab" data-tab="zone">
                        Zone Drivers <span class="badge rounded-pill bg-label-info ms-1" id="zone-count-badge">0</span>
                    </button>
                </div>

                <div class="assign-hint mb-3" id="assign-hint-store">
                    <i class="bx bx-info-circle" style="color: #ff7a00;"></i>
                    <strong>Direct assign.</strong> Store drivers cannot accept or reject — once assigned they must deliver this batch.
                </div>
                <div class="assign-hint mb-3 d-none" id="assign-hint-zone">
                    <i class="bx bx-info-circle" style="color: #ff7a00;"></i>
                    <strong>Send request.</strong> Select one or more zone drivers in this order area. Status stays Waiting until a driver accepts — then it becomes Accepted.
                </div>

                <div id="store-drivers-panel">
                    <div class="d-flex flex-column gap-2" id="store-driver-list" style="max-height: 320px; overflow-y: auto;"></div>
                    <div class="text-muted text-center py-4 d-none" id="store-empty">No available store drivers near this route.</div>
                </div>
                <div id="zone-drivers-panel" class="d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="text-muted fw-semibold text-uppercase" style="letter-spacing: 0.4px;">Select drivers to request</small>
                        <button type="button" class="btn btn-sm btn-link p-0" id="select-all-zone" style="color: #ff7a00; font-size: 0.82rem; text-decoration: none;">Select all</button>
                    </div>
                    <div class="d-flex flex-column gap-2" id="zone-driver-list" style="max-height: 320px; overflow-y: auto;"></div>
                    <div class="text-muted text-center py-4 d-none" id="zone-empty">No available zone drivers near this route.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                <button type="button" class="btn btn-primary-orange" id="confirm-batch-assign" style="border-radius: 8px;" disabled>
                    <i class="bx bx-check me-1"></i><span id="confirm-assign-label">Assign Driver</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('assets/js/batch-generation.js') }}"></script>
<script src="{{ asset('assets/js/batch-routes-map.js') }}"></script>
<script>
const BATCH_DRIVERS = @json($batchDrivers);
const AVATAR_BASE = @json(asset('assets/img/avatars'));
const DELIVERY_CHIP_CLASS = @json($deliveryChipClass);
const ALL_BATCHES = @json($batches);
const BATCH_COLORS = ['#3b82f6', '#22c55e', '#f97316', '#a855f7', '#ec4899', '#14b8a6'];

document.addEventListener('DOMContentLoaded', function () {
    let activeStatus = 'pending';
    const assignModal = new bootstrap.Modal(document.getElementById('assignBatchDriverModal'));
    const moveModal = new bootstrap.Modal(document.getElementById('moveOrderModal'));
    let currentBatchCard = null;
    let currentZoneKey = '';
    let currentStoreId = '';
    let currentBatchHub = null;
    let assignMode = 'store';
    let selectedStoreId = null;
    let selectedZoneIds = new Set();
    const detailMaps = new Map();

    // --- Assigned drivers collision map + pick/drop ---
    let assignedRoutes = ALL_BATCHES
        .filter(b => ['assigned', 'accepted'].includes(b.status) && b.driver)
        .map(b => JSON.parse(JSON.stringify(b)));
    let assignedMap = null;
    let pickedOrder = null; // { order, fromBatchId }
    let moveTargetBatchId = null;

    function getVisibleAssignedRoutes() {
        const storeId = document.getElementById('collision-store-filter').value;
        return assignedRoutes.filter(b => {
            if (!storeId) return true;
            return b.store_id === storeId;
        });
    }

    function renumberStops(orders) {
        return orders.map((o, i) => ({ ...o, stop: i + 1 }));
    }

    function reorderStop(batchId, orderId, direction) {
        const batch = assignedRoutes.find(b => b.id === batchId);
        if (!batch || !batch.orders?.length) return;

        const idx = batch.orders.findIndex(o => o.id === orderId);
        if (idx < 0) return;

        const swapWith = direction === 'up' ? idx - 1 : idx + 1;
        if (swapWith < 0 || swapWith >= batch.orders.length) return;

        const orders = [...batch.orders];
        [orders[idx], orders[swapWith]] = [orders[swapWith], orders[idx]];
        batch.orders = renumberStops(orders);

        syncBatchCardOrders(batch);
        refreshAssignedMap();
        renderCollisionDriverList();

        // Keep the same driver card highlighted
        const card = document.querySelector(`.collision-driver-card[data-batch-id="${batchId}"]`);
        card?.classList.add('active');
    }

    function renderCollisionDriverList() {
        const list = document.getElementById('collision-driver-list');
        const routes = getVisibleAssignedRoutes();
        document.getElementById('collision-driver-count').textContent =
            `${routes.length} driver${routes.length === 1 ? '' : 's'}`;

        if (!routes.length) {
            list.innerHTML = '<div class="text-muted text-center py-4">No assigned drivers waiting to start for this store.</div>';
            return;
        }

        list.innerHTML = routes.map((batch, idx) => {
            const color = BATCH_COLORS[idx % BATCH_COLORS.length];
            const avatar = batch.driver?.avatar || '1.png';
            const total = (batch.orders || []).length;
            const ordersHtml = (batch.orders || []).map((order, oIdx) => `
                <div class="collision-order-row ${pickedOrder?.order?.id === order.id ? 'picked' : ''}"
                     data-order-id="${order.id}" data-batch-id="${batch.id}" draggable="true">
                    <span class="stop-badge" style="background:${color};width:22px;height:22px;font-size:0.7rem;">${order.stop}</span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-body text-truncate">${order.locality || order.customer}</div>
                        <small class="text-muted">${order.id}</small>
                    </div>
                    <div class="stop-reorder-btns">
                        <button type="button" class="btn-reorder-stop" data-dir="up" data-order-id="${order.id}" data-batch-id="${batch.id}" title="Move earlier in route" ${oIdx === 0 ? 'disabled' : ''}>
                            <i class="bx bx-chevron-up"></i>
                        </button>
                        <button type="button" class="btn-reorder-stop" data-dir="down" data-order-id="${order.id}" data-batch-id="${batch.id}" title="Move later in route" ${oIdx === total - 1 ? 'disabled' : ''}>
                            <i class="bx bx-chevron-down"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-pick-order" title="Move to another driver" style="font-size:1rem;">
                        <i class="bx bx-transfer"></i>
                    </button>
                </div>`).join('');

            const dropHtml = pickedOrder && pickedOrder.fromBatchId !== batch.id
                ? `<div class="collision-drop-target mt-2" data-drop-batch="${batch.id}">
                        Drop here → ${batch.driver.name}
                   </div>`
                : '';

            return `
            <div class="collision-driver-card" data-batch-id="${batch.id}" data-batch-idx="${idx}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="width:10px;height:10px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                    <div class="avatar avatar-xs" style="width:28px;height:28px;">
                        <img src="${AVATAR_BASE}/${avatar}" class="rounded-circle" alt="">
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-body text-truncate">${batch.driver.name}</div>
                        <small class="text-muted">${batch.id} · ${batch.stops} stops · ${batch.status === 'accepted' ? 'Accepted' : 'Assigned'}</small>
                    </div>
                </div>
                <div class="d-flex flex-column gap-1 collision-orders-list" data-batch-id="${batch.id}">${ordersHtml}</div>
                ${dropHtml}
            </div>`;
        }).join('');
    }

    function openMoveModal(order, fromBatch) {
        pickedOrder = { order: { ...order }, fromBatchId: fromBatch.id };
        moveTargetBatchId = null;
        document.getElementById('move-order-summary').textContent =
            `${order.id} · ${order.customer || ''} (from ${fromBatch.driver?.name || fromBatch.id})`;

        const targets = getVisibleAssignedRoutes().filter(b => b.id !== fromBatch.id);
        const list = document.getElementById('move-target-list');
        if (!targets.length) {
            list.innerHTML = '<div class="text-muted text-center py-3">No other assigned drivers available to receive this order.</div>';
            document.getElementById('confirm-move-order').disabled = true;
        } else {
            list.innerHTML = targets.map(b => `
                <div class="driver-pick-card move-target-card" data-batch-id="${b.id}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar" style="width:40px;height:40px;">
                            <img src="${AVATAR_BASE}/${b.driver.avatar || '1.png'}" class="rounded-circle" alt="">
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-body">${b.driver.name}</div>
                            <small class="text-muted">${b.id} · ${b.orders.length} stops · ${b.route_label || b.zone}</small>
                        </div>
                        <div class="driver-pick-check"><i class="bx bx-check" style="font-size:0.85rem;"></i></div>
                    </div>
                </div>`).join('');
            document.getElementById('confirm-move-order').disabled = true;
        }

        document.getElementById('collision-picked-bar').classList.remove('d-none');
        document.getElementById('collision-picked-text').innerHTML =
            `<i class="bx bx-package me-1"></i> Picked <strong>${order.id}</strong> from <strong>${fromBatch.driver?.name}</strong> — select a driver to drop onto.`;
        renderCollisionDriverList();
        moveModal.show();
    }

    function clearPick() {
        pickedOrder = null;
        moveTargetBatchId = null;
        document.getElementById('collision-picked-bar').classList.add('d-none');
        document.getElementById('confirm-move-order').disabled = true;
        renderCollisionDriverList();
    }

    function moveOrderToBatch(toBatchId) {
        if (!pickedOrder || !toBatchId || pickedOrder.fromBatchId === toBatchId) return;

        const from = assignedRoutes.find(b => b.id === pickedOrder.fromBatchId);
        const to = assignedRoutes.find(b => b.id === toBatchId);
        if (!from || !to) return;

        const idx = from.orders.findIndex(o => o.id === pickedOrder.order.id);
        if (idx < 0) return;

        const [order] = from.orders.splice(idx, 1);
        order.delivery = to.status === 'accepted' ? 'Accepted' : 'Assigned';
        to.orders.push(order);

        from.orders = renumberStops(from.orders);
        to.orders = renumberStops(to.orders);
        from.stops = from.orders.length;
        to.stops = to.orders.length;
        from.value = Math.round(from.orders.reduce((s, o) => s + (o.value || 0), 0) * 100) / 100;
        to.value = Math.round(to.orders.reduce((s, o) => s + (o.value || 0), 0) * 100) / 100;

        // Sync list cards if present
        syncBatchCardOrders(from);
        syncBatchCardOrders(to);

        clearPick();
        moveModal.hide();
        refreshAssignedMap();
        renderCollisionDriverList();

        const toast = document.createElement('div');
        toast.className = 'alert alert-success mb-3';
        toast.style.cssText = 'border-radius:10px;font-size:0.88rem;';
        toast.innerHTML = `<i class="bx bx-check-circle me-1"></i> Moved <strong>${order.id}</strong> to <strong>${to.driver.name}</strong>. Routes updated.`;
        const panel = document.getElementById('assigned-drivers-panel');
        panel.parentElement.insertBefore(toast, panel);
        setTimeout(() => toast.remove(), 4000);
    }

    function syncBatchCardOrders(batch) {
        const card = document.querySelector(`.batch-card[data-batch-id="${batch.id}"]`);
        if (!card) return;
        card.dataset.batchMap = JSON.stringify({
            hub: batch.hub || null,
            batch: {
                id: batch.id,
                route_label: batch.route_label || batch.zone,
                zone: batch.zone,
                orders: batch.orders,
                suggested_driver: batch.suggested_driver || null,
                driver: batch.driver,
            },
        });
        // Force detail map rebuild next expand
        const mapId = card.dataset.mapId;
        if (mapId && detailMaps.has(mapId)) {
            detailMaps.get(mapId).destroy?.();
            detailMaps.delete(mapId);
            if (card.classList.contains('expanded')) {
                setTimeout(() => initBatchDetailMap(card), 50);
            }
        }
        const metricStops = [...card.querySelectorAll('.metric-value')].find(el =>
            /Stop/i.test(el.textContent)
        );
        if (metricStops) {
            metricStops.textContent = `${batch.stops} Stops`;
        }

        // Refresh stop numbers + order in the expanded table
        const tbody = card.querySelector('tbody');
        if (tbody && batch.orders?.length) {
            const rows = [...tbody.querySelectorAll('tr')];
            const byId = {};
            rows.forEach(row => {
                const link = row.querySelector('a[href*="/operations/orders/"]');
                if (!link) return;
                const match = link.textContent.match(/#(ORD-[\w-]+)/);
                if (match) byId[match[1]] = row;
            });
            const frag = document.createDocumentFragment();
            batch.orders.forEach(order => {
                const row = byId[order.id];
                if (!row) return;
                const badge = row.querySelector('.stop-badge');
                if (badge) badge.textContent = order.stop;
                frag.appendChild(row);
            });
            if (frag.childNodes.length) {
                tbody.innerHTML = '';
                tbody.appendChild(frag);
            }
        }
    }

    function refreshAssignedMap() {
        const routes = getVisibleAssignedRoutes();
        if (assignedMap) {
            assignedMap.updateBatches(routes);
            return;
        }
        if (!window.DeliverEaseBatchMap) return;
        assignedMap = window.DeliverEaseBatchMap.createBatchRoutesMap('assigned-drivers-map', {
            hub: routes[0]?.hub || null,
            batches: routes,
            drivers: BATCH_DRIVERS,
            height: 480,
            showAllBatches: true,
            showAssignedDrivers: true,
            showDriverOnFirst: false,
            interactiveOrders: true,
            legendTitle: 'Assigned drivers',
            onOrderClick(order, batch) {
                openMoveModal(order, batch);
            },
            onBatchSelect(idx) {
                document.querySelectorAll('.collision-driver-card').forEach((c, i) => {
                    c.classList.toggle('active', i === idx);
                });
            },
        });
    }

    document.getElementById('collision-store-filter').addEventListener('change', () => {
        if (assignedMap) {
            assignedMap.destroy();
            assignedMap = null;
        }
        clearPick();
        refreshAssignedMap();
        renderCollisionDriverList();
    });

    document.getElementById('btn-scroll-collision').addEventListener('click', () => {
        document.getElementById('assigned-drivers-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => assignedMap?.invalidateSize(), 300);
    });

    document.getElementById('collision-cancel-pick').addEventListener('click', clearPick);

    document.getElementById('collision-driver-list').addEventListener('click', (e) => {
        const reorderBtn = e.target.closest('.btn-reorder-stop');
        if (reorderBtn && !reorderBtn.disabled) {
            e.stopPropagation();
            reorderStop(reorderBtn.dataset.batchId, reorderBtn.dataset.orderId, reorderBtn.dataset.dir);
            return;
        }

        const drop = e.target.closest('.collision-drop-target');
        if (drop && pickedOrder) {
            moveOrderToBatch(drop.dataset.dropBatch);
            return;
        }

        // Only transfer icon opens move-to-driver modal (not the whole row)
        const pickBtn = e.target.closest('.btn-pick-order');
        if (pickBtn) {
            e.stopPropagation();
            const row = pickBtn.closest('.collision-order-row');
            if (!row) return;
            const batch = assignedRoutes.find(b => b.id === row.dataset.batchId);
            const order = batch?.orders?.find(o => o.id === row.dataset.orderId);
            if (batch && order) openMoveModal(order, batch);
            return;
        }

        const card = e.target.closest('.collision-driver-card');
        if (card && assignedMap) {
            const idx = parseInt(card.dataset.batchIdx, 10);
            if (!Number.isNaN(idx)) assignedMap.highlight(idx);
            document.querySelectorAll('.collision-driver-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        }
    });

    // Drag-and-drop to reorder stops within the same driver
    let dragOrderId = null;
    let dragBatchId = null;

    document.getElementById('collision-driver-list').addEventListener('dragstart', (e) => {
        const row = e.target.closest('.collision-order-row');
        if (!row) return;
        dragOrderId = row.dataset.orderId;
        dragBatchId = row.dataset.batchId;
        row.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragOrderId);
    });

    document.getElementById('collision-driver-list').addEventListener('dragend', (e) => {
        e.target.closest('.collision-order-row')?.classList.remove('dragging');
        document.querySelectorAll('.collision-order-row.drag-over').forEach(r => r.classList.remove('drag-over'));
        dragOrderId = null;
        dragBatchId = null;
    });

    document.getElementById('collision-driver-list').addEventListener('dragover', (e) => {
        const row = e.target.closest('.collision-order-row');
        if (!row || !dragOrderId) return;
        if (row.dataset.batchId !== dragBatchId) return; // only reorder within same driver
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        document.querySelectorAll('.collision-order-row.drag-over').forEach(r => r.classList.remove('drag-over'));
        row.classList.add('drag-over');
    });

    document.getElementById('collision-driver-list').addEventListener('drop', (e) => {
        const row = e.target.closest('.collision-order-row');
        if (!row || !dragOrderId || !dragBatchId) return;
        e.preventDefault();
        if (row.dataset.batchId !== dragBatchId) return;
        if (row.dataset.orderId === dragOrderId) return;

        const batch = assignedRoutes.find(b => b.id === dragBatchId);
        if (!batch) return;

        const fromIdx = batch.orders.findIndex(o => o.id === dragOrderId);
        const toIdx = batch.orders.findIndex(o => o.id === row.dataset.orderId);
        if (fromIdx < 0 || toIdx < 0) return;

        const orders = [...batch.orders];
        const [moved] = orders.splice(fromIdx, 1);
        orders.splice(toIdx, 0, moved);
        batch.orders = renumberStops(orders);

        syncBatchCardOrders(batch);
        refreshAssignedMap();
        renderCollisionDriverList();
        document.querySelector(`.collision-driver-card[data-batch-id="${dragBatchId}"]`)?.classList.add('active');
    });

    document.getElementById('move-target-list').addEventListener('click', (e) => {
        const card = e.target.closest('.move-target-card');
        if (!card) return;
        document.querySelectorAll('.move-target-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        moveTargetBatchId = card.dataset.batchId;
        document.getElementById('confirm-move-order').disabled = false;
    });

    document.getElementById('confirm-move-order').addEventListener('click', () => {
        if (moveTargetBatchId) moveOrderToBatch(moveTargetBatchId);
    });

    // Init collision map after a short delay so Leaflet is ready
    setTimeout(() => {
        refreshAssignedMap();
        renderCollisionDriverList();
    }, 200);

    function googleMapsUrl(hub, orders) {
        if (!hub?.lat || !orders?.length) return '#';
        const wps = orders
            .filter(o => o.lat != null && o.lng != null)
            .sort((a, b) => (a.stop || 0) - (b.stop || 0))
            .map(o => `${o.lat},${o.lng}`);
        if (!wps.length) return '#';
        const origin = `${hub.lat},${hub.lng}`;
        return `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${origin}&waypoints=${wps.join('|')}&travelmode=driving`;
    }

    function initBatchDetailMap(card) {
        if (!card || !window.DeliverEaseBatchMap) return;
        const mapId = card.dataset.mapId;
        if (!mapId || detailMaps.has(mapId)) {
            detailMaps.get(mapId)?.invalidateSize();
            return;
        }
        let payload;
        try {
            payload = JSON.parse(card.dataset.batchMap || '{}');
        } catch (e) {
            return;
        }
        if (!payload.hub || !payload.batch) return;

        const instance = window.DeliverEaseBatchMap.createBatchRoutesMap(mapId, {
            hub: payload.hub,
            batches: [payload.batch],
            drivers: BATCH_DRIVERS,
            showAllBatches: false,
            highlightIndex: 0,
            showDriverOnFirst: !!payload.batch.suggested_driver,
            height: 300,
        });
        detailMaps.set(mapId, instance);
    }

    function initVisibleBatchMaps() {
        document.querySelectorAll('.batch-card.expanded').forEach(initBatchDetailMap);
    }

    function injectGeneratedBatches() {
        let payload;
        try {
            payload = JSON.parse(sessionStorage.getItem('deliverease_generated_batches') || 'null');
        } catch (e) {
            return;
        }
        if (!payload || !Array.isArray(payload.batches) || !payload.batches.length) return;

        const list = document.getElementById('batch-list');
        const fragment = document.createDocumentFragment();

        payload.batches.slice().reverse().forEach((batch, i) => {
            const card = buildBatchCard(batch, i === 0);
            fragment.appendChild(card);
        });
        list.insertBefore(fragment, list.firstChild);

        const banner = document.getElementById('generated-banner');
        banner.classList.remove('d-none');
        document.getElementById('generated-banner-text').textContent =
            `${payload.batches.length} new batch${payload.batches.length === 1 ? '' : 'es'} generated for ${payload.store_name} using distance-based route optimization.`;

        const pendingTab = document.querySelector('.batch-tab[data-status="pending"]');
        if (pendingTab) {
            const count = document.querySelectorAll('.batch-card[data-status="pending"]').length;
            pendingTab.textContent = `Waiting (${count})`;
        }

        sessionStorage.removeItem('deliverease_generated_batches');
        filterBatches();
        initVisibleBatchMaps();
    }

    function buildBatchCard(batch, expanded) {
        const mapId = 'batch-map-' + batch.id.replace(/[^a-zA-Z0-9]/g, '-');
        const mapPayload = {
            hub: batch.hub || null,
            batch: {
                id: batch.id,
                route_label: batch.route_label || batch.zone,
                zone: batch.zone,
                orders: batch.orders || [],
                suggested_driver: batch.suggested_driver || null,
            },
        };
        const gmaps = googleMapsUrl(batch.hub, batch.orders);

        const div = document.createElement('div');
        div.className = 'batch-card' + (expanded ? ' expanded' : '');
        div.dataset.status = batch.status || 'pending';
        div.dataset.search = (batch.id + ' ' + batch.zone + ' ' + batch.store).toLowerCase();
        div.dataset.store = batch.store;
        div.dataset.zone = batch.zone;
        div.dataset.zoneKey = batch.zone_key || '';
        div.dataset.batchId = batch.id;
        div.dataset.storeId = batch.store_id || '';
        div.dataset.mapId = mapId;
        div.dataset.batchMap = JSON.stringify(mapPayload);
        if (batch.hub) {
            div.dataset.hubLat = batch.hub.lat;
            div.dataset.hubLng = batch.hub.lng;
        }

        const ordersHtml = (batch.orders || []).map(order => {
            const chipClass = DELIVERY_CHIP_CLASS[order.delivery] || 'chip-waiting';
            const detailUrl = `{{ url('/operations/orders') }}/${order.id}?from=batch`;
            return `<tr class="batch-order-row" style="cursor:pointer;" onclick="window.location='${detailUrl}'">
                <td><span class="stop-badge">${order.stop}</span></td>
                <td><a href="${detailUrl}" class="fw-semibold text-decoration-none" style="color:#ff7a00;" onclick="event.stopPropagation();">#${order.id}</a>
                    <small class="text-muted d-block">${order.customer}</small></td>
                <td class="text-muted" style="max-width:180px;">${order.address}</td>
                <td class="fw-semibold">$${Number(order.value).toFixed(2)}</td>
                <td>${order.payment}</td>
                <td><span class="status-chip ${chipClass}"><span class="dot"></span>${order.delivery}</span></td>
                <td class="text-end"><a href="${detailUrl}" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;font-size:0.75rem;" onclick="event.stopPropagation();">Details</a></td>
            </tr>`;
        }).join('');

        const suggested = batch.suggested_driver
            ? `<small class="text-muted d-block mt-1" style="font-size:0.78rem;"><i class="bx bx-user"></i> Suggested: ${batch.suggested_driver.name} (${batch.suggested_driver.reason})</small>`
            : '';

        div.innerHTML = `
            <div class="p-3 d-flex align-items-center gap-3 flex-wrap batch-summary" style="cursor:pointer;">
                <div class="batch-icon" style="background:rgba(255,171,0,0.12);color:#ffab00;"><i class="bx bx-package"></i></div>
                <div class="flex-grow-1 min-w-0" style="min-width:180px;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-bold text-body">Batch #${batch.id}</span>
                        <span class="status-chip chip-waiting"><span class="dot"></span>Waiting</span>
                        <span class="badge bg-label-success rounded-pill" style="font-size:0.65rem;">New</span>
                    </div>
                    <small class="text-muted"><i class="bx bx-map-pin"></i> ${batch.zone} · ${batch.store}</small>
                    ${suggested}
                </div>
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="text-center"><div class="metric-label">Orders</div><div class="metric-value">${batch.stops} Stops</div></div>
                    <div class="text-center"><div class="metric-label">Distance</div><div class="metric-value">${batch.distance}</div></div>
                    <div class="text-center"><div class="metric-label">Est. Time</div><div class="metric-value">${batch.est_time}</div></div>
                    <div class="text-center"><div class="metric-label">Total Value</div><div class="metric-value">$${Number(batch.value).toFixed(2)}</div></div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-view-details" style="border-radius:6px;">View Details</button>
                    <button type="button" class="btn btn-sm btn-primary-orange btn-assign-batch" style="border-radius:6px;">Assign Driver</button>
                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary toggle-batch"><i class="bx bx-chevron-down chevron-icon" style="font-size:1.25rem;"></i></button>
                </div>
            </div>
            <div class="batch-detail px-3 pb-3">
                <div class="row g-3 pt-1">
                    <div class="col-lg-7">
                        <h6 class="fw-bold text-body mb-3">Orders in Batch</h6>
                        <div class="table-responsive border rounded-3" style="border-color:#e0e2e7!important;">
                            <table class="table table-sm mb-0" style="font-size:0.85rem;">
                                <thead class="table-light"><tr><th>Stop</th><th>Order & Customer</th><th>Address</th><th>Value</th><th>Payment</th><th>Delivery</th><th class="text-end">Action</th></tr></thead>
                                <tbody>${ordersHtml}</tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold text-body mb-0">Route Map</h6>
                            <a href="${gmaps}" target="_blank" rel="noopener" class="text-decoration-none" style="color:#ff7a00;font-size:0.85rem;font-weight:600;${gmaps === '#' ? 'pointer-events:none;opacity:0.5;' : ''}">
                                Open in Maps <i class="bx bx-link-external"></i>
                            </a>
                        </div>
                        <div id="${mapId}" class="batch-detail-map-wrap batch-routes-map-wrap mb-2"></div>
                        <div class="d-flex justify-content-between" style="font-size:0.82rem;">
                            <small class="text-muted"><i class="bx bx-trip"></i> Hub → Stop 1: ${batch.route.hub_to_first}</small>
                            <small class="text-muted"><i class="bx bx-undo"></i> Return: ${batch.route.return}</small>
                        </div>
                        <small class="text-muted d-block mt-2">Grouped by lowest extra travel distance — border locations assigned to the most efficient route.</small>
                    </div>
                </div>
            </div>`;
        if (expanded) {
            setTimeout(() => initBatchDetailMap(div), 50);
        }
        return div;
    }

    if (new URLSearchParams(window.location.search).get('generated') === '1') {
        injectGeneratedBatches();
    }
    initVisibleBatchMaps();

    document.querySelectorAll('.batch-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.batch-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeStatus = this.dataset.status;
            filterBatches();
        });
    });

    ['search-batches', 'filter-store', 'filter-zone'].forEach(id => {
        document.getElementById(id).addEventListener('input', filterBatches);
        document.getElementById(id).addEventListener('change', filterBatches);
    });

    function filterBatches() {
        const q = (document.getElementById('search-batches').value || '').toLowerCase();
        const store = document.getElementById('filter-store').value;
        const zone = document.getElementById('filter-zone').value.toLowerCase();
        let visible = 0;
        document.querySelectorAll('.batch-card').forEach(card => {
            const matchStatus = activeStatus === 'all' || card.dataset.status === activeStatus;
            const matchSearch = !q || (card.dataset.search || '').includes(q);
            const matchStore = !store || card.dataset.store === store;
            const matchZone = !zone || (card.dataset.zone || '').toLowerCase().includes(zone);
            const show = matchStatus && matchSearch && matchStore && matchZone;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        document.getElementById('batch-showing').textContent = `Showing ${visible} batches`;
    }

    function batchTargetPoint(card) {
        if (card.dataset.hubLat && card.dataset.hubLng) {
            return { lat: parseFloat(card.dataset.hubLat), lng: parseFloat(card.dataset.hubLng) };
        }
        return null;
    }

    function driverScore(driver, target, storeId) {
        if (!target || driver.lat == null) return driver.load || 0;
        const dist = window.DeliverEaseBatchGen
            ? window.DeliverEaseBatchGen.roadKm({ lat: driver.lat, lng: driver.lng }, target)
            : 0;
        const typePenalty = driver.type === 'zone' ? 1.5 : 0;
        const storeMismatch = driver.type === 'store' && storeId && driver.store_id !== storeId ? 5 : 0;
        return dist + typePenalty + storeMismatch + (driver.load || 0) * 0.3;
    }

    function driversForBatch(card, type) {
        const zoneKey = card.dataset.zoneKey || currentZoneKey;
        const storeId = card.dataset.storeId || currentStoreId;
        const target = batchTargetPoint(card);

        return BATCH_DRIVERS
            .filter(d => {
                if (d.type !== type || d.status !== 'available') return false;
                if (type === 'store') return !storeId || d.store_id === storeId;
                if (zoneKey && Array.isArray(d.zones)) return d.zones.includes(zoneKey);
                return true;
            })
            .map(d => ({ driver: d, score: driverScore(d, target, storeId) }))
            .sort((a, b) => a.score - b.score)
            .map(x => x.driver);
    }

    function driverCardHtml(driver, multi) {
        const checkClass = multi ? 'driver-pick-check square' : 'driver-pick-check';
        return `
            <div class="driver-pick-card" data-driver-id="${driver.id}" data-driver-name="${driver.name}" data-avatar="${driver.avatar}" data-search="${(driver.name + ' ' + driver.id + ' ' + (driver.vehicle || '')).toLowerCase()}">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar" style="width: 40px; height: 40px;">
                        <img src="${AVATAR_BASE}/${driver.avatar}" class="rounded-circle" alt="">
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold text-body">${driver.name}</span>
                            <span class="badge bg-label-${driver.type === 'store' ? 'warning' : 'info'} rounded-pill" style="font-size: 0.65rem;">${driver.type === 'store' ? 'Store' : 'Zone'}</span>
                        </div>
                        <small class="text-muted">ID: ${driver.id} · ${driver.vehicle}${driver.store ? ' · ' + driver.store : ''}</small>
                        <div class="d-flex gap-3 mt-1" style="font-size: 0.8rem;">
                            <span><span class="text-muted">Load:</span> <strong>${driver.load}</strong></span>
                            <span><span class="text-muted">Dist:</span> <strong>${driver.distance}</strong> (${driver.eta})</span>
                        </div>
                    </div>
                    <div class="${checkClass}"><i class="bx bx-check" style="font-size: 0.85rem;"></i></div>
                </div>
            </div>`;
    }

    function renderDriverLists() {
        const card = currentBatchCard;
        const storeDrivers = card ? driversForBatch(card, 'store') : [];
        const zoneDrivers = card ? driversForBatch(card, 'zone') : [];
        const storeList = document.getElementById('store-driver-list');
        const zoneList = document.getElementById('zone-driver-list');

        storeList.innerHTML = storeDrivers.map(d => driverCardHtml(d, false)).join('');
        zoneList.innerHTML = zoneDrivers.map(d => driverCardHtml(d, true)).join('');

        document.getElementById('store-count-badge').textContent = storeDrivers.length;
        document.getElementById('zone-count-badge').textContent = zoneDrivers.length;
        document.getElementById('store-empty').classList.toggle('d-none', storeDrivers.length > 0);
        document.getElementById('zone-empty').classList.toggle('d-none', zoneDrivers.length > 0);

        selectedStoreId = null;
        selectedZoneIds = new Set();
        updateConfirmBtn();
        applyDriverSearch();
    }

    function setAssignMode(mode) {
        assignMode = mode;
        document.querySelectorAll('.assign-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === mode));
        document.getElementById('store-drivers-panel').classList.toggle('d-none', mode !== 'store');
        document.getElementById('zone-drivers-panel').classList.toggle('d-none', mode !== 'zone');
        document.getElementById('assign-hint-store').classList.toggle('d-none', mode !== 'store');
        document.getElementById('assign-hint-zone').classList.toggle('d-none', mode !== 'zone');
        document.getElementById('confirm-assign-label').textContent = mode === 'store' ? 'Assign Driver' : 'Send Request';
        updateConfirmBtn();
    }

    function updateConfirmBtn() {
        const btn = document.getElementById('confirm-batch-assign');
        if (assignMode === 'store') {
            btn.disabled = !selectedStoreId;
        } else {
            btn.disabled = selectedZoneIds.size === 0;
        }
    }

    function applyDriverSearch() {
        const q = (document.getElementById('search-batch-drivers').value || '').toLowerCase();
        document.querySelectorAll('#store-driver-list .driver-pick-card, #zone-driver-list .driver-pick-card').forEach(card => {
            card.style.display = !q || (card.dataset.search || '').includes(q) ? '' : 'none';
        });
    }

    document.querySelectorAll('.assign-tab').forEach(tab => {
        tab.addEventListener('click', () => setAssignMode(tab.dataset.tab));
    });

    document.getElementById('search-batch-drivers').addEventListener('input', applyDriverSearch);

    document.getElementById('store-driver-list').addEventListener('click', function (e) {
        const card = e.target.closest('.driver-pick-card');
        if (!card) return;
        this.querySelectorAll('.driver-pick-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedStoreId = card.dataset.driverId;
        updateConfirmBtn();
    });

    document.getElementById('zone-driver-list').addEventListener('click', function (e) {
        const card = e.target.closest('.driver-pick-card');
        if (!card) return;
        const id = card.dataset.driverId;
        if (selectedZoneIds.has(id)) {
            selectedZoneIds.delete(id);
            card.classList.remove('selected');
        } else {
            selectedZoneIds.add(id);
            card.classList.add('selected');
        }
        updateConfirmBtn();
    });

    document.getElementById('select-all-zone').addEventListener('click', function () {
        document.querySelectorAll('#zone-driver-list .driver-pick-card').forEach(card => {
            if (card.style.display === 'none') return;
            selectedZoneIds.add(card.dataset.driverId);
            card.classList.add('selected');
        });
        updateConfirmBtn();
    });

    document.getElementById('batch-list').addEventListener('click', function (e) {
        const card = e.target.closest('.batch-card');
        if (!card) return;

        if (e.target.closest('.btn-simulate-accept')) {
            e.stopPropagation();
            card.dataset.status = 'accepted';
            card.dataset.requestPending = '0';
            const chip = card.querySelector('.status-chip');
            if (chip) {
                chip.className = 'status-chip chip-accepted';
                chip.innerHTML = '<span class="dot"></span>Accepted';
            }
            card.querySelector('.request-pending-note')?.remove();
            e.target.closest('.btn-simulate-accept').remove();
            filterBatches();
            return;
        }

        if (e.target.closest('.btn-assign-batch')) {
            currentBatchCard = card;
            currentZoneKey = card.dataset.zoneKey || '';
            currentStoreId = card.dataset.storeId || '';
            currentBatchHub = batchTargetPoint(card);
            document.getElementById('assign-batch-id').textContent = card.dataset.batchId || '';
            document.getElementById('assign-zone-label').textContent = card.dataset.zone || currentZoneKey;
            document.getElementById('search-batch-drivers').value = '';
            setAssignMode('store');
            renderDriverLists();
            assignModal.show();
            return;
        }

        if (e.target.closest('.toggle-batch') || e.target.closest('.batch-summary') || e.target.closest('.btn-view-details')) {
            if (e.target.closest('a') || e.target.closest('.btn-assign-batch')) return;
            card.classList.toggle('expanded');
            if (card.classList.contains('expanded')) {
                setTimeout(() => initBatchDetailMap(card), 80);
            }
        }
    });

    document.getElementById('confirm-batch-assign').addEventListener('click', function () {
        if (!currentBatchCard) return;

        if (assignMode === 'store') {
            const card = document.querySelector(`#store-driver-list .driver-pick-card[data-driver-id="${selectedStoreId}"]`);
            const name = card?.dataset.driverName || 'Driver';
            const avatar = card?.dataset.avatar || '1.png';
            currentBatchCard.dataset.status = 'assigned';
            const chip = currentBatchCard.querySelector('.status-chip');
            if (chip) {
                chip.className = 'status-chip chip-assigned';
                chip.innerHTML = '<span class="dot"></span>Assigned';
            }
            // Show assigned driver in summary if slot exists
            const actions = currentBatchCard.querySelector('.btn-assign-batch')?.parentElement;
            if (actions && !currentBatchCard.querySelector('.assigned-driver-chip')) {
                const chipEl = document.createElement('div');
                chipEl.className = 'd-flex align-items-center gap-2 me-2 assigned-driver-chip';
                chipEl.innerHTML = `
                    <div class="avatar avatar-xs" style="width: 28px; height: 28px;">
                        <img src="${AVATAR_BASE}/${avatar}" class="rounded-circle" alt="">
                    </div>
                    <small class="fw-semibold text-body">${name}</small>`;
                actions.insertBefore(chipEl, actions.firstChild);
            }
            currentBatchCard.querySelector('.btn-assign-batch')?.remove();
        } else {
            // Zone drivers: request sent — keep Waiting (same as order broadcast); accept → Accepted
            const count = selectedZoneIds.size;
            const chip = currentBatchCard.querySelector('.status-chip');
            if (chip) {
                chip.className = 'status-chip chip-waiting';
                chip.innerHTML = '<span class="dot"></span>Waiting';
            }
            // Mark request pending without inventing a "broadcast" status
            currentBatchCard.dataset.requestPending = '1';
            currentBatchCard.dataset.requestCount = String(count);
            currentBatchCard.dataset.status = 'pending';

            let note = currentBatchCard.querySelector('.request-pending-note');
            if (!note) {
                const zoneLine = currentBatchCard.querySelector('small.text-muted');
                if (zoneLine) {
                    note = document.createElement('div');
                    note.className = 'request-pending-note';
                    note.style.cssText = 'font-size: 0.78rem; color: #ff7a00; margin-top: 2px;';
                    zoneLine.parentElement.appendChild(note);
                }
            }
            if (note) {
                note.innerHTML = `<i class="bx bx-broadcast"></i> Request sent to ${count} zone driver${count === 1 ? '' : 's'}`;
            }

            const assignBtn = currentBatchCard.querySelector('.btn-assign-batch');
            if (assignBtn) {
                assignBtn.textContent = 'Simulate Accept';
                assignBtn.classList.remove('btn-assign-batch', 'btn-primary-orange');
                assignBtn.classList.add('btn-simulate-accept', 'btn-outline-secondary');
                assignBtn.title = 'Demo: mark as accepted when a zone driver accepts';
            }
        }

        assignModal.hide();
        filterBatches();
    });

    filterBatches();
});
</script>
@endsection
