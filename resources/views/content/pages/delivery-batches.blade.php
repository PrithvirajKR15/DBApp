@php
$isNavbar = false;
$data = $data ?? [];
$storesWithGroups = $data['stores_with_groups'] ?? [];
$stores = $data['stores'] ?? [];
$batchDrivers = $data['drivers'] ?? [];
$batches = $data['batches'] ?? [];
$pendingCount = count(array_filter($batches, fn ($b) => $b['status'] === 'pending'));
$assignedCount = count(array_filter($batches, fn ($b) => $b['status'] === 'assigned'));
$inProgressCount = count(array_filter($batches, fn ($b) => $b['status'] === 'in_progress'));
$completedCount = count(array_filter($batches, fn ($b) => $b['status'] === 'completed'));
$cancelledCount = count(array_filter($batches, fn ($b) => $b['status'] === 'cancelled'));
$statusMeta = [
    'pending' => ['label' => 'Waiting', 'class' => 'chip-waiting'],
    'assigned' => ['label' => 'Assigned', 'class' => 'chip-assigned'],
    'in_progress' => ['label' => 'In Progress', 'class' => 'chip-out'],
    'completed' => ['label' => 'Completed', 'class' => 'chip-completed'],
    'cancelled' => ['label' => 'Cancelled', 'class' => 'chip-waiting'],
    'open' => ['label' => 'Open', 'class' => 'chip-assigned'],
];
$deliveryChipClass = [
    'Waiting' => 'chip-waiting',
    'Assigned' => 'chip-assigned',
    'Out Delivery' => 'chip-out',
    'Delivered' => 'chip-completed',
];
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
    }
    .btn-primary-orange:hover, .btn-primary-orange:focus {
        background-color: #e06b00 !important;
        border-color: #e06b00 !important;
        color: #ffffff !important;
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
    }
    .store-section, .group-card, .child-batch-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
    }
    .status-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 6px;
        font-size: 0.75rem; font-weight: 600;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .chip-waiting { background: rgba(255,171,0,0.12); color: #ffab00; }
    .chip-waiting .dot { background: #ffab00; }
    .chip-assigned { background: rgba(105,108,255,0.12); color: #696cff; }
    .chip-assigned .dot { background: #696cff; }
    .chip-out { background: rgba(234,84,85,0.12); color: #ea5455; }
    .chip-out .dot { background: #ea5455; }
    .chip-completed { background: rgba(40,199,111,0.12); color: #28c76f; }
    .chip-completed .dot { background: #28c76f; }
    .stop-badge {
        width: 26px; height: 26px; border-radius: 50%;
        background: #32475c; color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700;
    }
    .metric-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.4px; color: #8592a3; font-weight: 600; }
    .metric-value { font-size: 0.95rem; font-weight: 700; color: #32475c; }
    .child-detail { display: none; border-top: 1px solid #f1f3f5; }
    .child-batch-card.expanded .child-detail { display: block; }
    .child-batch-card.expanded .chevron-icon { transform: rotate(180deg); }
    .chevron-icon { transition: transform 0.2s ease; }
    .group-body { display: none; }
    .group-card.expanded .group-body { display: block; }
    .group-card.expanded > .group-header .chevron-icon { transform: rotate(180deg); }
    .driver-pick-card {
        border: 1px solid #e0e2e7; border-radius: 12px; padding: 12px 14px;
        cursor: pointer; transition: all 0.2s ease; background: #fff;
    }
    .driver-pick-card:hover { border-color: #ffb366; }
    .driver-pick-card.selected {
        border-color: #ff7a00; background: rgba(255, 122, 0, 0.03);
        box-shadow: 0 0 0 1px rgba(255, 122, 0, 0.2);
    }
    .driver-pick-check {
        width: 22px; height: 22px; border-radius: 50%; border: 2px solid #d1d5db;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: transparent;
    }
    .driver-pick-card.selected .driver-pick-check { border-color: #ff7a00; background: #ff7a00; color: #fff; }
    .batch-orders-table tbody tr.order-row-draggable { cursor: grab; }
    .batch-orders-table tbody tr.order-row-dragging { opacity: 0.45; }
    .batch-orders-table tbody tr.order-row-drag-over { box-shadow: inset 0 2px 0 #ff7a00; }
    .drag-handle { color: #8592a3; cursor: grab; font-size: 1rem; line-height: 1; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h3 class="mb-1 fw-bold text-body" style="font-size: 1.6rem;">Delivery Batches</h3>
        <p class="mb-0 text-muted" style="font-size: 0.9rem;">Store → parent groups → child routes. Combined map to verify drivers; move orders / reorder stops before delivery starts.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ url('/operations/delivery-batches/settings') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2" style="border-radius: 8px; border-color: #e0e2e7; color: #566a7f;">
            <i class="bx bx-cog"></i><span>Configuration</span>
        </a>
        <a href="{{ url('/operations/delivery-batches/generate') }}" class="btn btn-primary-orange d-flex align-items-center gap-2" style="padding: 10px 18px; border-radius: 8px;">
            <i class="bx bx-refresh"></i><span>Generate Batches</span>
        </a>
    </div>
</div>

<div class="card shadow-none border mb-3" style="border-radius: 12px;">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #e0e2e7 !important; border-radius: 8px !important;">
                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent" id="search-batches" placeholder="Search store, group, batch, driver…" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="filter-store" style="border-radius: 8px; font-size: 0.88rem; height: 38px; border-color: #e0e2e7;">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store['id'] }}">{{ $store['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-7">
                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end" id="batch-tabs">
                    <button type="button" class="btn btn-pill batch-tab active" data-status="active">Active ({{ $pendingCount + $assignedCount + $inProgressCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="assigned">Assigned ({{ $assignedCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="in_progress">In Progress ({{ $inProgressCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="completed">Completed ({{ $completedCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="cancelled">Cancelled ({{ $cancelledCount }})</button>
                    <button type="button" class="btn btn-pill batch-tab" data-status="all">All</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-success d-none mb-3" id="generated-banner" style="border-radius: 10px; font-size: 0.88rem;">
    <i class="bx bx-check-circle me-1"></i>
    <span id="generated-banner-text">Parent batch group created with store drivers assigned.</span>
</div>

<div class="d-flex flex-column gap-3" id="store-list">
@forelse ($storesWithGroups as $storeBlock)
    @php
        $storeGroups = $storeBlock['groups'] ?? [];
    @endphp
    <div class="store-section p-3 store-block"
         data-store-id="{{ $storeBlock['id'] }}"
         data-search="{{ strtolower(($storeBlock['name'] ?? '').' '.($storeBlock['id'] ?? '')) }}">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-0 fw-bold text-body">{{ $storeBlock['name'] }}</h5>
                <small class="text-muted">
                    {{ count($storeGroups) }} parent group{{ count($storeGroups) === 1 ? '' : 's' }}
                    · {{ (int) ($storeBlock['pending'] ?? 0) }} pending orders
                    · {{ (int) ($storeBlock['available_drivers'] ?? 0) }} drivers free
                </small>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
        @foreach ($storeGroups as $group)
            @php
                $groupMeta = $statusMeta[$group['status']] ?? ['label' => ucfirst($group['status']), 'class' => 'chip-waiting'];
                $groupSearch = strtolower(($group['id'] ?? '').' '.($group['store'] ?? ''));
                foreach ($group['batches'] ?? [] as $gb) {
                    $groupSearch .= ' '.strtolower(($gb['id'] ?? '').' '.($gb['driver']['name'] ?? '').' '.($gb['driver']['id'] ?? '').' '.($gb['status'] ?? ''));
                }
            @endphp
            <div class="group-card group-block"
                 data-group-id="{{ $group['id'] }}"
                 data-store-id="{{ $storeBlock['id'] }}"
                 data-statuses="{{ implode(',', collect($group['batches'] ?? [])->pluck('status')->all()) }}"
                 data-search="{{ $groupSearch }}">
                <div class="group-header p-3 d-flex align-items-center justify-content-between gap-2 flex-wrap" style="cursor:pointer;">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <div class="batch-icon" style="width:40px;height:40px;border-radius:10px;background:rgba(255,122,0,0.12);color:#ff7a00;display:flex;align-items:center;justify-content:center;">
                            <i class="bx bx-layer"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="fw-bold text-body text-truncate">{{ $group['id'] }}</div>
                            <small class="text-muted">
                                {{ (int) $group['batch_count'] }} routes · {{ (int) $group['order_count'] }} orders
                                @if (!empty($group['created_at'])) · {{ $group['created_at'] }} @endif
                            </small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="status-chip {{ $groupMeta['class'] }}"><span class="dot"></span>{{ $groupMeta['label'] }}</span>
                        @if ((int) ($group['overflow_count'] ?? 0) > 0)
                            <span class="badge bg-label-warning rounded-pill">{{ (int) $group['overflow_count'] }} left pending</span>
                        @endif
                        <i class="bx bx-chevron-down chevron-icon text-muted"></i>
                    </div>
                </div>
                <div class="group-body px-3 pb-3">
                    @php
                        $groupMapId = 'group-map-' . preg_replace('/[^a-zA-Z0-9]/', '-', $group['id']);
                        $groupHub = collect($group['batches'] ?? [])->firstWhere(fn ($b) => !empty($b['hub']))['hub']
                            ?? null;
                        $groupMapBatches = collect($group['batches'] ?? [])->map(fn ($b) => [
                            'id' => $b['id'],
                            'route_label' => $b['route_label'] ?? $b['zone'],
                            'status' => $b['status'],
                            'editable' => !empty($b['editable']),
                            'orders' => $b['orders'] ?? [],
                            'driver' => $b['driver'] ?? null,
                            'hub' => $b['hub'] ?? $groupHub,
                            'route' => $b['route'] ?? null,
                        ])->values()->all();
                        $siblingEditable = collect($group['batches'] ?? [])
                            ->filter(fn ($b) => !empty($b['editable']))
                            ->map(fn ($b) => [
                                'id' => $b['id'],
                                'label' => ($b['driver']['name'] ?? 'Unassigned').' · '.$b['id'],
                                'driver' => $b['driver']['name'] ?? null,
                            ])->values()->all();
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <h6 class="mb-0 fw-bold text-body"><i class="bx bx-map-alt me-1" style="color:#ff7a00;"></i> Combined group routes</h6>
                                <small class="text-muted">Click a stop number to move that order to another driver. Locked batches stay read-only.</small>
                            </div>
                        </div>
                        <div id="{{ $groupMapId }}"
                             class="border rounded group-combined-map"
                             style="height:360px;border-radius:10px !important;"
                             data-map-payload='@json(['hub' => $groupHub, 'batches' => $groupMapBatches])'></div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                    @foreach ($group['batches'] ?? [] as $batch)
                        @php
                            $meta = $statusMeta[$batch['status']] ?? ['label' => ucfirst((string) $batch['status']), 'class' => 'chip-waiting'];
                            $editable = !empty($batch['editable']);
                            $batchMapId = 'batch-map-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $batch['id']);
                            $batchMapPayload = [
                                'hub' => $batch['hub'] ?? $groupHub,
                                'batch' => [
                                    'id' => $batch['id'],
                                    'route_label' => $batch['route_label'] ?? $batch['zone'],
                                    'orders' => $batch['orders'] ?? [],
                                    'driver' => $batch['driver'] ?? null,
                                    'route' => $batch['route'] ?? null,
                                ],
                            ];
                        @endphp
                        <div class="child-batch-card child-block"
                             data-batch-id="{{ $batch['id'] }}"
                             data-status="{{ $batch['status'] }}"
                             data-store-id="{{ $batch['store_id'] }}"
                             data-group-id="{{ $group['id'] }}"
                             data-editable="{{ $editable ? '1' : '0' }}"
                             data-siblings='@json($siblingEditable)'>
                            <div class="p-3 d-flex align-items-center justify-content-between gap-2 flex-wrap child-header" style="cursor:pointer;">
                                <div class="min-w-0">
                                    <div class="fw-bold text-body">{{ $batch['id'] }} · {{ $batch['route_label'] ?? $batch['zone'] }}</div>
                                    <small class="text-muted">
                                        {{ (int) $batch['stops'] }} stops · {{ $batch['distance'] ?? '—' }} · {{ $batch['est_time'] ?? '—' }}
                                        @if (!empty($batch['driver']))
                                            · {{ $batch['driver']['name'] }}
                                        @endif
                                    </small>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="status-chip {{ $meta['class'] }}"><span class="dot"></span>{{ $meta['label'] }}</span>
                                    @if ($editable)
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-reassign"
                                                data-batch-id="{{ $batch['id'] }}"
                                                data-store-id="{{ $batch['store_id'] }}"
                                                style="border-radius:8px;">
                                            {{ empty($batch['driver']) ? 'Assign Driver' : 'Reassign Driver' }}
                                        </button>
                                    @else
                                        <span class="badge bg-label-secondary rounded-pill">Locked</span>
                                    @endif
                                    <i class="bx bx-chevron-down chevron-icon text-muted"></i>
                                </div>
                            </div>
                            <div class="child-detail p-3">
                                <div class="row g-3">
                                    <div class="col-lg-7">
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0 batch-orders-table">
                                                <thead>
                                                    <tr class="text-muted" style="font-size:0.75rem;">
                                                        <th style="width:90px;">Stop</th>
                                                        <th>Order</th>
                                                        <th>Customer</th>
                                                        <th>Address</th>
                                                        <th>Status</th>
                                                        @if ($editable)
                                                            <th style="width:120px;">Actions</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($batch['orders'] ?? [] as $order)
                                                    <tr style="font-size:0.85rem;"
                                                        data-order-code="{{ $order['id'] }}"
                                                        @if ($editable) class="order-row-draggable" draggable="true" @endif>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-1">
                                                                @if ($editable)
                                                                    <i class="bx bx-menu drag-handle" title="Drag to reorder"></i>
                                                                @endif
                                                                <span class="stop-badge">{{ $order['stop'] }}</span>
                                                                @if ($editable)
                                                                    <div class="btn-group-vertical">
                                                                        <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-stop-up" title="Move stop up" style="line-height:1;"><i class="bx bx-chevron-up"></i></button>
                                                                        <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-stop-down" title="Move stop down" style="line-height:1;"><i class="bx bx-chevron-down"></i></button>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="fw-semibold">{{ $order['id'] }}</td>
                                                        <td>{{ $order['customer'] }}</td>
                                                        <td class="text-muted">{{ $order['locality'] ?? $order['address'] }}</td>
                                                        <td>
                                                            @php $dClass = $deliveryChipClass[$order['delivery'] ?? ''] ?? 'chip-waiting'; @endphp
                                                            <span class="status-chip {{ $dClass }}"><span class="dot"></span>{{ $order['delivery'] ?? '—' }}</span>
                                                        </td>
                                                        @if ($editable)
                                                            <td>
                                                                <button type="button"
                                                                        class="btn btn-sm btn-outline-secondary btn-move-order"
                                                                        data-order-code="{{ $order['id'] }}"
                                                                        data-from-batch="{{ $batch['id'] }}"
                                                                        style="border-radius:8px;font-size:0.75rem;">
                                                                    Move
                                                                </button>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div id="{{ $batchMapId }}" class="border rounded" style="height:240px;border-radius:10px !important;"
                                             data-map-payload='@json($batchMapPayload)'></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    </div>
@empty
    <div class="card shadow-none border" style="border-radius:12px;">
        <div class="card-body text-center py-5 text-muted">
            <i class="bx bx-package" style="font-size:2rem;"></i>
            <div class="mt-2">No parent batch groups yet. Generate batches for a store to get started.</div>
            <a href="{{ url('/operations/delivery-batches/generate') }}" class="btn btn-primary-orange mt-3" style="border-radius:8px;">Generate Batches</a>
        </div>
    </div>
@endforelse
</div>

<!-- Assign / Reassign Modal -->
<div class="modal fade" id="assignDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Assign Store Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:0.88rem;" id="assign-modal-hint">Select an available store driver for this route.</p>
                <div class="d-flex flex-column gap-2" id="assign-driver-list"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                <button type="button" class="btn btn-primary-orange" id="confirm-assign-driver" style="border-radius:8px;" disabled>Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Move Order Modal -->
<div class="modal fade" id="moveOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Move Order to Another Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" style="font-size:0.88rem;" id="move-order-hint">Choose another unlocked batch in this parent group.</p>
                <select class="form-select" id="move-target-batch" style="border-radius:8px;">
                    <option value="">Select target batch…</option>
                </select>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                <button type="button" class="btn btn-primary-orange" id="confirm-move-order" style="border-radius:8px;" disabled>Move Order</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('assets/js/batch-routes-map.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const BATCH_DRIVERS = @json($batchDrivers);
    const AVATAR_BASE = @json(asset('assets/img/avatars'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const DELIVERY_CHIP = @json($deliveryChipClass);
    let assignBatchId = null;
    let assignStoreId = null;
    let selectedDriverCode = null;
    let moveOrderCode = null;
    let moveFromBatch = null;
    const assignModal = new bootstrap.Modal(document.getElementById('assignDriverModal'));
    const moveModal = new bootstrap.Modal(document.getElementById('moveOrderModal'));
    const mapInstances = new Map();

    function notifyError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Unable to continue',
                text: message || 'Something went wrong.',
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-primary-orange px-3 py-2' },
                buttonsStyling: false,
            });
            return;
        }
        window.alert(message);
    }

    function notifySuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: message,
                timer: 1400,
                showConfirmButton: false,
            });
            return;
        }
    }

    function notifyInfo(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Note',
                text: message,
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-primary-orange px-3 py-2' },
                buttonsStyling: false,
            });
            return;
        }
        window.alert(message);
    }

    if (new URLSearchParams(window.location.search).get('generated') === '1') {
        const banner = document.getElementById('generated-banner');
        banner.classList.remove('d-none');
        history.replaceState({}, '', window.location.pathname);
    }

    function findBatchCard(batchId) {
        return [...document.querySelectorAll('.child-batch-card')]
            .find((c) => c.dataset.batchId === batchId) || null;
    }

    function destroyMap(el) {
        if (!el?.id) return;
        const inst = mapInstances.get(el.id);
        if (inst) {
            try { inst.destroy(); } catch (e) { /* ignore */ }
            mapInstances.delete(el.id);
        }
        el.innerHTML = '';
    }

    function refreshMap(el, multi = false) {
        if (!el) return;
        destroyMap(el);
        initMap(el, multi);
        setTimeout(() => mapInstances.get(el.id)?.invalidateSize?.(), 150);
        setTimeout(() => mapInstances.get(el.id)?.invalidateSize?.(), 400);
    }

    function keepViewOn(el) {
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function openMoveModal(orderCode, fromBatchId, groupCard) {
        if (!orderCode || !fromBatchId || !groupCard) return;

        const sourceCard = [...groupCard.querySelectorAll('.child-batch-card')]
            .find((c) => c.dataset.batchId === fromBatchId);
        if (sourceCard && sourceCard.dataset.editable !== '1') {
            notifyInfo('This batch has started delivery and is locked.');
            return;
        }

        const siblings = [...groupCard.querySelectorAll('.child-batch-card')]
            .filter((c) => c.dataset.editable === '1' && c.dataset.batchId !== fromBatchId)
            .map((c) => {
                const title = c.querySelector('.fw-bold.text-body')?.textContent?.trim() || c.dataset.batchId;
                const driver = (c.querySelector('small.text-muted')?.textContent || '').split('·').pop()?.trim();
                return {
                    id: c.dataset.batchId,
                    label: driver ? `${driver} · ${c.dataset.batchId}` : title,
                };
            });

        if (!siblings.length) {
            notifyInfo('No other unlocked driver batches in this group to move to.');
            return;
        }

        moveOrderCode = orderCode;
        moveFromBatch = fromBatchId;
        const select = document.getElementById('move-target-batch');
        select.innerHTML = '<option value="">Select target batch…</option>' +
            siblings.map((s) => `<option value="${s.id}">${s.label}</option>`).join('');
        document.getElementById('confirm-move-order').disabled = true;
        document.getElementById('move-order-hint').textContent =
            `Move ${moveOrderCode} from ${moveFromBatch} to another unlocked driver batch in this group.`;
        moveModal.show();
    }

    function initMap(el, multi = false) {
        if (!el || !window.DeliverEaseBatchMap || mapInstances.has(el.id)) return;
        try {
            const payload = JSON.parse(el.dataset.mapPayload || '{}');
            const groupCard = el.closest('.group-card');
            const map = window.DeliverEaseBatchMap.createBatchRoutesMap(el.id, {
                hub: payload.hub,
                batches: multi ? (payload.batches || []) : (payload.batch ? [payload.batch] : []),
                height: multi ? 360 : 240,
                showDriverOnFirst: true,
                legendTitle: multi ? 'Drivers' : 'Route',
                interactiveOrders: multi,
                onOrderClick(order, batch) {
                    if (!multi || !order?.id || !batch?.id) return;
                    openMoveModal(order.id, batch.id, groupCard);
                },
            });
            mapInstances.set(el.id, map);
            setTimeout(() => map?.invalidateSize?.(), 200);
        } catch (err) { /* ignore */ }
    }

    function orderRowHtml(order, editable, batchId) {
        const dClass = DELIVERY_CHIP[order.delivery] || 'chip-waiting';
        const stopControls = editable ? `
            <i class="bx bx-menu drag-handle" title="Drag to reorder"></i>
            <span class="stop-badge">${order.stop}</span>
            <div class="btn-group-vertical">
                <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-stop-up" title="Move stop up" style="line-height:1;"><i class="bx bx-chevron-up"></i></button>
                <button type="button" class="btn btn-sm btn-link p-0 text-muted btn-stop-down" title="Move stop down" style="line-height:1;"><i class="bx bx-chevron-down"></i></button>
            </div>` : `<span class="stop-badge">${order.stop}</span>`;
        const moveBtn = editable ? `
            <td>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-move-order"
                    data-order-code="${order.id}" data-from-batch="${batchId}"
                    style="border-radius:8px;font-size:0.75rem;">Move</button>
            </td>` : '';

        return `<tr style="font-size:0.85rem;" data-order-code="${order.id}"
            ${editable ? 'class="order-row-draggable" draggable="true"' : ''}>
            <td>
                <div class="d-flex align-items-center gap-1">
                    ${stopControls}
                </div>
            </td>
            <td class="fw-semibold">${order.id}</td>
            <td>${order.customer || ''}</td>
            <td class="text-muted">${order.locality || order.address || ''}</td>
            <td><span class="status-chip ${dClass}"><span class="dot"></span>${order.delivery || '—'}</span></td>
            ${moveBtn}
        </tr>`;
    }

    function applyBatchToCard(batch) {
        const card = findBatchCard(batch.id);
        if (!card || !batch) return card;

        const editable = !!batch.editable;
        card.dataset.editable = editable ? '1' : '0';
        card.dataset.status = batch.status || card.dataset.status;

        const title = card.querySelector('.child-header .fw-bold.text-body');
        if (title) {
            title.textContent = `${batch.id} · ${batch.route_label || batch.zone || ''}`.trim();
        }
        const meta = card.querySelector('.child-header small.text-muted');
        if (meta) {
            const driverBit = batch.driver?.name ? ` · ${batch.driver.name}` : '';
            meta.textContent = `${batch.stops || 0} stops · ${batch.distance || '—'} · ${batch.est_time || '—'}${driverBit}`;
        }

        const reassignBtn = card.querySelector('.btn-reassign');
        const lockedBadge = card.querySelector('.badge.bg-label-secondary');
        if (editable) {
            if (reassignBtn) {
                reassignBtn.textContent = batch.driver ? 'Reassign Driver' : 'Assign Driver';
            }
            lockedBadge?.remove();
        }

        const tbody = card.querySelector('.batch-orders-table tbody');
        if (tbody) {
            const orders = [...(batch.orders || [])].sort((a, b) => (a.stop || 0) - (b.stop || 0));
            tbody.innerHTML = orders
                .map((o) => orderRowHtml(o, editable, batch.id))
                .join('');
        }

        const mapEl = card.querySelector('[id^="batch-map-"]');
        if (mapEl) {
            const hub = batch.hub || JSON.parse(mapEl.dataset.mapPayload || '{}').hub || null;
            const orders = [...(batch.orders || [])].sort((a, b) => (a.stop || 0) - (b.stop || 0));
            mapEl.dataset.mapPayload = JSON.stringify({
                hub,
                batch: {
                    id: batch.id,
                    route_label: batch.route_label || batch.zone,
                    orders,
                    driver: batch.driver || null,
                    route: batch.route || null,
                    editable,
                    status: batch.status,
                },
            });
            if (card.classList.contains('expanded')) {
                refreshMap(mapEl, false);
            }
        }

        return card;
    }

    function rebuildGroupCombinedMap(groupCard) {
        if (!groupCard) return;
        const mapEl = groupCard.querySelector('.group-combined-map');
        if (!mapEl) return;

        const prev = JSON.parse(mapEl.dataset.mapPayload || '{}');
        const batches = [...groupCard.querySelectorAll('.child-batch-card')].map((card) => {
            const childMap = card.querySelector('[id^="batch-map-"]');
            const payload = JSON.parse(childMap?.dataset.mapPayload || '{}');
            const batch = payload.batch || { id: card.dataset.batchId, orders: [] };
            return {
                ...batch,
                editable: card.dataset.editable === '1',
                status: card.dataset.status,
                hub: payload.hub || prev.hub || null,
            };
        });

        mapEl.dataset.mapPayload = JSON.stringify({
            hub: prev.hub || batches.find((b) => b.hub)?.hub || null,
            batches,
        });

        if (groupCard.classList.contains('expanded')) {
            refreshMap(mapEl, true);
        }
    }

    document.querySelectorAll('.group-header').forEach(header => {
        header.addEventListener('click', () => {
            const card = header.closest('.group-card');
            card?.classList.toggle('expanded');
            if (card?.classList.contains('expanded')) {
                const mapEl = card.querySelector('.group-combined-map');
                initMap(mapEl, true);
            }
        });
    });

    document.querySelectorAll('.child-header').forEach(header => {
        header.addEventListener('click', (e) => {
            if (e.target.closest('.btn-reassign')) return;
            const card = header.closest('.child-batch-card');
            card?.classList.toggle('expanded');
            if (card?.classList.contains('expanded')) {
                initMap(card.querySelector('[id^="batch-map-"]'), false);
            }
        });
    });

    function matchesStatusFilter(statusesCsv, filter) {
        const statuses = (statusesCsv || '').split(',').filter(Boolean);
        if (filter === 'all') return true;
        if (filter === 'active') {
            return statuses.some(s => ['pending', 'assigned', 'in_progress'].includes(s));
        }
        return statuses.includes(filter);
    }

    function applyFilters() {
        const q = (document.getElementById('search-batches').value || '').toLowerCase();
        const storeId = document.getElementById('filter-store').value;
        const status = document.querySelector('.batch-tab.active')?.dataset.status || 'active';

        document.querySelectorAll('.store-block').forEach(storeEl => {
            let anyGroupVisible = false;
            storeEl.querySelectorAll('.group-block').forEach(groupEl => {
                const matchStore = !storeId || groupEl.dataset.storeId === storeId;
                const matchStatus = matchesStatusFilter(groupEl.dataset.statuses, status);
                const matchQ = !q || (groupEl.dataset.search || '').includes(q) || (storeEl.dataset.search || '').includes(q);
                const show = matchStore && matchStatus && matchQ;
                groupEl.style.display = show ? '' : 'none';
                if (show) anyGroupVisible = true;
            });
            const storeMatch = !storeId || storeEl.dataset.storeId === storeId;
            storeEl.style.display = storeMatch && anyGroupVisible ? '' : 'none';
        });
    }

    document.getElementById('search-batches').addEventListener('input', applyFilters);
    document.getElementById('filter-store').addEventListener('change', applyFilters);
    document.querySelectorAll('.batch-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.batch-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            applyFilters();
        });
    });
    applyFilters();

    const firstGroup = document.querySelector('.group-card');
    if (firstGroup) {
        firstGroup.classList.add('expanded');
        initMap(firstGroup.querySelector('.group-combined-map'), true);
    }

    document.querySelectorAll('.btn-reassign').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            assignBatchId = btn.dataset.batchId;
            assignStoreId = btn.dataset.storeId;
            selectedDriverCode = null;
            document.getElementById('confirm-assign-driver').disabled = true;

            const drivers = BATCH_DRIVERS.filter(d =>
                d.store_id === assignStoreId &&
                (d.available === true || d.status === 'available')
            );

            const list = document.getElementById('assign-driver-list');
            if (!drivers.length) {
                list.innerHTML = '<div class="text-muted">No available store drivers for this store.</div>';
            } else {
                list.innerHTML = drivers.map(d => `
                    <div class="driver-pick-card d-flex align-items-center gap-2" data-driver-code="${d.id}">
                        <div class="driver-pick-check"><i class="bx bx-check" style="font-size:0.85rem;"></i></div>
                        <img src="${AVATAR_BASE}/${d.avatar || '1.png'}" class="rounded-circle" width="36" height="36" alt="">
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-body text-truncate">${d.name}</div>
                            <small class="text-muted">${d.vehicle || 'Vehicle'} · ${d.id}</small>
                        </div>
                    </div>
                `).join('');
                list.querySelectorAll('.driver-pick-card').forEach(card => {
                    card.addEventListener('click', () => {
                        list.querySelectorAll('.driver-pick-card').forEach(c => c.classList.remove('selected'));
                        card.classList.add('selected');
                        selectedDriverCode = card.dataset.driverCode;
                        document.getElementById('confirm-assign-driver').disabled = false;
                    });
                });
            }

            document.getElementById('assign-modal-hint').textContent =
                `Reassign ${assignBatchId} to another available store driver.`;
            assignModal.show();
        });
    });

    document.getElementById('confirm-assign-driver').addEventListener('click', async function () {
        if (!assignBatchId || !selectedDriverCode) return;
        const btn = this;
        btn.disabled = true;
        try {
            const res = await fetch(`{{ url('/operations/delivery-batches') }}/${encodeURIComponent(assignBatchId)}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ driver_code: selectedDriverCode }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Assign failed.');
            }
            assignModal.hide();
            const card = applyBatchToCard(data.batch);
            const groupCard = card?.closest('.group-card');
            rebuildGroupCombinedMap(groupCard);
            keepViewOn(card || groupCard);
            notifySuccess(data.message || 'Driver assigned.');
        } catch (err) {
            notifyError(err.message || 'Assign failed.');
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('store-list').addEventListener('click', (e) => {
        const moveBtn = e.target.closest('.btn-move-order');
        if (moveBtn) {
            e.stopPropagation();
            const groupCard = moveBtn.closest('.group-card');
            openMoveModal(moveBtn.dataset.orderCode, moveBtn.dataset.fromBatch, groupCard);
            return;
        }

        const stopBtn = e.target.closest('.btn-stop-up, .btn-stop-down');
        if (!stopBtn) return;
        e.stopPropagation();

        const row = stopBtn.closest('tr');
        const tbody = row?.parentElement;
        const batchCard = stopBtn.closest('.child-batch-card');
        if (!row || !tbody || !batchCard) return;

        if (stopBtn.classList.contains('btn-stop-up') && row.previousElementSibling) {
            tbody.insertBefore(row, row.previousElementSibling);
        } else if (stopBtn.classList.contains('btn-stop-down') && row.nextElementSibling) {
            tbody.insertBefore(row.nextElementSibling, row);
        } else {
            return;
        }

        persistStopOrder(batchCard).catch((err) => {
            notifyError(err.message || 'Reorder failed.');
        });
    });

    // Drag-and-drop stop reordering within an editable batch.
    let dragRow = null;
    const storeList = document.getElementById('store-list');

    storeList.addEventListener('dragstart', (e) => {
        const row = e.target.closest('tr.order-row-draggable');
        if (!row || row.closest('.child-batch-card')?.dataset.editable !== '1') return;
        // Don't start a drag from action buttons.
        if (e.target.closest('button, a, .btn-move-order')) {
            e.preventDefault();
            return;
        }
        dragRow = row;
        row.classList.add('order-row-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.dataset.orderCode || '');
    });

    storeList.addEventListener('dragover', (e) => {
        const row = e.target.closest('tr.order-row-draggable');
        if (!dragRow || !row || row === dragRow) return;
        if (row.parentElement !== dragRow.parentElement) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        row.classList.add('order-row-drag-over');
        const rect = row.getBoundingClientRect();
        const before = (e.clientY - rect.top) < rect.height / 2;
        if (before) {
            row.parentElement.insertBefore(dragRow, row);
        } else {
            row.parentElement.insertBefore(dragRow, row.nextElementSibling);
        }
    });

    storeList.addEventListener('dragleave', (e) => {
        const row = e.target.closest('tr.order-row-draggable');
        row?.classList.remove('order-row-drag-over');
    });

    storeList.addEventListener('drop', (e) => {
        e.preventDefault();
        const row = e.target.closest('tr.order-row-draggable');
        row?.classList.remove('order-row-drag-over');
    });

    storeList.addEventListener('dragend', (e) => {
        const row = dragRow || e.target.closest('tr.order-row-draggable');
        document.querySelectorAll('.order-row-drag-over').forEach((r) => r.classList.remove('order-row-drag-over'));
        row?.classList.remove('order-row-dragging');
        const batchCard = row?.closest('.child-batch-card');
        dragRow = null;
        if (!batchCard) return;
        persistStopOrder(batchCard).catch((err) => {
            notifyError(err.message || 'Reorder failed.');
        });
    });

    document.getElementById('move-target-batch').addEventListener('change', function () {
        document.getElementById('confirm-move-order').disabled = !this.value;
    });

    document.getElementById('confirm-move-order').addEventListener('click', async function () {
        const toBatch = document.getElementById('move-target-batch').value;
        if (!moveOrderCode || !moveFromBatch || !toBatch) return;
        const btn = this;
        btn.disabled = true;
        try {
            const res = await fetch(@json(url('/operations/delivery-batches/move-order')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    order_code: moveOrderCode,
                    from_batch: moveFromBatch,
                    to_batch: toBatch,
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Move failed.');
            }
            moveModal.hide();

            const fromCard = applyBatchToCard(data.from);
            const toCard = applyBatchToCard(data.to);
            // Keep both child details open so admin can verify.
            fromCard?.classList.add('expanded');
            toCard?.classList.add('expanded');
            if (fromCard) initMap(fromCard.querySelector('[id^="batch-map-"]'), false);
            if (toCard) initMap(toCard.querySelector('[id^="batch-map-"]'), false);

            const groupCard = fromCard?.closest('.group-card') || toCard?.closest('.group-card');
            groupCard?.classList.add('expanded');
            rebuildGroupCombinedMap(groupCard);
            keepViewOn(toCard || groupCard);
            notifySuccess(data.message || 'Order moved.');
        } catch (err) {
            notifyError(err.message || 'Move failed.');
        } finally {
            btn.disabled = false;
        }
    });

    async function persistStopOrder(batchCard) {
        const batchId = batchCard.dataset.batchId;
        const codes = [...batchCard.querySelectorAll('tbody tr[data-order-code]')]
            .map(row => row.dataset.orderCode);
        const res = await fetch(`{{ url('/operations/delivery-batches') }}/${encodeURIComponent(batchId)}/reorder-stops`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify({ order_codes: codes }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Reorder failed.');
        }

        const card = applyBatchToCard(data.batch);
        card?.classList.add('expanded');
        const groupCard = card?.closest('.group-card');
        groupCard?.classList.add('expanded');
        rebuildGroupCombinedMap(groupCard);
        keepViewOn(card || groupCard);
        notifySuccess(data.message || 'Stop sequence updated.');
    }
});
</script>
@endsection
