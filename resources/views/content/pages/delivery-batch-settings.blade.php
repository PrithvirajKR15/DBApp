@php
$isNavbar = false;
$data = $data ?? [];
$settings = $data['settings'] ?? [];
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Batch Configuration')
@section('page-title', 'Batch Configuration')

@section('content')
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
    .settings-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
    }
    .settings-card .card-header {
        background: transparent;
        border-bottom: 1px solid #f1f3f5;
        padding: 1rem 1.25rem;
    }
    .form-check-input:checked {
        background-color: #ff7a00;
        border-color: #ff7a00;
    }
    .hint-box {
        background: rgba(255, 122, 0, 0.06);
        border: 1px solid rgba(255, 122, 0, 0.18);
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 0.85rem;
        color: #566a7f;
    }
</style>

<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ url('/operations/delivery-batches') }}" class="text-muted" style="font-size: 1.25rem;"><i class="bx bx-arrow-back"></i></a>
            <h3 class="mb-0 fw-bold text-body" style="font-size: 1.6rem; font-family: 'Public Sans', sans-serif;">Batch Configuration</h3>
        </div>
        <p class="mb-0 text-muted ms-4" style="font-size: 0.9rem;">Set defaults for how delivery batches are generated and offered to drivers.</p>
    </div>
</div>

