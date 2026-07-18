@php
$isNavbar = false;
$isFooter = true;
$isFlex = true;
$container = 'container-fluid';
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Live Map')

@section('content')
<!-- Include Leaflet CSS/JS for real maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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
        background-color: #696cff; /* Blue */
    }

    .status-indicator.idle {
        background-color: #71dd37; /* Green */
    }

    .status-indicator.offline {
        background-color: #8592a3; /* Grey */
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

    /* Custom Map Markers */
    .custom-marker {
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
    }

    .custom-marker.transit {
        border-color: #696cff;
    }

    .custom-marker.idle {
        border-color: #71dd37;
    }

    .custom-marker.offline {
        border-color: #8592a3;
    }

    .custom-marker img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .custom-marker .marker-status-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #ffffff;
    }

    .custom-marker.transit .marker-status-dot {
        background-color: #696cff;
    }

    .custom-marker.idle .marker-status-dot {
        background-color: #71dd37;
    }

    .custom-marker.offline .marker-status-dot {
        background-color: #8592a3;
    }

    /* Leaflet popup overrides */
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        padding: 4px;
    }

    .leaflet-popup-content {
        margin: 8px 12px;
        font-size: 0.85rem;
        line-height: 1.4;
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
</style>

<div class="tracking-wrapper">
    <!-- Top Full-Width Navbar -->
    <div class="bg-white border-bottom d-flex align-items-center justify-content-between" style="height: 70px; min-height: 70px; border-color: #e0e2e7 !important; z-index: 1000;">
        <!-- Right Content Block -->
        <div class="flex-grow-1 d-flex align-items-center justify-content-between px-4" style="height: 100%;">
            <!-- Header Page Title & Active Status badge -->
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-bold text-body" style="font-size: 1.25rem;">Live Map</h4>
                <span class="badge bg-label-success rounded-pill d-inline-flex align-items-center gap-1" style="text-transform: none; padding: 4px 8px; font-size: 0.72rem; font-weight: 500; background-color: rgba(113, 221, 55, 0.1) !important; color: #71dd37 !important;">
                    <span class="d-inline-block rounded-circle bg-success animate-pulse" style="width: 6px; height: 6px;"></span>
                    Live Tracking Active
                </span>
            </div>

            <!-- Navbar Actions -->
            <div class="d-flex align-items-center gap-3">
                <!-- Zone Filter -->
                <div class="position-relative d-flex align-items-center">
                    <i class="bx bx-layer text-muted position-absolute" style="left: 12px; font-size: 1.1rem; pointer-events: none;"></i>
                    <select class="form-select form-select-sm" id="map-zone-filter" style="width: 140px; padding-left: 36px; border-color: #d9dee3; font-size: 0.82rem; height: 38px; border-radius: 6px; cursor: pointer;">
                        <option value="all">All Zones</option>
                        <option value="manhattan">Manhattan Core</option>
                        <option value="brooklyn">Brooklyn Zone</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="position-relative d-flex align-items-center">
                    <i class="bx bx-filter text-muted position-absolute" style="left: 12px; font-size: 1.1rem; pointer-events: none;"></i>
                    <select class="form-select form-select-sm" id="map-status-filter" style="width: 140px; padding-left: 36px; border-color: #d9dee3; font-size: 0.82rem; height: 38px; border-radius: 6px; cursor: pointer;">
                        <option value="all">All Statuses</option>
                        <option value="transit">In Transit</option>
                        <option value="idle">Idle / Available</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>

                <!-- Bell Icon -->
                <div class="position-relative cursor-pointer d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bx bx-bell text-muted" style="font-size: 1.3rem;"></i>
                    <span class="position-absolute bg-danger rounded-circle" style="width: 6px; height: 6px; top: 8px; right: 8px;"></span>
                </div>

                <!-- Refresh Button -->
                <button class="btn btn-sm text-white d-flex align-items-center gap-1" id="btn-refresh-map" style="background-color: #ff7a00; border-color: #ff7a00; padding: 0 16px; font-weight: 600; border-radius: 6px; height: 38px; font-size: 0.82rem;">
                    <i class="bx bx-refresh" style="font-size: 1.15rem;"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Main Workspace Area -->
    <div class="d-flex flex-grow-1 w-100" style="min-height: 0; height: calc(100% - 70px);">
        <!-- Left Sidebar -->
        <div class="tracking-sidebar">
            <!-- Sidebar Stats (directly at top of sidebar) -->
            <div class="row g-2 px-3 my-3">
                <div class="col-4">
                    <div class="border rounded text-center py-2 px-1" style="background-color: #fff; border-color: #e0e2e7 !important; border-radius: 8px !important;">
                        <h4 class="mb-0 fw-bold text-body" style="font-size: 1.2rem;">28</h4>
                        <div class="text-muted" style="font-size: 0.72rem; font-weight: 500;">In Transit</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded text-center py-2 px-1" style="background-color: #fff; border-color: #e0e2e7 !important; border-radius: 8px !important;">
                        <h4 class="mb-0 fw-bold text-success" style="font-size: 1.2rem;">14</h4>
                        <div class="text-muted" style="font-size: 0.72rem; font-weight: 500;">Idle</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded text-center py-2 px-1" style="background-color: #fff; border-color: #e0e2e7 !important; border-radius: 8px !important;">
                        <h4 class="mb-0 fw-bold text-secondary" style="font-size: 1.2rem;">6</h4>
                        <div class="text-muted" style="font-size: 0.72rem; font-weight: 500;">Offline</div>
                    </div>
                </div>
            </div>

            <!-- Search input -->
            <div class="px-3 mb-3">
                <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #e0e2e7 !important; border-radius: 8px !important;">
                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted" style="font-size: 1.1rem;"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent ps-1" placeholder="Search driver or order..." id="search-driver-map" style="box-shadow: none; font-size: 0.85rem; height: 38px;">
                </div>
            </div>

            <!-- Driver List Container -->
            <div class="flex-grow-1 px-3" id="driver-map-list" style="overflow-y: auto; padding-bottom: 20px;">
                <!-- Rendered dynamically -->
            </div>

            <!-- Sidebar Footer -->
            <div class="mt-auto border-top px-3 py-2 bg-transparent d-flex justify-content-between align-items-center" style="font-size: 0.75rem; border-color: #e0e2e7 !important; min-height: 44px;">
                <span class="text-muted" id="showing-drivers-text">Showing 7 of 48 drivers</span>
                <a href="javascript:void(0);" style="color: #ff7a00; font-weight: 600;">View All</a>
            </div>
        </div>

        <!-- Map Area (Right) -->
        <div class="flex-grow-1 position-relative h-100" style="min-width: 0;">
            <!-- The actual map -->
            <div id="map" style="height: 100%; width: 100%;"></div>

            <!-- Floating Vertical Map Controls -->
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

            <!-- Floating Driver Profile Card -->
            <div id="driver-profile-card" class="driver-profile-card">
                <!-- Rendered dynamically -->
            </div>

            <!-- Floating Legend Card (Bottom Right) -->
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
                        <span class="d-inline-block border border-danger rounded" style="width: 10px; height: 10px; border-width: 2px !important; background-color: rgba(255, 62, 29, 0.1);"></span>
                        <span class="text-muted">Delivery Zone</span>
                    </div>
                </div>
            </div>

            <!-- Floating Live Stats Card (Bottom Left) -->
            <div class="map-stats-card bg-white p-3 border rounded shadow-sm" style="position: absolute; bottom: 20px; left: 20px; width: 220px; z-index: 1000; border-color: #e0e2e7 !important; border-radius: 12px !important;">
                <h6 class="mb-2 fw-bold text-body" style="font-size: 0.8rem; letter-spacing: 0.5px;">Live Stats</h6>
                <div class="d-flex flex-column gap-2" style="font-size: 0.75rem;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Active Drivers</span>
                        <span class="fw-bold text-body" style="font-size: 0.82rem;">42</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">In Transit</span>
                        <span class="fw-bold text-primary" style="font-size: 0.82rem; color: #696cff !important;">28</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Orders Today</span>
                        <span class="fw-bold text-body" style="font-size: 0.82rem;">1,248</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Avg. ETA</span>
                        <span class="fw-bold" style="font-size: 0.82rem; color: #ff7a00;">14 min</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initial coordinates (Center of Manhattan/New York)
    const map = L.map('map', {
        zoomControl: false // Disable Leaflet default zoom controls
    }).setView([40.7150, -74.0020], 13);

    // Dynamic tile management
    let currentTile = 'osm';
    const osmTile = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    });
    const darkTile = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
    });
    osmTile.addTo(map);

    // Add a Delivery Zone polygon (e.g., Manhattan center boundary)
    const zoneCoords = [
        [40.7350, -74.0150],
        [40.7300, -73.9850],
        [40.7020, -73.9900],
        [40.7080, -74.0180]
    ];
    const deliveryZone = L.polygon(zoneCoords, {
        color: '#ff3e1d', // Red border
        fillColor: '#ff3e1d',
        fillOpacity: 0.06,
        weight: 2,
        dashArray: '6, 6'
    }).addTo(map);
    deliveryZone.bindPopup('<strong>Manhattan Core Delivery Zone</strong><br>Delivery restrictions may apply.');

    // Driver Data Source
    const drivers = [
        {
            id: 1,
            name: "Mike Johnson",
            status: "Transit",
            zone: "manhattan",
            orderId: "#ORD-8924",
            distance: "2.4 km away",
            distanceNum: "2.4",
            eta: "12 mins",
            speed: "32 km/h",
            speedNum: "32",
            avatar: "5.png",
            lat: 40.7128,
            lng: -74.0060,
            destination: "123 Main St",
            rating: "4.8",
            statusClass: "transit"
        },
        {
            id: 2,
            name: "Sarah Connor",
            status: "Transit",
            zone: "manhattan",
            orderId: "#ORD-8925",
            distance: "1.1 km away",
            distanceNum: "1.1",
            eta: "5 mins",
            speed: "28 km/h",
            speedNum: "28",
            avatar: "6.png",
            lat: 40.7250,
            lng: -74.0150,
            destination: "789 Broadway St",
            rating: "4.9",
            statusClass: "transit"
        },
        {
            id: 3,
            name: "David Smith",
            status: "Transit",
            zone: "manhattan",
            orderId: "#ORD-8930",
            distance: "3.7 km away",
            distanceNum: "3.7",
            eta: "20 mins",
            speed: "40 km/h",
            speedNum: "40",
            avatar: "7.png",
            lat: 40.7050,
            lng: -73.9960,
            destination: "456 Elm St",
            rating: "4.5",
            statusClass: "transit"
        },
        {
            id: 4,
            name: "Emily Rodriguez",
            status: "Idle",
            zone: "manhattan",
            details: "Available &middot; Downtown Zone",
            avatar: "2.png",
            lat: 40.7180,
            lng: -74.0010,
            rating: "4.9",
            statusClass: "idle"
        },
        {
            id: 5,
            name: "James Park",
            status: "Idle",
            zone: "manhattan",
            details: "Available &middot; Northwest District",
            avatar: "3.png",
            lat: 40.7290,
            lng: -73.9890,
            rating: "4.7",
            statusClass: "idle"
        },
        {
            id: 6,
            name: "Carlos Mendes",
            status: "Transit",
            zone: "manhattan",
            orderId: "#ORD-8932",
            distance: "0.8 km away",
            distanceNum: "0.8",
            eta: "3 mins",
            speed: "22 km/h",
            speedNum: "22",
            avatar: "4.png",
            lat: 40.7110,
            lng: -74.0130,
            destination: "101 Pine St",
            rating: "4.6",
            statusClass: "transit"
        },
        {
            id: 7,
            name: "Lisa Chen",
            status: "Offline",
            zone: "brooklyn",
            details: "Last seen 45 mins ago",
            avatar: "1.png",
            lat: 40.7200,
            lng: -73.9750,
            rating: "4.4",
            statusClass: "offline"
        }
    ];

    const markers = {};

    // Render left list
    const listContainer = document.getElementById('driver-map-list');
    
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
                            <img src="{{ asset('assets/img/avatars') }}/${d.avatar}" alt="${d.name}">
                            <span class="status-indicator ${d.status.toLowerCase()}"></span>
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

    // Render list initially
    function renderDriversList() {
        listContainer.innerHTML = drivers.map(d => getDriverCardHtml(d)).join('');
    }
    renderDriversList();

    // Map Markers Generation
    drivers.forEach(d => {
        const markerIcon = L.divIcon({
            className: 'custom-marker-container',
            html: `
                <div class="custom-marker ${d.status.toLowerCase()}">
                    <img src="{{ asset('assets/img/avatars') }}/${d.avatar}" alt="${d.name}">
                    <span class="marker-status-dot"></span>
                </div>
            `,
            iconSize: [42, 42],
            iconAnchor: [21, 42],
            popupAnchor: [0, -42]
        });

        const marker = L.marker([d.lat, d.lng], { icon: markerIcon }).addTo(map);
        marker.bindPopup(`<strong>${d.name}</strong><br><span style="font-size:0.75rem; color:#8592a3;">Status: ${d.status}</span>`);
        markers[d.id] = marker;

        // Custom Marker click handler
        marker.on('click', function () {
            selectDriver(d.id, false); // Select without centering (since clicked)
        });
    });

    // Profile Card Rendering Logic
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
                        <span class="fw-bold text-body">${driver.details.replace('Available &middot; ', '')}</span>
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
                    <img src="{{ asset('assets/img/avatars') }}/${driver.avatar}" alt="${driver.name}">
                    <span class="status-indicator ${driver.status.toLowerCase()}"></span>
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

        // Hook up profile close button
        document.getElementById('close-profile-card-btn').addEventListener('click', function() {
            cardEl.style.display = 'none';
            // Deselect card in list
            document.querySelectorAll('.driver-item-card.active').forEach(c => c.classList.remove('active'));
            // Close map popup
            map.closePopup();
        });
    }

    // Driver Selection Logic (Shared by List Clicks and Marker Clicks)
    window.selectDriver = function(id, flyToMap = true) {
        const d = drivers.find(drv => drv.id === id);
        if (!d) return;

        // Highlight selected list card
        document.querySelectorAll('.driver-item-card').forEach(card => card.classList.remove('active'));
        const cardEl = document.getElementById(`driver-card-${id}`);
        if (cardEl) {
            cardEl.classList.add('active');
            // Scroll card into view inside the list container
            cardEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Render floating profile details card
        renderDriverProfileCard(d);

        // Pan map and open standard marker popup
        if (flyToMap) {
            map.flyTo([d.lat, d.lng], 14, {
                animate: true,
                duration: 1.2
            });
            
            if (markers[id]) {
                markers[id].openPopup();
            }
        }
    };

    // Auto-select based on query parameter 'driver' or 'order' if provided, otherwise default to Mike Johnson (id: 1)
    const urlParams = new URLSearchParams(window.location.search);
    const driverParam = urlParams.get('driver');
    const orderParam = urlParams.get('order');

    if (driverParam) {
        const found = drivers.find(d => d.name.toLowerCase() === driverParam.toLowerCase());
        if (found) {
            setTimeout(() => {
                selectDriver(found.id);
            }, 600);
        } else {
            setTimeout(() => {
                selectDriver(1);
            }, 400);
        }
    } else if (orderParam) {
        const found = drivers.find(d => d.orderId && d.orderId.toLowerCase() === orderParam.toLowerCase());
        if (found) {
            setTimeout(() => {
                selectDriver(found.id);
            }, 600);
        } else {
            setTimeout(() => {
                selectDriver(1);
            }, 400);
        }
    } else {
        setTimeout(() => {
            selectDriver(1);
        }, 400);
    }

    // Search and filtering implementation
    function filterDrivers() {
        const searchQuery = document.getElementById('search-driver-map').value.toLowerCase();
        const statusFilter = document.getElementById('map-status-filter').value;
        const zoneFilter = document.getElementById('map-zone-filter').value;
        let visibleCount = 0;

        drivers.forEach(d => {
            const cardEl = document.getElementById(`driver-card-${d.id}`);
            
            const matchesSearch = d.name.toLowerCase().includes(searchQuery) || 
                                  (d.orderId && d.orderId.toLowerCase().includes(searchQuery));
                                  
            const matchesStatus = (statusFilter === 'all') || 
                                  (d.status.toLowerCase() === statusFilter);

            const matchesZone = (zoneFilter === 'all') || 
                                (d.zone === zoneFilter);

            if (matchesSearch && matchesStatus && matchesZone) {
                if (cardEl) cardEl.style.display = 'block';
                visibleCount++;
                if (markers[d.id] && !map.hasLayer(markers[d.id])) {
                    map.addLayer(markers[d.id]);
                }
            } else {
                if (cardEl) cardEl.style.display = 'none';
                if (markers[d.id]) {
                    map.removeLayer(markers[d.id]);
                }
            }
        });

        document.getElementById('showing-drivers-text').innerText = `Showing ${visibleCount} of ${drivers.length} drivers`;
    }

    // Attach filter event listeners
    document.getElementById('search-driver-map').addEventListener('input', filterDrivers);
    document.getElementById('map-status-filter').addEventListener('change', filterDrivers);
    document.getElementById('map-zone-filter').addEventListener('change', filterDrivers);

    // Custom Map Controls Event Listeners
    document.getElementById('control-zoom-in').addEventListener('click', function() {
        map.zoomIn();
    });
    
    document.getElementById('control-zoom-out').addEventListener('click', function() {
        map.zoomOut();
    });

    document.getElementById('control-recenter').addEventListener('click', function() {
        // Find visible markers bounds
        const group = [];
        drivers.forEach(d => {
            const statusFilter = document.getElementById('map-status-filter').value;
            const zoneFilter = document.getElementById('map-zone-filter').value;
            
            const matchesStatus = (statusFilter === 'all' || d.status.toLowerCase() === statusFilter);
            const matchesZone = (zoneFilter === 'all' || d.zone === zoneFilter);
            
            if (matchesStatus && matchesZone) {
                group.push([d.lat, d.lng]);
            }
        });
        if (group.length > 0) {
            map.fitBounds(group, { padding: [50, 50] });
        } else {
            map.setView([40.7150, -74.0020], 13);
        }
    });

    document.getElementById('control-layers').addEventListener('click', function() {
        if (currentTile === 'osm') {
            map.removeLayer(osmTile);
            darkTile.addTo(map);
            currentTile = 'dark';
            this.style.color = '#ff7a00';
        } else {
            map.removeLayer(darkTile);
            osmTile.addTo(map);
            currentTile = 'osm';
            this.style.color = '#566a7f';
        }
    });

    document.getElementById('control-fullscreen').addEventListener('click', function() {
        const wrapper = document.querySelector('.tracking-wrapper');
        if (!document.fullscreenElement) {
            wrapper.requestFullscreen().then(() => {
                this.style.color = '#ff7a00';
            }).catch(err => {
                console.error(`Error enabling fullscreen: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
            this.style.color = '#566a7f';
        }
    });

    // Handle Esc key / native exit of fullscreen to reset button color
    document.addEventListener('fullscreenchange', function() {
        const btn = document.getElementById('control-fullscreen');
        if (btn) {
            if (document.fullscreenElement) {
                btn.style.color = '#ff7a00';
            } else {
                btn.style.color = '#566a7f';
            }
        }
    });

    // Refresh simulation action handler
    document.getElementById('btn-refresh-map').addEventListener('click', function() {
        const refreshBtn = this;
        const icon = refreshBtn.querySelector('i');
        
        icon.classList.add('bx-spin');
        refreshBtn.disabled = true;

        setTimeout(() => {
            drivers.forEach(d => {
                if (d.status !== 'Offline') {
                    // Slight variation in location simulating active movement
                    const latOffset = (Math.random() - 0.5) * 0.002;
                    const lngOffset = (Math.random() - 0.5) * 0.002;
                    
                    d.lat += latOffset;
                    d.lng += lngOffset;

                    if (markers[d.id]) {
                        markers[d.id].setLatLng([d.lat, d.lng]);
                    }
                }
            });

            // Recalculate simulation values
            drivers.forEach(d => {
                if (d.status === 'Transit') {
                    const randomDistance = (parseFloat(d.distanceNum) + (Math.random() - 0.5) * 0.4).toFixed(1);
                    d.distanceNum = Math.max(0.1, randomDistance).toString();
                    d.distance = `${d.distanceNum} km away`;
                    
                    const randomEta = Math.max(1, parseInt(d.eta) + Math.round((Math.random() - 0.5) * 2));
                    d.eta = `${randomEta} mins`;
                }
            });

            renderDriversList();
            filterDrivers();

            // Sync open profile details
            const activeCard = document.querySelector('.driver-item-card.active');
            if (activeCard) {
                const activeId = parseInt(activeCard.id.replace('driver-card-', ''));
                const activeDriver = drivers.find(drv => drv.id === activeId);
                if (activeDriver) {
                    renderDriverProfileCard(activeDriver);
                }
            }

            icon.classList.remove('bx-spin');
            refreshBtn.disabled = false;
        }, 800);
    });

    // Invalidate map size to solve height quirks on loading
    setTimeout(() => {
        map.invalidateSize();
    }, 200);
});
</script>
@endsection
