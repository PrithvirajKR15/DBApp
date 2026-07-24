@php
$isNavbar = false;
$isFooter = true;
$isFlex = true;
$container = 'container-fluid';
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Live Map')

@section('content')
<style>
    /* Prevent page scrollbars, force full height */
    html, body, .layout-wrapper, .layout-container, .layout-page, .content-wrapper {
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
    }

    /* Full height tracking layout */
    .tracking-wrapper {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        max-height: 100%;
        overflow: hidden;
        background-color: #f5f5f9;
        font-family: 'Public Sans', -apple-system, sans-serif;
    }

    /* Sidebar Styles */
    .tracking-sidebar {
        width: 380px;
        min-width: 380px;
        max-width: 380px;
        background: #ffffff;
        border-right: 1px solid #e0e2e7;
        display: flex;
        flex-direction: column;
        height: 100%;
        z-index: 10;
    }

    /* Driver Item Card */
    .driver-item-card {
        background: #ffffff;
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .driver-item-card:hover {
        border-color: #ff7a00;
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.06);
    }

    .driver-item-card.active {
        border: 1px solid #ff7a00 !important;
        background-color: rgba(255, 122, 0, 0.02);
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.12);
    }

    /* Avatar Status Wrapper */
    .driver-avatar-wrapper {
        position: relative;
        width: 44px;
        height: 44px;
        flex-shrink: 0;
    }

    .driver-avatar-wrapper img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .driver-avatar-wrapper .status-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        border: 2px solid #ffffff;
    }

    .status-indicator.transit {
        background-color: #696cff;
    }

    .status-indicator.idle {
        background-color: #71dd37;
    }

    .status-indicator.offline {
        background-color: #8592a3;
    }

    /* Status Badges */
    .status-badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: none;
    }

    .status-badge.transit {
        background-color: rgba(105, 108, 255, 0.1);
        color: #696cff;
    }

    .status-badge.idle {
        background-color: rgba(113, 221, 55, 0.1);
        color: #71dd37;
    }

    .status-badge.offline {
        background-color: rgba(133, 146, 163, 0.1);
        color: #8592a3;
    }

    /* Floating Driver Profile Card */
    .driver-profile-card {
        position: absolute;
        top: 20px;
        left: 20px;
        width: 340px;
        z-index: 1000;
        background: #ffffff;
        border: 1px solid #ff7a00;
        border-radius: 16px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        display: none;
        padding: 18px;
    }

    .profile-card-close-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        background: none;
        border: none;
        color: #8592a3;
        font-size: 1.4rem;
        cursor: pointer;
        line-height: 1;
        padding: 2px 6px;
        transition: color 0.15s ease;
    }

    .profile-card-close-btn:hover {
        color: #ff3e1d;
    }

    .order-details-box {
        background-color: rgba(255, 122, 0, 0.04);
        border-radius: 12px;
        padding: 14px;
        margin-top: 14px;
        margin-bottom: 14px;
    }

    .order-details-title {
        color: #ff7a00;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .profile-stats-row {
        display: flex;
        gap: 8px;
        margin-bottom: 14px;
    }

    .profile-stat-col {
        flex: 1;
        background-color: #f8f9fa;
        border-radius: 8px;
        text-align: center;
        padding: 8px 4px;
    }

    .profile-stat-val {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2b2b2b;
        line-height: 1.2;
    }

    .profile-stat-lbl {
        font-size: 0.65rem;
        color: #8592a3;
        font-weight: 500;
        margin-top: 2px;
    }

    /* Custom Map Markers (Google Maps OverlayView) */
    .gm-driver-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.16);
        border: 3px solid #696cff;
        position: relative;
        cursor: pointer;
        transform: translate(-50%, -100%);
    }

    .gm-driver-marker.transit {
        border-color: #696cff;
    }

    .gm-driver-marker.idle {
        border-color: #71dd37;
    }

    .gm-driver-marker.offline {
        border-color: #8592a3;
    }

    .gm-driver-marker img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .gm-driver-marker .marker-status-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #ffffff;
    }

    .gm-driver-marker.transit .marker-status-dot {
        background-color: #696cff;
    }

    .gm-driver-marker.idle .marker-status-dot {
        background-color: #71dd37;
    }

    .gm-driver-marker.offline .marker-status-dot {
        background-color: #8592a3;
    }

    .gm-driver-marker.hidden {
        display: none !important;
    }

    .gm-style .gm-style-iw-c {
        border-radius: 12px !important;
        padding: 0 !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1) !important;
    }

    .gm-style .gm-style-iw-d {
        overflow: hidden !important;
    }

    /* Custom Map Vertical Controls */
    .map-control-btn {
        width: 36px;
        height: 36px;
        color: #566a7f;
        background: #ffffff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .map-control-btn:hover {
        background-color: #f8f9fa;
        color: #ff7a00;
    }
    .map-control-btn:first-child {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    .map-control-btn:last-child {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .map-key-missing {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        background: #f5f5f9;
        color: #566a7f;
        text-align: center;
        padding: 2rem;
    }

    .stat-filter-chip {
        background-color: #fff;
        border: 1px solid #e0e2e7 !important;
        border-radius: 8px !important;
        cursor: pointer;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        user-select: none;
    }

    .stat-filter-chip:hover {
        border-color: #ff7a00 !important;
        box-shadow: 0 2px 8px rgba(255, 122, 0, 0.08);
    }

    .stat-filter-chip.active {
        border-color: #ff7a00 !important;
        background-color: rgba(255, 122, 0, 0.04);
        box-shadow: 0 2px 8px rgba(255, 122, 0, 0.12);
    }

    .stat-filter-chip.active[data-status="transit"] h4 {
        color: #696cff !important;
    }

    .stat-filter-chip.active[data-status="idle"] h4 {
        color: #71dd37 !important;
    }

    .stat-filter-chip.active[data-status="offline"] h4 {
        color: #8592a3 !important;
    }
</style>

<div class="tracking-wrapper">
    <!-- Top Full-Width Navbar -->
    <div class="bg-white border-bottom d-flex align-items-center justify-content-between" style="height: 70px; min-height: 70px; border-color: #e0e2e7 !important; z-index: 1000;">
        <div class="flex-grow-1 d-flex align-items-center justify-content-between px-4" style="height: 100%;">
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-bold text-body" style="font-size: 1.25rem;">Live Map</h4>
                <span class="badge bg-label-success rounded-pill d-inline-flex align-items-center gap-1" style="text-transform: none; padding: 4px 8px; font-size: 0.72rem; font-weight: 500; background-color: rgba(113, 221, 55, 0.1) !important; color: #71dd37 !important;">
                    <span class="d-inline-block rounded-circle bg-success animate-pulse" style="width: 6px; height: 6px;"></span>
                    Live Tracking Active
                </span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="position-relative d-flex align-items-center">
                    <i class="bx bx-layer text-muted position-absolute" style="left: 12px; font-size: 1.1rem; pointer-events: none;"></i>
                    <select class="form-select form-select-sm" id="map-zone-filter" style="width: 150px; padding-left: 36px; border-color: #d9dee3; font-size: 0.82rem; height: 38px; border-radius: 6px; cursor: pointer;">
                        <option value="all">All Zones</option>
                        @foreach(($zones ?? []) as $zone)
                            <option value="{{ $zone['code'] }}">{{ $zone['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="position-relative d-flex align-items-center">
                    <i class="bx bx-filter text-muted position-absolute" style="left: 12px; font-size: 1.1rem; pointer-events: none;"></i>
                    <select class="form-select form-select-sm" id="map-status-filter" style="width: 140px; padding-left: 36px; border-color: #d9dee3; font-size: 0.82rem; height: 38px; border-radius: 6px; cursor: pointer;">
                        <option value="all">All Statuses</option>
                        <option value="transit">In Transit</option>
                        <option value="idle">Idle / Available</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>

                <div class="position-relative cursor-pointer d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bx bx-bell text-muted" style="font-size: 1.3rem;"></i>
                    <span class="position-absolute bg-danger rounded-circle" style="width: 6px; height: 6px; top: 8px; right: 8px;"></span>
                </div>

                <button class="btn btn-sm text-white d-flex align-items-center gap-1" id="btn-refresh-map" style="background-color: #ff7a00; border-color: #ff7a00; padding: 0 16px; font-weight: 600; border-radius: 6px; height: 38px; font-size: 0.82rem;">
                    <i class="bx bx-refresh" style="font-size: 1.15rem;"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Main Workspace Area -->
    <div class="d-flex flex-grow-1 w-100" style="min-height: 0; height: calc(100% - 70px);">
        <div class="tracking-sidebar">
            <div class="row g-2 px-3 my-3" id="stat-filter-chips">
                <div class="col-4">
                    <div class="stat-filter-chip border rounded text-center py-2 px-1" data-status="transit" role="button" tabindex="0" title="Show in-transit drivers" aria-pressed="false">
                        <h4 class="mb-0 fw-bold text-body" style="font-size: 1.2rem;">{{ $stats['transit'] }}</h4>
                        <div class="text-muted" style="font-size: 0.72rem; font-weight: 500;">In Transit</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-filter-chip border rounded text-center py-2 px-1" data-status="idle" role="button" tabindex="0" title="Show idle drivers" aria-pressed="false">
                        <h4 class="mb-0 fw-bold text-success" style="font-size: 1.2rem;">{{ $stats['idle'] }}</h4>
                        <div class="text-muted" style="font-size: 0.72rem; font-weight: 500;">Idle</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-filter-chip border rounded text-center py-2 px-1" data-status="offline" role="button" tabindex="0" title="Show offline drivers" aria-pressed="false">
                        <h4 class="mb-0 fw-bold text-secondary" style="font-size: 1.2rem;">{{ $stats['offline'] }}</h4>
                        <div class="text-muted" style="font-size: 0.72rem; font-weight: 500;">Offline</div>
                    </div>
                </div>
            </div>

            <div class="px-3 mb-3">
                <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #e0e2e7 !important; border-radius: 8px !important;">
                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted" style="font-size: 1.1rem;"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent ps-1" placeholder="Search driver or order..." id="search-driver-map" style="box-shadow: none; font-size: 0.85rem; height: 38px;">
                </div>
            </div>

            <div class="flex-grow-1 px-3" id="driver-map-list" style="overflow-y: auto; padding-bottom: 20px;">
                <!-- Rendered dynamically -->
            </div>

            <div class="mt-auto border-top px-3 py-2 bg-transparent d-flex justify-content-between align-items-center" style="font-size: 0.75rem; border-color: #e0e2e7 !important; min-height: 44px;">
                <span class="text-muted" id="showing-drivers-text">Showing {{ $stats['total'] }} of {{ $stats['total'] }} drivers</span>
                <a href="javascript:void(0);" style="color: #ff7a00; font-weight: 600;">View All</a>
            </div>
        </div>

        <div class="flex-grow-1 position-relative h-100" style="min-width: 0;">
            <div id="map" style="height: 100%; width: 100%;"></div>

            <div class="d-flex flex-column bg-white shadow-sm border rounded" style="position: absolute; top: 20px; right: 20px; z-index: 1000; border-color: #e0e2e7 !important; border-radius: 8px !important; overflow: hidden; width: 36px;">
                <button class="map-control-btn" id="control-zoom-in" title="Zoom In">
                    <i class="bx bx-plus" style="font-size: 1.25rem;"></i>
                </button>
                <div style="height: 1px; background-color: #e0e2e7;"></div>
                <button class="map-control-btn" id="control-zoom-out" title="Zoom Out">
                    <i class="bx bx-minus" style="font-size: 1.25rem;"></i>
                </button>
                <div style="height: 1px; background-color: #e0e2e7;"></div>
                <button class="map-control-btn" id="control-recenter" title="Recenter Map">
                    <i class="bx bx-target-lock" style="font-size: 1.2rem;"></i>
                </button>
                <div style="height: 1px; background-color: #e0e2e7;"></div>
                <button class="map-control-btn" id="control-layers" title="Toggle Map Style">
                    <i class="bx bx-layer" style="font-size: 1.2rem;"></i>
                </button>
                <div style="height: 1px; background-color: #e0e2e7;"></div>
                <button class="map-control-btn" id="control-fullscreen" title="Toggle Fullscreen">
                    <i class="bx bx-fullscreen" style="font-size: 1.1rem;"></i>
                </button>
            </div>

            <div id="driver-profile-card" class="driver-profile-card"></div>

            <div class="map-legend-card bg-white p-3 border rounded shadow-sm" style="position: absolute; bottom: 20px; right: 20px; width: 180px; z-index: 1000; border-color: #e0e2e7 !important; border-radius: 12px !important;">
                <h6 class="mb-2 fw-bold text-body" style="font-size: 0.8rem; letter-spacing: 0.5px;">Legend</h6>
                <div class="d-flex flex-column gap-2" style="font-size: 0.75rem;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle bg-primary" style="width: 10px; height: 10px;"></span>
                        <span class="text-muted">In Transit</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle bg-success" style="width: 10px; height: 10px;"></span>
                        <span class="text-muted">Idle / Available</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle bg-secondary" style="width: 10px; height: 10px;"></span>
                        <span class="text-muted">Offline</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block border border-danger rounded-circle" style="width: 10px; height: 10px; border-width: 2px !important; background-color: rgba(255, 62, 29, 0.1);"></span>
                        <span class="text-muted">Zone (10 km)</span>
                    </div>
                </div>
            </div>

            <div class="map-stats-card bg-white p-3 border rounded shadow-sm" style="position: absolute; bottom: 20px; left: 20px; width: 220px; z-index: 1000; border-color: #e0e2e7 !important; border-radius: 12px !important;">
                <h6 class="mb-2 fw-bold text-body" style="font-size: 0.8rem; letter-spacing: 0.5px;">Live Stats</h6>
                <div class="d-flex flex-column gap-2" style="font-size: 0.75rem;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Active Drivers</span>
                        <span class="fw-bold text-body" style="font-size: 0.82rem;">{{ $stats['active'] }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">In Transit</span>
                        <span class="fw-bold text-primary" style="font-size: 0.82rem; color: #696cff !important;">{{ $stats['transit'] }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Orders Today</span>
                        <span class="fw-bold text-body" style="font-size: 0.82rem;">{{ number_format($stats['orders_today']) }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Avg. ETA</span>
                        <span class="fw-bold" style="font-size: 0.82rem; color: #ff7a00;">{{ $stats['avg_eta'] }} min</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.LIVE_MAP = {
        drivers: @json($drivers),
        zones: @json($zones ?? []),
        zoneRadiusMeters: {{ (int) ($zoneRadiusMeters ?? 10000) }},
        avatarBase: @json(asset('assets/img/avatars')),
        googleMapsKey: @json($googleMapsKey ?? ''),
        center: @json($mapCenter ?? ['lat' => 8.5241, 'lng' => 76.9366]),
    };
</script>

@if(!empty($googleMapsKey))
<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsKey) }}&callback=initLiveMap" async defer></script>
@endif

<script>
(function () {
    const cfg = window.LIVE_MAP;
    const drivers = cfg.drivers || [];
    const zones = cfg.zones || [];
    const zoneRadiusMeters = cfg.zoneRadiusMeters || 10000;
    const markers = {};
    const zoneCircles = [];
    let map = null;
    let infoWindow = null;
    let isDark = false;
    let mapReady = false;
    let DriverMarker = null;

    function defineDriverMarker() {
        DriverMarker = class extends google.maps.OverlayView {
            constructor(driver, mapInstance) {
                super();
                this.driver = driver;
                this.position = new google.maps.LatLng(driver.lat, driver.lng);
                this.div = null;
                this.setMap(mapInstance);
            }

            onAdd() {
                const d = this.driver;
                const div = document.createElement('div');
                div.className = `gm-driver-marker ${String(d.status).toLowerCase()}`;
                div.innerHTML = `
                    <img src="${cfg.avatarBase}/${d.image}" alt="${d.name}">
                    <span class="marker-status-dot"></span>
                `;
                div.addEventListener('click', (e) => {
                    e.stopPropagation();
                    window.selectDriver(d.id, false);
                    this.openInfo();
                });
                this.div = div;
                this.getPanes().overlayMouseTarget.appendChild(div);
            }

            draw() {
                if (!this.div) return;
                const projection = this.getProjection();
                const point = projection.fromLatLngToDivPixel(this.position);
                if (point) {
                    this.div.style.left = point.x + 'px';
                    this.div.style.top = point.y + 'px';
                }
            }

            onRemove() {
                if (this.div && this.div.parentNode) {
                    this.div.parentNode.removeChild(this.div);
                }
                this.div = null;
            }

            setVisible(visible) {
                if (this.div) {
                    this.div.classList.toggle('hidden', !visible);
                }
            }

            setPosition(lat, lng) {
                this.position = new google.maps.LatLng(lat, lng);
                this.draw();
            }

            openInfo() {
                if (!infoWindow) return;
                infoWindow.setContent(`
                    <div style="padding:8px 12px;font-size:0.85rem;line-height:1.4;">
                        <strong>${this.driver.name}</strong><br>
                        <span style="font-size:0.75rem;color:#8592a3;">Status: ${this.driver.status}</span>
                    </div>
                `);
                infoWindow.setPosition(this.position);
                infoWindow.open(map);
            }
        };
    }

    function getDriverCardHtml(d) {
        let badgeClass = '';
        let detailsText = '';
        let row3Html = '';

        if (d.status === 'Transit') {
            badgeClass = 'transit';
            detailsText = `${d.orderId} &middot; ${d.distance}`;
            row3Html = `
                <div class="d-flex justify-content-between mt-2 pt-2 border-top" style="border-color: #f1f3f5 !important; font-size: 0.75rem;">
                    <span style="color: #ff7a00; font-weight: 600;"><i class="bx bx-time-five me-1 align-middle" style="font-size: 0.95rem;"></i>ETA: ${d.eta}</span>
                    <span style="color: #ff7a00; font-weight: 600;"><i class="bx bx-cycling me-1 align-middle" style="font-size: 0.95rem;"></i>${d.speed}</span>
                </div>
            `;
        } else if (d.status === 'Idle') {
            badgeClass = 'idle';
            detailsText = d.details;
            row3Html = `
                <div class="d-flex justify-content-between mt-2 pt-2 border-top" style="border-color: #f1f3f5 !important; font-size: 0.75rem;">
                    <span class="text-success" style="font-weight: 600;"><i class="bx bx-check-circle me-1 align-middle" style="font-size: 0.95rem;"></i>Ready</span>
                    <span class="text-warning" style="font-weight: 600;"><i class="bx bxs-star me-1 align-middle" style="font-size: 0.95rem;"></i>${d.rating} Rating</span>
                </div>
            `;
        } else {
            badgeClass = 'offline';
            detailsText = d.details;
        }

        return `
            <div class="driver-item-card" id="driver-card-${d.id}" onclick="selectDriver(${d.id})">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="driver-avatar-wrapper me-3">
                            <img src="${cfg.avatarBase}/${d.image}" alt="${d.name}">
                            <span class="status-indicator ${String(d.status).toLowerCase()}"></span>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-body" style="font-size: 0.88rem;">${d.name}</h6>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">${detailsText}</small>
                        </div>
                    </div>
                    <span class="status-badge ${badgeClass}">${d.status}</span>
                </div>
                ${row3Html}
            </div>
        `;
    }

    function renderDriversList() {
        const listContainer = document.getElementById('driver-map-list');
        listContainer.innerHTML = drivers.map(d => getDriverCardHtml(d)).join('');
    }

    function renderDriverProfileCard(driver) {
        const cardEl = document.getElementById('driver-profile-card');
        if (!driver) {
            cardEl.style.display = 'none';
            return;
        }

        let statusText = '';
        let statusColor = '';
        if (driver.status === 'Transit') {
            statusText = 'In Transit';
            statusColor = '#ff7a00';
        } else if (driver.status === 'Idle') {
            statusText = 'Idle (Available)';
            statusColor = '#71dd37';
        } else {
            statusText = 'Offline';
            statusColor = '#8592a3';
        }

        let orderHtml = '';
        if (driver.status === 'Transit') {
            orderHtml = `
                <div class="order-details-box">
                    <div class="order-details-title">Current Order</div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.8rem;">
                        <span class="text-muted">Order ID</span>
                        <span class="fw-bold text-body">${driver.orderId}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.8rem;">
                        <span class="text-muted">Destination</span>
                        <span class="fw-bold text-body" style="max-width: 160px; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${driver.destination}</span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                        <span class="text-muted">ETA</span>
                        <span class="fw-bold" style="color: #ff7a00;">${driver.eta}</span>
                    </div>
                </div>
            `;
        } else if (driver.status === 'Idle') {
            orderHtml = `
                <div class="order-details-box" style="background-color: rgba(113, 221, 55, 0.04);">
                    <div class="order-details-title" style="color: #71dd37;">Availability Status</div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.8rem;">
                        <span class="text-muted">Status</span>
                        <span class="fw-bold text-success">Available</span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                        <span class="text-muted">Current Zone</span>
                        <span class="fw-bold text-body">${(driver.details || '').replace('Available &middot; ', '')}</span>
                    </div>
                </div>
            `;
        } else {
            orderHtml = `
                <div class="order-details-box" style="background-color: rgba(133, 146, 163, 0.04);">
                    <div class="order-details-title" style="color: #8592a3;">Status Info</div>
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.8rem;">
                        <span class="text-muted">Status</span>
                        <span class="fw-bold text-secondary">Offline</span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                        <span class="text-muted">Last Seen</span>
                        <span class="fw-bold text-body">${driver.details}</span>
                    </div>
                </div>
            `;
        }

        let statsHtml = '';
        if (driver.status === 'Transit') {
            statsHtml = `
                <div class="profile-stats-row">
                    <div class="profile-stat-col">
                        <div class="profile-stat-val">${driver.speedNum}</div>
                        <div class="profile-stat-lbl">km/h</div>
                    </div>
                    <div class="profile-stat-col">
                        <div class="profile-stat-val">${driver.distanceNum}</div>
                        <div class="profile-stat-lbl">km left</div>
                    </div>
                    <div class="profile-stat-col">
                        <div class="profile-stat-val text-success">${driver.rating}</div>
                        <div class="profile-stat-lbl">Rating</div>
                    </div>
                </div>
            `;
        } else if (driver.status === 'Idle') {
            statsHtml = `
                <div class="profile-stats-row">
                    <div class="profile-stat-col">
                        <div class="profile-stat-val">0</div>
                        <div class="profile-stat-lbl">km/h</div>
                    </div>
                    <div class="profile-stat-col">
                        <div class="profile-stat-val">14m</div>
                        <div class="profile-stat-lbl">Idle Time</div>
                    </div>
                    <div class="profile-stat-col">
                        <div class="profile-stat-val text-success">${driver.rating}</div>
                        <div class="profile-stat-lbl">Rating</div>
                    </div>
                </div>
            `;
        } else {
            statsHtml = `
                <div class="profile-stats-row">
                    <div class="profile-stat-col">
                        <div class="profile-stat-val">--</div>
                        <div class="profile-stat-lbl">km/h</div>
                    </div>
                    <div class="profile-stat-col">
                        <div class="profile-stat-val">Offline</div>
                        <div class="profile-stat-lbl">Status</div>
                    </div>
                    <div class="profile-stat-col">
                        <div class="profile-stat-val text-secondary">${driver.rating}</div>
                        <div class="profile-stat-lbl">Rating</div>
                    </div>
                </div>
            `;
        }

        cardEl.innerHTML = `
            <button class="profile-card-close-btn" id="close-profile-card-btn">&times;</button>
            <div class="d-flex align-items-center mb-3">
                <div class="driver-avatar-wrapper me-3">
                    <img src="${cfg.avatarBase}/${driver.image}" alt="${driver.name}">
                    <span class="status-indicator ${String(driver.status).toLowerCase()}"></span>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 0.95rem;">${driver.name}</h5>
                    <small class="fw-bold" style="color: ${statusColor}; font-size: 0.75rem;">${statusText}</small>
                </div>
            </div>
            ${orderHtml}
            ${statsHtml}
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm flex-grow-1 py-2" style="font-size: 0.75rem; border-color: #d9dee3;"><i class="bx bx-phone me-1" style="font-size: 0.9rem;"></i> Call</button>
                <button class="btn btn-outline-secondary btn-sm flex-grow-1 py-2" style="font-size: 0.75rem; border-color: #d9dee3;"><i class="bx bx-message-square-detail me-1" style="font-size: 0.9rem;"></i> Message</button>
                <button class="btn btn-primary btn-sm flex-grow-1 py-2 d-flex align-items-center justify-content-center gap-1" style="font-size: 0.75rem; background-color: #ff7a00; border-color: #ff7a00; border-radius: 6px;">
                    <i class="bx bx-git-branch" style="font-size: 0.9rem;"></i> Route
                </button>
            </div>
        `;

        cardEl.style.display = 'block';

        document.getElementById('close-profile-card-btn').addEventListener('click', function () {
            cardEl.style.display = 'none';
            document.querySelectorAll('.driver-item-card.active').forEach(c => c.classList.remove('active'));
            if (infoWindow) infoWindow.close();
        });
    }

    window.selectDriver = function (id, flyToMap = true) {
        const d = drivers.find(drv => drv.id === id);
        if (!d) return;

        document.querySelectorAll('.driver-item-card').forEach(card => card.classList.remove('active'));
        const cardEl = document.getElementById(`driver-card-${id}`);
        if (cardEl) {
            cardEl.classList.add('active');
            cardEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        renderDriverProfileCard(d);

        if (flyToMap && mapReady && map && shouldShowOnMap(d)) {
            map.panTo({ lat: d.lat, lng: d.lng });
            map.setZoom(Math.max(map.getZoom(), 14));
            if (markers[id]) {
                markers[id].openInfo();
            }
        } else if (infoWindow) {
            infoWindow.close();
        }
    };

    function syncStatChips(statusFilter) {
        document.querySelectorAll('.stat-filter-chip').forEach(chip => {
            const active = chip.dataset.status === statusFilter;
            chip.classList.toggle('active', active);
            chip.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function setStatusFilter(status, { fitMap = false } = {}) {
        const select = document.getElementById('map-status-filter');
        const next = status || 'all';
        if (select.value !== next) {
            select.value = next;
        }
        syncStatChips(next);
        filterDrivers();
        if (fitMap && mapReady) {
            fitVisibleDrivers();
        }
    }

    function hasMapLocation(d) {
        return Number.isFinite(d.lat) && Number.isFinite(d.lng) && !(d.lat === 0 && d.lng === 0);
    }

    /** Online / in-transit drivers are plotted at their current or last known fix. */
    function shouldShowOnMap(d) {
        return d.status !== 'Offline' && hasMapLocation(d);
    }

    function syncZoneCircles() {
        if (!map) return;

        const zoneFilter = document.getElementById('map-zone-filter')?.value || 'all';
        zoneCircles.forEach(({ circle, zone }) => {
            // No zone rings for "All Zones"; only show when a region is chosen.
            const visible = zoneFilter !== 'all' && zone.code === zoneFilter;
            circle.setMap(visible ? map : null);
        });
    }

    function drawZoneCircles() {
        zoneCircles.length = 0;

        zones.forEach(zone => {
            if (!zone.lat || !zone.lng) return;

            const circle = new google.maps.Circle({
                center: { lat: zone.lat, lng: zone.lng },
                radius: zoneRadiusMeters,
                strokeColor: '#ff3e1d',
                strokeOpacity: 0.85,
                strokeWeight: 2,
                fillColor: '#ff3e1d',
                fillOpacity: 0.05,
                map: null, // hidden until a specific region is selected
                clickable: true,
                zIndex: 1
            });

            circle.addListener('click', () => {
                infoWindow.setContent(
                    `<div style="padding:8px 12px;">` +
                    `<strong>${zone.name}</strong><br>` +
                    `<span style="font-size:0.75rem;color:#8592a3;">${regionLabel(zone.region)} · 10 km radius</span>` +
                    `</div>`
                );
                infoWindow.setPosition({ lat: zone.lat, lng: zone.lng });
                infoWindow.open(map);
            });

            zoneCircles.push({ circle, zone });
        });

        syncZoneCircles();
    }

    function filterDrivers() {
        const searchQuery = document.getElementById('search-driver-map').value.toLowerCase();
        const statusFilter = document.getElementById('map-status-filter').value;
        const zoneFilter = document.getElementById('map-zone-filter').value;
        let visibleCount = 0;

        syncStatChips(statusFilter);
        syncZoneCircles();

        drivers.forEach(d => {
            const cardEl = document.getElementById(`driver-card-${d.id}`);
            const matchesSearch = d.name.toLowerCase().includes(searchQuery) ||
                (d.orderId && d.orderId.toLowerCase().includes(searchQuery));
            const matchesStatus = (statusFilter === 'all') || (d.status.toLowerCase() === statusFilter);
            const matchesZone = (zoneFilter === 'all') || (d.zone === zoneFilter);
            const visible = matchesSearch && matchesStatus && matchesZone;

            if (cardEl) cardEl.style.display = visible ? 'block' : 'none';
            if (markers[d.id]) markers[d.id].setVisible(visible && shouldShowOnMap(d));
            if (visible) visibleCount++;
        });

        document.getElementById('showing-drivers-text').innerText = `Showing ${visibleCount} of ${drivers.length} drivers`;
    }

    function fitVisibleDrivers() {
        const bounds = new google.maps.LatLngBounds();
        let hasPoints = false;
        const statusFilter = document.getElementById('map-status-filter').value;
        const zoneFilter = document.getElementById('map-zone-filter').value;

        drivers.forEach(d => {
            const matchesStatus = (statusFilter === 'all' || d.status.toLowerCase() === statusFilter);
            const matchesZone = (zoneFilter === 'all' || d.zone === zoneFilter);
            if (matchesStatus && matchesZone && shouldShowOnMap(d)) {
                bounds.extend({ lat: d.lat, lng: d.lng });
                hasPoints = true;
            }
        });

        if (hasPoints) {
            map.fitBounds(bounds, 50);
        } else {
            map.setCenter(cfg.center);
            map.setZoom(13);
        }
    }

    function bindUi() {
        renderDriversList();

        document.getElementById('search-driver-map').addEventListener('input', filterDrivers);
        document.getElementById('map-status-filter').addEventListener('change', () => {
            setStatusFilter(document.getElementById('map-status-filter').value, { fitMap: true });
        });
        document.getElementById('map-zone-filter').addEventListener('change', () => {
            filterDrivers();
            if (mapReady) fitVisibleDrivers();
        });

        document.querySelectorAll('.stat-filter-chip').forEach(chip => {
            const activate = () => {
                const status = chip.dataset.status;
                const current = document.getElementById('map-status-filter').value;
                // Click again to clear and show all.
                setStatusFilter(current === status ? 'all' : status, { fitMap: true });
            };
            chip.addEventListener('click', activate);
            chip.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    activate();
                }
            });
        });

        document.getElementById('control-zoom-in').addEventListener('click', () => {
            if (map) map.setZoom(map.getZoom() + 1);
        });
        document.getElementById('control-zoom-out').addEventListener('click', () => {
            if (map) map.setZoom(map.getZoom() - 1);
        });
        document.getElementById('control-recenter').addEventListener('click', () => {
            if (mapReady) fitVisibleDrivers();
        });
        document.getElementById('control-layers').addEventListener('click', function () {
            if (!map) return;
            isDark = !isDark;
            map.setMapTypeId(isDark ? 'hybrid' : 'roadmap');
            this.style.color = isDark ? '#ff7a00' : '#566a7f';
        });
        document.getElementById('control-fullscreen').addEventListener('click', function () {
            const wrapper = document.querySelector('.tracking-wrapper');
            if (!document.fullscreenElement) {
                wrapper.requestFullscreen().then(() => {
                    this.style.color = '#ff7a00';
                }).catch(err => console.error(err.message));
            } else {
                document.exitFullscreen();
                this.style.color = '#566a7f';
            }
        });
        document.addEventListener('fullscreenchange', function () {
            const btn = document.getElementById('control-fullscreen');
            if (btn) btn.style.color = document.fullscreenElement ? '#ff7a00' : '#566a7f';
            if (map) {
                setTimeout(() => google.maps.event.trigger(map, 'resize'), 200);
            }
        });

        document.getElementById('btn-refresh-map').addEventListener('click', function () {
            const icon = this.querySelector('i');
            icon.classList.add('bx-spin');
            this.disabled = true;
            window.location.reload();
        });

        const urlParams = new URLSearchParams(window.location.search);
        const driverParam = urlParams.get('driver');
        const orderParam = urlParams.get('order');

        // Only select when deep-linked via ?driver= or ?order= — never pre-select on load.
        const pick = () => {
            let found = null;
            if (driverParam) {
                found = drivers.find(d => d.name.toLowerCase() === driverParam.toLowerCase());
            } else if (orderParam) {
                found = drivers.find(d => d.orderId && d.orderId.toLowerCase() === orderParam.toLowerCase());
            }
            if (found) selectDriver(found.id);
        };

        setTimeout(pick, 300);
    }

    window.initLiveMap = function () {
        defineDriverMarker();

        map = new google.maps.Map(document.getElementById('map'), {
            center: cfg.center,
            zoom: 13,
            disableDefaultUI: true,
            gestureHandling: 'greedy',
            clickableIcons: false,
            styles: []
        });

        infoWindow = new google.maps.InfoWindow();

        drawZoneCircles();

        drivers.forEach(d => {
            // Plot current/last known location for online & in-transit drivers only.
            if (!shouldShowOnMap(d)) return;
            markers[d.id] = new DriverMarker(d, map);
        });

        mapReady = true;
        filterDrivers();
        fitVisibleDrivers();

        setTimeout(() => google.maps.event.trigger(map, 'resize'), 200);
    };

    document.addEventListener('DOMContentLoaded', function () {
        bindUi();

        if (!cfg.googleMapsKey) {
            document.getElementById('map').innerHTML = `
                <div class="map-key-missing">
                    <div>
                        <h5 class="fw-bold text-body mb-2">Google Maps API key required</h5>
                        <p class="mb-0" style="max-width: 420px;">
                            Add <code>GOOGLE_MAPS_API_KEY</code> to your <code>.env</code> file, then refresh this page.
                            Driver list on the left is already loaded from the database.
                        </p>
                    </div>
                </div>
            `;
        }
    });
})();
</script>
@endsection