<form id="batch-settings-form" onsubmit="return false;">
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Capacity -->
            <div class="settings-card card shadow-none mb-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold text-body"><i class="bx bx-package me-2" style="color: #ff7a00;"></i>Batch Capacity</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="orders-per-batch">Orders per Batch</label>
                            <input type="number" class="form-control" id="orders-per-batch" name="orders_per_batch" value="{{ $settings['orders_per_batch'] }}" min="1" max="50" style="border-radius: 8px;">
                            <small class="text-muted">Maximum number of orders grouped into a single delivery route.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="max-distance">Max Route Distance (km)</label>
                            <input type="number" class="form-control" id="max-distance" name="max_distance_km" value="{{ $settings['max_distance_km'] }}" min="1" style="border-radius: 8px;">
                            <small class="text-muted">Total hub → stops → hub distance cap per batch.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="max-duration">Max Route Duration (min)</label>
                            <input type="number" class="form-control" id="max-duration" name="max_route_minutes" value="{{ $settings['max_route_minutes'] ?? 45 }}" min="10" style="border-radius: 8px;">
                            <small class="text-muted">Estimated driving time limit per optimized route.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acceptance -->
            <div class="settings-card card shadow-none mb-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold text-body"><i class="bx bx-time-five me-2" style="color: #ff7a00;"></i>Acceptance Window</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="accept-minutes">Required Time to Accept Delivery</label>
                            <select class="form-select" id="accept-minutes" name="accept_minutes" style="border-radius: 8px;">
                                @foreach ([3, 5, 10, 15, 20, 30] as $min)
                                <option value="{{ $min }}" @selected($settings['accept_minutes'] == $min)>{{ $min }} Minutes</option>
                                @endforeach
                            </select>
                            <small class="text-muted">How long drivers have to accept a broadcast or batch assignment.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="default-slot">Default Delivery Slot</label>
                            <input type="text" class="form-control" id="default-slot" name="slot_window" value="{{ $settings['slot_window'] }}" style="border-radius: 8px;" placeholder="e.g. 14:00–18:00">
                            <small class="text-muted">Used when generating batches for the current window.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver priority -->
            <div class="settings-card card shadow-none mb-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold text-body"><i class="bx bx-group me-2" style="color: #ff7a00;"></i>Driver Assignment Priority</h6>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="prefer-store" name="prefer_store_drivers" @checked($settings['prefer_store_drivers'])>
                        <label class="form-check-label fw-semibold" for="prefer-store">Prioritize store drivers</label>
                        <div class="text-muted" style="font-size: 0.85rem; margin-left: 2.2rem;">Store-linked drivers are offered first for batches from their hub.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="fallback-zone" name="auto_fallback_zone" @checked($settings['auto_fallback_zone'])>
                        <label class="form-check-label fw-semibold" for="fallback-zone">Fallback to nearest zone drivers</label>
                        <div class="text-muted" style="font-size: 0.85rem; margin-left: 2.2rem;">If no store driver is available, offer the batch to zone drivers ranked by proximity to the route.</div>
                    </div>
                    <div class="hint-box mt-3">
                        <i class="bx bx-info-circle" style="color: #ff7a00;"></i>
                        Batches are created by road-distance proximity and route efficiency — not fixed zone boundaries. Zones are used for reporting and driver fallback only.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="settings-card card shadow-none sticky-top" style="top: 1rem;">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold text-body">Summary</h6>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled mb-4" style="font-size: 0.9rem;">
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Orders / batch</span>
                            <span class="fw-semibold text-body" id="sum-orders">{{ $settings['orders_per_batch'] }}</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Accept within</span>
                            <span class="fw-semibold text-body" id="sum-accept">{{ $settings['accept_minutes'] }} min</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Max distance</span>
                            <span class="fw-semibold text-body" id="sum-distance">{{ $settings['max_distance_km'] }} km</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Max duration</span>
                            <span class="fw-semibold text-body" id="sum-duration">{{ $settings['max_route_minutes'] ?? 45 }} min</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Store priority</span>
                            <span class="fw-semibold text-body" id="sum-store">{{ $settings['prefer_store_drivers'] ? 'On' : 'Off' }}</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Zone fallback</span>
                            <span class="fw-semibold text-body" id="sum-zone">{{ $settings['auto_fallback_zone'] ? 'On' : 'Off' }}</span>
                        </li>
                    </ul>
                    <button type="button" class="btn btn-primary-orange w-100 mb-2" id="save-settings" style="border-radius: 8px;">
                        <i class="bx bx-save me-1"></i>Save Configuration
                    </button>
                    <a href="{{ url('/operations/delivery-batches/generate') }}" class="btn btn-outline-secondary w-100" style="border-radius: 8px;">
                        Generate Batches
                    </a>
                    <div class="alert alert-success mt-3 d-none mb-0" id="save-success" style="border-radius: 8px; font-size: 0.85rem;">
                        Configuration saved (demo — not persisted yet).
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const orders = document.getElementById('orders-per-batch');
    const accept = document.getElementById('accept-minutes');
    const distance = document.getElementById('max-distance');
    const duration = document.getElementById('max-duration');
    const preferStore = document.getElementById('prefer-store');
    const fallbackZone = document.getElementById('fallback-zone');

    function getSettings() {
        return {
            orders_per_batch: parseInt(orders.value, 10),
            accept_minutes: parseInt(accept.value, 10),
            max_distance_km: parseFloat(distance.value),
            max_route_minutes: parseInt(duration.value, 10),
            prefer_store_drivers: preferStore.checked,
            auto_fallback_zone: fallbackZone.checked,
        };
    }

    function loadSettings() {
        try {
            const saved = JSON.parse(localStorage.getItem('deliverease_batch_settings') || 'null');
            if (!saved) return;
            if (saved.orders_per_batch) orders.value = saved.orders_per_batch;
            if (saved.accept_minutes) accept.value = saved.accept_minutes;
            if (saved.max_distance_km) distance.value = saved.max_distance_km;
            if (saved.max_route_minutes) duration.value = saved.max_route_minutes;
            if (saved.prefer_store_drivers != null) preferStore.checked = saved.prefer_store_drivers;
            if (saved.auto_fallback_zone != null) fallbackZone.checked = saved.auto_fallback_zone;
        } catch (e) { /* ignore */ }
    }
    loadSettings();

    function syncSummary() {
        document.getElementById('sum-orders').textContent = orders.value;
        document.getElementById('sum-accept').textContent = accept.value + ' min';
        document.getElementById('sum-distance').textContent = distance.value + ' km';
        document.getElementById('sum-duration').textContent = duration.value + ' min';
        document.getElementById('sum-store').textContent = preferStore.checked ? 'On' : 'Off';
        document.getElementById('sum-zone').textContent = fallbackZone.checked ? 'On' : 'Off';
    }

    [orders, accept, distance, duration, preferStore, fallbackZone].forEach(el => {
        el.addEventListener('input', syncSummary);
        el.addEventListener('change', syncSummary);
    });
    syncSummary();

    document.getElementById('save-settings').addEventListener('click', function () {
        localStorage.setItem('deliverease_batch_settings', JSON.stringify(getSettings()));
        const box = document.getElementById('save-success');
        box.classList.remove('d-none');
        setTimeout(() => box.classList.add('d-none'), 3000);
    });
});
</script>
@endsection
