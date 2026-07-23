@php
$isNavbar = false;
$data = $data ?? [];
$stores = $data['stores'] ?? [];
$settings = $data['settings'] ?? [];
$pendingOrders = $data['pending_orders'] ?? [];
$batchDrivers = $data['drivers'] ?? [];
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Generate Delivery Batch')
@section('page-title', 'Generate Delivery Batch')

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
    .btn-primary-orange:hover {
        background-color: #e06b00 !important;
        border-color: #e06b00 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.2) !important;
    }
    .btn-primary-orange:disabled {
        background-color: #ffb366 !important;
        border-color: #ffb366 !important;
        opacity: 0.75;
    }
    .stepper {
        display: flex;
        align-items: center;
        gap: 0;
        max-width: 640px;
    }
    .step-item {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }
    .step-num {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem;
        background: #eef0f3; color: #8592a3;
        flex-shrink: 0;
    }
    .step-item.active .step-num,
    .step-item.done .step-num {
        background: #ff7a00; color: #fff;
    }
    .step-label { font-size: 0.82rem; font-weight: 600; color: #8592a3; }
    .step-item.active .step-label,
    .step-item.done .step-label { color: #32475c; }
    .step-line {
        height: 2px; flex: 0 0 28px;
        background: #e0e2e7; margin: 0 6px;
    }
    .step-line.done { background: #ff7a00; }

    .store-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        height: 100%;
    }
    .store-card:hover { border-color: #ffb366; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
    .store-card.selected {
        border-color: #ff7a00;
        box-shadow: 0 0 0 1px rgba(255,122,0,0.25);
        background: rgba(255,122,0,0.02);
    }
    .store-card.offline {
        opacity: 0.55;
        cursor: not-allowed;
        pointer-events: none;
    }
    .store-avatar {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 1rem;
    }
    .store-radio {
        width: 20px; height: 20px; border-radius: 50%;
        border: 2px solid #d1d5db;
        display: flex; align-items: center; justify-content: center;
    }
    .store-card.selected .store-radio {
        border-color: #ff7a00; background: #ff7a00; color: #fff;
    }
    .status-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 20px;
        font-size: 0.75rem; font-weight: 600;
    }
    .status-pill .dot { width: 6px; height: 6px; border-radius: 50%; }
    .status-active { background: rgba(40,199,111,0.12); color: #28c76f; }
    .status-active .dot { background: #28c76f; }
    .status-high_load { background: rgba(255,171,0,0.12); color: #ffab00; }
    .status-high_load .dot { background: #ffab00; }
    .status-offline { background: rgba(133,146,163,0.12); color: #8592a3; }
    .status-offline .dot { background: #8592a3; }
    .config-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
        padding: 20px;
    }
    .preview-batch-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
        padding: 16px;
    }
    .preview-batch-card.border-highlight {
        border-color: rgba(255,122,0,0.4);
        background: rgba(255,122,0,0.02);
    }
    .route-seq {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
        font-size: 0.82rem;
    }
    .route-seq .hub { color: #ff7a00; font-weight: 700; }
    .route-seq .arrow { color: #cbd5e1; }
    .route-seq .stop { background: #f1f3f5; padding: 2px 8px; border-radius: 6px; }
    .border-badge {
        font-size: 0.7rem;
        background: rgba(0,207,232,0.12);
        color: #00a0b8;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
    }
    .gen-spinner {
        width: 18px; height: 18px;
        border: 2px solid rgba(255,255,255,0.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        display: inline-block;
        vertical-align: middle;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ url('/operations/delivery-batches') }}" class="text-muted" style="font-size: 1.25rem;"><i class="bx bx-arrow-back"></i></a>
            <h3 class="mb-0 fw-bold text-body" style="font-size: 1.6rem; font-family: 'Public Sans', sans-serif;">Generate Delivery Batch</h3>
        </div>
        <p class="mb-0 text-muted ms-4" style="font-size: 0.9rem;">Group nearby orders by road distance and optimize delivery routes — not fixed zones</p>
    </div>
    <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.85rem;">
        <span><i class="bx bx-time-five"></i> Slot: {{ $settings['slot_window'] }}</span>
        <span><i class="bx bx-map"></i> Distance-based routing</span>
    </div>
</div>

<!-- Stepper -->
<div class="stepper mb-4" id="wizard-stepper">
    <div class="step-item active" data-step="1">
        <div class="step-num">1</div>
        <div class="step-label">Select Store</div>
    </div>
    <div class="step-line" id="step-line-1"></div>
    <div class="step-item" data-step="2">
        <div class="step-num">2</div>
        <div class="step-label">Configure</div>
    </div>
    <div class="step-line" id="step-line-2"></div>
    <div class="step-item" data-step="3">
        <div class="step-num">3</div>
        <div class="step-label">Review Routes</div>
    </div>
    <div class="step-line" id="step-line-3"></div>
    <div class="step-item" data-step="4">
        <div class="step-num">4</div>
        <div class="step-label">Assign Drivers</div>
    </div>
</div>

<!-- Step 1: Select Store -->
<div id="step-1">
    <div class="mb-3">
        <h5 class="fw-bold text-body mb-1">Choose a Store to Generate Batches For</h5>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">All pending orders from this hub will be grouped into optimized routes based on proximity and travel distance.</p>
    </div>

    <div class="card shadow-none border mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-md-8">
                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #e0e2e7 !important; border-radius: 8px !important;">
                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted"></i></span>
                        <input type="text" class="form-control border-0 bg-transparent" id="search-stores" placeholder="Search stores by name or location..." style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <select class="form-select" id="filter-region" style="border-radius: 8px; height: 38px; border-color: #e0e2e7; font-size: 0.88rem;">
                        <option value="">All Regions</option>
                        <option>Pattom</option>
                        <option>Kowdiar</option>
                        <option>Medical College</option>
                        <option>East Fort</option>
                        <option>Technopark</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3" id="store-grid">
        @foreach ($stores as $store)
        @php
            $statusClass = 'status-' . $store['status'];
            $statusLabel = $store['status'] === 'high_load' ? 'High Load' : ucfirst($store['status']);
            $initial = strtoupper(substr($store['name'], 0, 1));
        @endphp
        <div class="col-12 col-md-6 col-xl-4 store-col"
             data-search="{{ strtolower($store['name'].' '.$store['zone'].' '.$store['branch']) }}"
             data-region="{{ $store['zone'] }}">
            <div class="store-card {{ $store['status'] === 'offline' ? 'offline' : '' }}"
                 data-store-id="{{ $store['id'] }}"
                 data-pending="{{ $store['pending'] }}"
             data-drivers="{{ $store['drivers'] }}"
             data-available-drivers="{{ $store['available_drivers'] ?? 0 }}"
             data-lat="{{ $store['lat'] ?? '' }}"
             data-lng="{{ $store['lng'] ?? '' }}">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="store-avatar" style="background: {{ $store['color'] }};">{{ $initial }}</div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-body">{{ $store['name'] }}</div>
                        <small class="text-muted">{{ $store['zone'] }} · {{ $store['branch'] }}</small>
                    </div>
                    <div class="store-radio"><i class="bx bx-check" style="font-size: 0.75rem;"></i></div>
                </div>
                <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                        <div class="text-muted" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Pending</div>
                        <div class="fw-bold text-body" style="font-size: 1.2rem;">{{ $store['pending'] }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Available</div>
                        <div class="fw-bold text-body" style="font-size: 1.2rem;">{{ $store['available_drivers'] ?? 0 }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Est. Batches</div>
                        <div class="fw-bold text-body" style="font-size: 1.2rem;">{{ $store['est_batches'] ? '~'.$store['est_batches'] : '0' }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="status-pill {{ $statusClass }}"><span class="dot"></span>{{ $statusLabel }}</span>
                    <small class="text-muted">
                        @if ($store['slot'])
                            Slot: {{ $store['slot'] }}
                        @else
                            No orders
                        @endif
                    </small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Step 2: Configure -->
<div id="step-2" class="d-none">
    <div class="mb-3">
        <h5 class="fw-bold text-body mb-1">Configure Route Constraints</h5>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Set capacity and distance limits. The engine groups nearby orders and optimizes stop sequence — zones are used for reporting only.</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="config-card mb-3">
                <h6 class="fw-bold text-body mb-3">Batch Configuration</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Orders per Batch</label>
                        <input type="number" class="form-control" id="cfg-orders-per-batch" value="{{ $settings['orders_per_batch'] }}" min="1" max="30" style="border-radius: 8px;">
                        <small class="text-muted">Maximum orders per optimized route</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Max Route Distance (km)</label>
                        <input type="number" class="form-control" id="cfg-max-distance" value="{{ $settings['max_distance_km'] }}" min="1" style="border-radius: 8px;">
                        <small class="text-muted">Total hub → stops → hub distance cap</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Max Route Duration (min)</label>
                        <input type="number" class="form-control" id="cfg-max-duration" value="{{ $settings['max_route_minutes'] ?? 45 }}" min="10" style="border-radius: 8px;">
                        <small class="text-muted">Estimated driving time limit per batch</small>
                    </div>
                </div>
                <div class="alert mt-3 mb-0" style="background: rgba(105,108,255,0.06); border-color: rgba(105,108,255,0.18); color: #566a7f; border-radius: 10px; font-size: 0.85rem;">
                    <i class="bx bx-user-check" style="color: #696cff;"></i>
                    Batches are assigned directly to <strong>store drivers</strong> — once assigned, the driver must deliver the route. No acceptance window needed.
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="config-card h-100">
                <h6 class="fw-bold text-body mb-3">How It Works</h6>
                <div class="d-flex flex-column gap-2 mb-3" style="font-size: 0.85rem;">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="badge bg-label-warning rounded-pill">1</span>
                        <span>Find nearest unassigned orders using road distance from the store hub</span>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <span class="badge bg-label-warning rounded-pill">2</span>
                        <span>Build batches by lowest extra travel cost — border orders go to the most efficient route</span>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <span class="badge bg-label-warning rounded-pill">3</span>
                        <span>Optimize stop sequence within each batch (nearest-neighbor routing)</span>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <span class="badge bg-label-warning rounded-pill">4</span>
                        <span>Suggest the nearest available store driver for direct assignment</span>
                    </div>
                </div>
                <div class="alert mb-0" style="background: rgba(255,122,0,0.08); border-color: rgba(255,122,0,0.2); color: #566a7f; border-radius: 10px; font-size: 0.85rem;">
                    <i class="bx bx-info-circle" style="color: #ff7a00;"></i>
                    Example: Kuravankonam orders near the Zone A/B boundary are assigned to whichever batch adds the least extra distance — not by zone label alone.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step 3: Review -->
<div id="step-3" class="d-none">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-body mb-1">Review Generated Batches</h5>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="preview-summary">—</p>
        </div>
        <div class="d-flex gap-3 text-muted" style="font-size: 0.85rem;">
            <span><i class="bx bx-package"></i> <strong id="preview-batch-count">0</strong> batches</span>
            <span><i class="bx bx-map-pin"></i> <strong id="preview-order-count">0</strong> orders</span>
            <span><i class="bx bx-trip"></i> <strong id="preview-total-dist">0 km</strong></span>
        </div>
    </div>

    <div class="card shadow-none border mb-4" style="border-radius: 14px; overflow: hidden;">
        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3 px-3">
            <h6 class="mb-0 fw-bold text-body"><i class="bx bx-map-alt me-1" style="color:#ff7a00;"></i> Route Overview</h6>
            <a href="#" id="preview-open-gmaps" target="_blank" rel="noopener" class="text-decoration-none d-none" style="color:#ff7a00;font-size:0.85rem;font-weight:600;">
                Open in Google Maps <i class="bx bx-link-external"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div id="batch-routes-map" style="height: 460px;"></div>
        </div>
    </div>

    <h6 class="fw-bold text-body mb-3">Batch Details</h6>
    <div class="d-flex flex-column gap-3" id="preview-batch-list"></div>
    <div class="alert alert-warning d-none mt-3 mb-0" id="overflow-alert" style="border-radius: 10px; font-size: 0.88rem;">
        <i class="bx bx-info-circle me-1"></i>
        <span id="overflow-alert-text"></span>
    </div>
</div>

<!-- Step 4: Assign Drivers -->
<div id="step-4" class="d-none">
    <div class="mb-3">
        <h5 class="fw-bold text-body mb-1">Assign Store Drivers</h5>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Pick one available store driver for each child batch. Each driver can only take one batch in this group.</p>
    </div>
    <div class="d-flex flex-column gap-3" id="assign-batch-list"></div>
</div>

<!-- Footer -->
<div class="d-flex align-items-center justify-content-between mt-4 pt-3 border-top flex-wrap gap-3">
    <div class="text-muted" style="font-size: 0.85rem;" id="footer-hint">
        <i class="bx bx-info-circle"></i> Select a store with active pending orders to proceed.
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/operations/delivery-batches') }}" class="btn btn-outline-secondary" style="border-radius: 8px;">Cancel</a>
        <button type="button" class="btn btn-outline-secondary d-none" id="btn-back" style="border-radius: 8px;">Back</button>
        <button type="button" class="btn btn-primary-orange" id="btn-continue" disabled style="border-radius: 8px;">
            Continue <i class="bx bx-right-arrow-alt"></i>
        </button>
        <button type="button" class="btn btn-primary-orange d-none" id="btn-generate" style="border-radius: 8px;">
            <i class="bx bx-route me-1"></i>Generate Routes
        </button>
        <button type="button" class="btn btn-primary-orange d-none" id="btn-to-assign" style="border-radius: 8px;">
            Assign Drivers <i class="bx bx-right-arrow-alt"></i>
        </button>
        <button type="button" class="btn btn-primary-orange d-none" id="btn-confirm" disabled style="border-radius: 8px;">
            <i class="bx bx-check me-1"></i>Confirm & Save Group
        </button>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('assets/js/batch-generation.js') }}"></script>
<script src="{{ asset('assets/js/batch-routes-map.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const STORES = @json($stores);
    const PENDING_ORDERS = @json($pendingOrders);
    const BATCH_DRIVERS = @json($batchDrivers);
    const DEFAULT_SETTINGS = @json($settings);

    let step = 1;
    let selectedStore = null;
    let generatedBatches = [];
    let overflowCount = 0;
    let previewMap = null;

    function loadSavedSettings() {
        if (DEFAULT_SETTINGS.orders_per_batch) document.getElementById('cfg-orders-per-batch').value = DEFAULT_SETTINGS.orders_per_batch;
        if (DEFAULT_SETTINGS.max_distance_km) document.getElementById('cfg-max-distance').value = DEFAULT_SETTINGS.max_distance_km;
        if (DEFAULT_SETTINGS.max_route_minutes) document.getElementById('cfg-max-duration').value = DEFAULT_SETTINGS.max_route_minutes;
    }
    loadSavedSettings();

    document.getElementById('store-grid').addEventListener('click', function (e) {
        const card = e.target.closest('.store-card:not(.offline)');
        if (!card) return;
        document.querySelectorAll('.store-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const storeId = card.dataset.storeId;
        const storeData = STORES.find(s => s.id === storeId);
        selectedStore = {
            id: storeId,
            pending: parseInt(card.dataset.pending, 10),
            drivers: parseInt(card.dataset.drivers, 10),
            availableDrivers: parseInt(card.dataset.availableDrivers || '0', 10),
            name: card.querySelector('.fw-bold').textContent,
            meta: card.querySelector('small.text-muted').textContent,
            color: card.querySelector('.store-avatar').style.background,
            initial: card.querySelector('.store-avatar').textContent,
            est: card.querySelectorAll('.fw-bold.text-body')[2]?.textContent || '~0',
            lat: parseFloat(card.dataset.lat),
            lng: parseFloat(card.dataset.lng),
            storeData,
        };
        document.getElementById('btn-continue').disabled = selectedStore.availableDrivers < 1 || selectedStore.pending < 1;
    });

    function filterStores() {
        const q = (document.getElementById('search-stores').value || '').toLowerCase();
        const region = document.getElementById('filter-region').value;
        document.querySelectorAll('.store-col').forEach(col => {
            const matchQ = !q || (col.dataset.search || '').includes(q);
            const matchR = !region || col.dataset.region === region;
            col.style.display = matchQ && matchR ? '' : 'none';
        });
    }
    document.getElementById('search-stores').addEventListener('input', filterStores);
    document.getElementById('filter-region').addEventListener('change', filterStores);

    function getConfig() {
        return {
            ordersPerBatch: parseInt(document.getElementById('cfg-orders-per-batch').value, 10) || 5,
            maxDistanceKm: parseFloat(document.getElementById('cfg-max-distance').value) || 10,
            maxRouteMinutes: parseInt(document.getElementById('cfg-max-duration').value, 10) || 45,
            maxBatches: selectedStore ? selectedStore.availableDrivers : 0,
            preferStoreDrivers: true,
            autoFallbackZone: false,
        };
    }

    function availableDriversForStore() {
        if (!selectedStore) return [];
        return BATCH_DRIVERS.filter(d =>
            d.store_id === selectedStore.id &&
            (d.available === true || d.status === 'available')
        );
    }

    function buildRouteSequenceHtml(orders) {
        const stops = orders.map(o => `<span class="stop">${o.locality || o.address.split(',')[0]}</span>`).join('<span class="arrow">→</span>');
        return `<span class="hub">Store</span><span class="arrow">→</span>${stops}<span class="arrow">→</span><span class="hub">Store</span>`;
    }

    function renderPreview(batches) {
        const list = document.getElementById('preview-batch-list');
        const totalOrders = batches.reduce((s, b) => s + b.stops, 0);
        const totalDist = batches.reduce((s, b) => s + (b.distance_km || 0), 0);

        document.getElementById('preview-summary').textContent =
            `${batches.length} route${batches.length === 1 ? '' : 's'} for ${selectedStore.name} (capped by ${selectedStore.availableDrivers} available driver${selectedStore.availableDrivers === 1 ? '' : 's'})`;
        document.getElementById('preview-batch-count').textContent = batches.length;
        document.getElementById('preview-order-count').textContent = totalOrders;
        document.getElementById('preview-total-dist').textContent = totalDist.toFixed(1) + ' km';

        const overflowAlert = document.getElementById('overflow-alert');
        if (overflowCount > 0) {
            document.getElementById('overflow-alert-text').textContent =
                `${overflowCount} order${overflowCount === 1 ? '' : 's'} remain pending for admin to assign later (not included in this parent batch).`;
            overflowAlert.classList.remove('d-none');
        } else {
            overflowAlert.classList.add('d-none');
        }

        list.innerHTML = batches.map((batch, idx) => {
            const hasBorder = batch.border_orders && batch.border_orders.length > 0;
            return `
            <div class="preview-batch-card ${hasBorder ? 'border-highlight' : ''}" data-preview-batch="${idx}">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                    <div>
                        <div class="fw-bold text-body">Batch ${idx + 1} · ${batch.id}</div>
                        <small class="text-muted">${batch.stops} stops · ${batch.distance} · ${batch.est_time}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-body">$${batch.value.toFixed(2)}</div>
                        ${hasBorder ? '<span class="border-badge"><i class="bx bx-transfer"></i> Border order optimized</span>' : ''}
                    </div>
                </div>
                <div class="route-seq mb-2">${buildRouteSequenceHtml(batch.orders)}</div>
                <div class="d-flex flex-wrap gap-3" style="font-size: 0.82rem;">
                    <span class="text-muted"><i class="bx bx-trip"></i> Hub → Stop 1: <strong>${batch.route.hub_to_first}</strong></span>
                    <span class="text-muted"><i class="bx bx-undo"></i> Return: <strong>${batch.route.return}</strong></span>
                </div>
            </div>`;
        }).join('');

        if (previewMap) previewMap.destroy();
        if (window.DeliverEaseBatchMap && selectedStore) {
            previewMap = window.DeliverEaseBatchMap.createBatchRoutesMap('batch-routes-map', {
                hub: { lat: selectedStore.lat, lng: selectedStore.lng, name: selectedStore.name },
                batches,
                drivers: BATCH_DRIVERS,
                height: 460,
                showDriverOnFirst: false,
                onBatchSelect(idx) {
                    document.querySelectorAll('.preview-batch-card').forEach((card, i) => {
                        card.classList.toggle('map-highlight', i === idx);
                    });
                    const card = document.querySelector(`[data-preview-batch="${idx}"]`);
                    card?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                },
            });

            const gmapsLink = document.getElementById('preview-open-gmaps');
            const allStops = batches.flatMap(b => b.orders || []);
            const origin = `${selectedStore.lat},${selectedStore.lng}`;
            const wps = allStops
                .filter(o => o.lat != null)
                .map(o => `${o.lat},${o.lng}`)
                .join('|');
            if (wps && gmapsLink) {
                gmapsLink.href = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${origin}&waypoints=${wps}&travelmode=driving`;
                gmapsLink.classList.remove('d-none');
            }
        }
    }

    function renderAssignStep(batches) {
        const drivers = availableDriversForStore();
        const list = document.getElementById('assign-batch-list');
        list.innerHTML = batches.map((batch, idx) => {
            const options = drivers.map(d =>
                `<option value="${d.id}">${d.name} · ${d.vehicle || 'Vehicle'}</option>`
            ).join('');
            return `
            <div class="card shadow-none border" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-7">
                            <div class="fw-bold text-body">Batch ${idx + 1} · ${batch.id}</div>
                            <small class="text-muted">${batch.stops} stops · ${batch.distance} · ${batch.route_label || batch.zone}</small>
                            <div class="route-seq mt-2 mb-0">${buildRouteSequenceHtml(batch.orders)}</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold mb-1">Store driver</label>
                            <select class="form-select assign-driver-select" data-batch-idx="${idx}" style="border-radius: 8px;">
                                <option value="">Select driver…</option>
                                ${options}
                            </select>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');

        list.querySelectorAll('.assign-driver-select').forEach(sel => {
            sel.addEventListener('change', () => {
                syncDriverOptions();
                updateConfirmEnabled();
            });
        });
        syncDriverOptions();
        updateConfirmEnabled();
    }

    function syncDriverOptions() {
        const selects = [...document.querySelectorAll('.assign-driver-select')];
        const chosen = new Set(selects.map(s => s.value).filter(Boolean));
        selects.forEach(sel => {
            const current = sel.value;
            [...sel.options].forEach(opt => {
                if (!opt.value) return;
                opt.disabled = chosen.has(opt.value) && opt.value !== current;
            });
        });
    }

    function updateConfirmEnabled() {
        const selects = [...document.querySelectorAll('.assign-driver-select')];
        const allFilled = selects.length > 0 && selects.every(s => !!s.value);
        const values = selects.map(s => s.value).filter(Boolean);
        const unique = new Set(values).size === values.length;
        document.getElementById('btn-confirm').disabled = !(allFilled && unique);
    }

    function collectDriverAssignments() {
        return generatedBatches.map((batch, idx) => {
            const sel = document.querySelector(`.assign-driver-select[data-batch-idx="${idx}"]`);
            return {
                ...batch,
                driver_code: sel?.value || '',
            };
        });
    }

    function runGeneration() {
        if (!selectedStore || !window.DeliverEaseBatchGen) {
            return { batches: [], overflow: [], overflow_count: 0 };
        }

        const orders = PENDING_ORDERS.filter(o => o.store_id === selectedStore.id);
        const hub = { lat: selectedStore.lat, lng: selectedStore.lng };
        const config = getConfig();

        const result = window.DeliverEaseBatchGen.generateBatches(
            orders,
            hub,
            selectedStore.storeData || { id: selectedStore.id, name: selectedStore.name },
            availableDriversForStore(),
            config
        );

        // Back-compat if an older cached asset still returns an array.
        if (Array.isArray(result)) {
            return { batches: result, overflow: [], overflow_count: 0 };
        }

        return result;
    }

    function goStep(n) {
        step = n;
        document.getElementById('step-1').classList.toggle('d-none', n !== 1);
        document.getElementById('step-2').classList.toggle('d-none', n !== 2);
        document.getElementById('step-3').classList.toggle('d-none', n !== 3);
        document.getElementById('step-4').classList.toggle('d-none', n !== 4);

        document.getElementById('btn-continue').classList.toggle('d-none', n !== 1);
        document.getElementById('btn-generate').classList.toggle('d-none', n !== 2);
        document.getElementById('btn-to-assign').classList.toggle('d-none', n !== 3);
        document.getElementById('btn-confirm').classList.toggle('d-none', n !== 4);
        document.getElementById('btn-back').classList.toggle('d-none', n === 1);

        document.querySelectorAll('.step-item').forEach(item => {
            const s = parseInt(item.dataset.step, 10);
            item.classList.toggle('active', s === n);
            item.classList.toggle('done', s < n);
        });
        document.getElementById('step-line-1').classList.toggle('done', n > 1);
        document.getElementById('step-line-2').classList.toggle('done', n > 2);
        document.getElementById('step-line-3').classList.toggle('done', n > 3);

        if (n === 2) {
            document.getElementById('footer-hint').innerHTML = '<i class="bx bx-info-circle"></i> Set max orders per batch. Routes are capped by available store drivers.';
        } else if (n === 3) {
            document.getElementById('footer-hint').innerHTML = '<i class="bx bx-info-circle"></i> Review routes, then assign a store driver to each child batch.';
            setTimeout(() => previewMap?.invalidateSize(), 300);
        } else if (n === 4) {
            document.getElementById('footer-hint').innerHTML = '<i class="bx bx-info-circle"></i> Assign every child batch a unique store driver, then confirm.';
        } else {
            document.getElementById('footer-hint').innerHTML = '<i class="bx bx-info-circle"></i> Select a store with pending orders and available drivers.';
        }
    }

    document.getElementById('btn-continue').addEventListener('click', () => {
        if (!selectedStore) return;
        if (selectedStore.availableDrivers < 1) {
            alert('No available store drivers for this store. Free a driver or wait until a batch finishes.');
            return;
        }
        goStep(2);
    });

    document.getElementById('btn-back').addEventListener('click', () => {
        if (step === 4) goStep(3);
        else if (step === 3) goStep(2);
        else goStep(1);
    });

    document.getElementById('btn-generate').addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="gen-spinner me-1"></span> Optimizing routes…';

        setTimeout(() => {
            const result = runGeneration();
            generatedBatches = result.batches || [];
            overflowCount = result.overflow_count || 0;
            if (!generatedBatches.length) {
                alert(selectedStore.availableDrivers < 1
                    ? 'No available store drivers to create batches.'
                    : 'No pending orders with coordinates found for this store.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bx-route me-1"></i>Generate Routes';
                return;
            }
            renderPreview(generatedBatches);
            goStep(3);
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-route me-1"></i>Generate Routes';
        }, 400);
    });

    document.getElementById('btn-to-assign').addEventListener('click', () => {
        if (!generatedBatches.length) return;
        renderAssignStep(generatedBatches);
        goStep(4);
    });

    document.getElementById('btn-confirm').addEventListener('click', async function () {
        const btn = this;
        if (!generatedBatches.length || !selectedStore) return;

        const batchesWithDrivers = collectDriverAssignments();
        if (batchesWithDrivers.some(b => !b.driver_code)) {
            alert('Assign a store driver to every child batch.');
            return;
        }

        const payload = {
            store_id: selectedStore.id,
            store_name: selectedStore.name,
            generated_at: new Date().toISOString(),
            config: getConfig(),
            overflow_count: overflowCount,
            batches: batchesWithDrivers,
        };

        btn.disabled = true;
        btn.innerHTML = '<span class="gen-spinner me-1"></span> Saving…';

        try {
            const res = await fetch(@json(url('/operations/delivery-batches')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Failed to save batches.');
            }
            window.location.href = '{{ url('/operations/delivery-batches') }}?generated=1';
        } catch (err) {
            alert(err.message || 'Failed to save batches.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-check me-1"></i>Confirm & Save Group';
            updateConfirmEnabled();
        }
    });
});
</script>
@endsection
