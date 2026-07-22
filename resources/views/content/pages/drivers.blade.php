@php
$isNavbar = false;
$type = $driverType ?? 'store';
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', $type === 'store' ? 'Store Drivers' : 'Zone-wise Drivers')
@section('page-title', $type === 'store' ? 'Store Drivers Management' : 'Zone-wise Drivers Management')

@section('content')
<style>
    /* Premium Orange & Theme Styles */
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
    
    .bell-notification {
        position: relative;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        width: 42px;
        height: 42px;
        background-color: #ffffff;
        border: 1px solid #e0e2e7;
        transition: all 0.2s ease-in-out;
        color: #566a7f;
    }
    .bell-notification:hover {
        background-color: #f8f9fa;
        border-color: #d1d3e2;
        color: #ff7a00;
    }
    .bell-notification .notification-dot {
        position: absolute;
        width: 8px;
        height: 8px;
        background-color: #ff3e1d;
        border-radius: 50%;
        top: 11px;
        right: 12px;
        border: 2px solid #ffffff;
    }

    /* Summary KPI Cards */
    .summary-card {
        border-radius: 12px;
        background-color: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    /* Working days (same look as approval review) */
    .day-badge {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.82rem;
        margin-right: 6px;
        margin-bottom: 4px;
        border: none;
        padding: 0;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        user-select: none;
    }
    .day-badge.active {
        background-color: #ff7a00;
        color: #ffffff;
    }
    .day-badge.inactive {
        background-color: #f1f5f9;
        color: #94a3b8;
    }
    .day-badge:hover {
        opacity: 0.9;
        transform: scale(1.05);
    }
    .day-badge:focus-visible {
        outline: 2px solid rgba(255, 122, 0, 0.45);
        outline-offset: 2px;
    }

    /* Filter Pills */
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

    /* Status Dot Badges */
    .status-badge-custom {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 6px;
        text-transform: capitalize;
    }
    .status-badge-custom.active,
    .status-badge-custom.online {
        background-color: rgba(40, 199, 111, 0.1) !important;
        color: #28c76f !important;
    }
    .status-badge-custom.active .dot,
    .status-badge-custom.online .dot {
        background-color: #28c76f;
    }
    .status-badge-custom.offline {
        background-color: rgba(133, 146, 163, 0.1) !important;
        color: #8592a3 !important;
    }
    .status-badge-custom.offline .dot {
        background-color: #8592a3;
    }
    .status-badge-custom.transit {
        background-color: rgba(0, 186, 209, 0.12) !important;
        color: #00bad1 !important;
    }
    .status-badge-custom.transit .dot {
        background-color: #00bad1;
    }
    .status-badge-custom.pending {
        background-color: rgba(255, 171, 0, 0.1) !important;
        color: #ffab00 !important;
    }
    .status-badge-custom.pending .dot {
        background-color: #ffab00;
    }
    .status-badge-custom.suspended {
        background-color: rgba(255, 62, 29, 0.1) !important;
        color: #ff3e1d !important;
    }
    .status-badge-custom.suspended .dot {
        background-color: #ff3e1d;
    }
    .status-badge-custom.rejected {
        background-color: rgba(234, 84, 85, 0.12) !important;
        color: #ea5455 !important;
    }
    .status-badge-custom.rejected .dot {
        background-color: #ea5455;
    }
    .dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .btn-availability-toggle {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-availability-toggle.online {
        color: #28c76f;
        border-color: rgba(40, 199, 111, 0.45) !important;
        background-color: rgba(40, 199, 111, 0.08);
    }
    .btn-availability-toggle.online:hover:not(:disabled) {
        color: #fff;
        background-color: #28c76f;
        border-color: #28c76f !important;
    }
    .btn-availability-toggle.offline {
        color: #8592a3;
        border-color: #e0e2e7 !important;
        background-color: #fff;
    }
    .btn-availability-toggle.offline:hover:not(:disabled) {
        color: #fff;
        background-color: #8592a3;
        border-color: #8592a3 !important;
    }
    .btn-availability-toggle.transit {
        color: #00bad1;
        border-color: rgba(0, 186, 209, 0.45) !important;
        background-color: rgba(0, 186, 209, 0.08);
    }
    .btn-availability-toggle.transit:hover:not(:disabled) {
        color: #fff;
        background-color: #00bad1;
        border-color: #00bad1 !important;
    }
    .btn-availability-toggle:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .availability-dropdown .dropdown-menu {
        min-width: 168px;
        padding: 6px;
        border-radius: 10px;
        border-color: #eef2f7;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1);
    }
    .availability-dropdown .dropdown-item {
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 8px 10px;
        color: #475569;
    }
    .availability-dropdown .dropdown-item.active,
    .availability-dropdown .dropdown-item:active {
        background-color: rgba(255, 122, 0, 0.08);
        color: #ff7a00;
    }
    .availability-dropdown .dropdown-item.disabled {
        opacity: 0.7;
    }
    .availability-dropdown .dropdown-header {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #94a3b8;
        padding: 4px 10px 6px;
    }

    .bulk-actions-bar {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 12px 16px;
        margin-bottom: 12px;
        background: #fff8f2;
        border: 1px solid rgba(255, 122, 0, 0.25);
        border-radius: 10px;
    }
    .bulk-actions-bar.is-visible {
        display: flex;
    }
    .bulk-actions-bar .bulk-count {
        font-size: 0.88rem;
        font-weight: 600;
        color: #32475c;
    }
    .bulk-actions-bar .bulk-count span {
        color: #ff7a00;
    }
    .bulk-actions-bar .btn {
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        height: 34px;
        padding: 6px 12px;
    }

    /* Table Styles */
    #drivers-table th {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #8592a3;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e0e2e7;
    }
    #drivers-table td {
        border-bottom: 1px solid #f1f3f5;
    }
    .table-hover tbody tr {
        transition: background-color 0.2s ease-in-out;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(255, 122, 0, 0.02) !important;
    }

    /* Grid View Styles */
    .driver-grid-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .driver-grid-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06) !important;
    }

    /* Modal Icons & Tabs styling */
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

    .modal-nav-tabs {
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        gap: 1.25rem;
        padding: 0 1.5rem;
        margin-bottom: 1.5rem;
    }
    .modal-nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        padding: 10px 4px;
        color: #64748b;
        font-weight: 500;
        font-size: 0.88rem;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .modal-nav-tabs .nav-link:hover {
        color: #ff7a00;
    }
    .modal-nav-tabs .nav-link.active {
        color: #ff7a00;
        border-bottom-color: #ff7a00;
        font-weight: 600;
    }

    /* Circular profile picture upload box */
    .profile-upload-wrapper {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .avatar-preview-container {
        position: relative;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        border: 1px solid #e0e2e7;
        background-color: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
    }
    .avatar-preview-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .avatar-preview-container .avatar-placeholder-icon {
        font-size: 2.2rem;
        color: #cbd5e1;
    }
    .avatar-upload-badge {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #ff7a00;
        border: 2px solid #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 0.8rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Compact cute toasts */
    .app-toast-stack {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1090;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        pointer-events: none;
    }
    .app-toast {
        pointer-events: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        max-width: min(280px, calc(100vw - 2rem));
        padding: 8px 12px 8px 8px;
        background: #ffffff;
        border: 1px solid #eef2f7;
        border-radius: 999px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        color: #475569;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.3;
        animation: app-toast-in 0.28s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .app-toast.is-leaving {
        animation: app-toast-out 0.22s ease forwards;
    }
    .app-toast__icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.85rem;
    }
    .app-toast__msg {
        padding-right: 4px;
        word-break: break-word;
    }
    .app-toast--success .app-toast__icon {
        background: #e8faf0;
        color: #28c76f;
    }
    .app-toast--error .app-toast__icon {
        background: #fdeeee;
        color: #ea5455;
    }
    .app-toast--warning .app-toast__icon {
        background: #fff5e5;
        color: #ff9f43;
    }
    .app-toast--info .app-toast__icon {
        background: #fff4eb;
        color: #ff7a00;
    }
    @keyframes app-toast-in {
        from { opacity: 0; transform: translateY(-8px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes app-toast-out {
        from { opacity: 1; transform: translateY(0) scale(1); }
        to { opacity: 0; transform: translateY(-6px) scale(0.96); }
    }
</style>

@php
$storesList = $stores ?? collect();
$zonesList = $zones ?? collect();
@endphp

<!-- Page Header Section -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
        <h3 class="mb-0 fw-bold text-body" style="font-size: 1.6rem; font-family: 'Public Sans', sans-serif;" id="page-main-title">Store Drivers</h3>
        <span class="badge rounded-pill badge-orange-light" id="header-total-badge" style="font-size: 0.8rem; font-weight: 600; padding: 6px 12px;">0 Total</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- Notification Bell -->
        <div class="bell-notification">
            <i class="bx bx-bell" style="font-size: 1.3rem;"></i>
            <span class="notification-dot animate-pulse"></span>
        </div>
        
        <!-- Add Driver Button (Shown conditionally or on both) -->
        <button class="btn btn-primary-orange d-flex align-items-center gap-2" onclick="openAddModal()" id="add-driver-btn" style="padding: 10px 20px; border-radius: 8px;">
            <i class="bx bx-plus" style="font-size: 1.15rem;"></i>
            <span>Add Driver</span>
        </button>
    </div>
</div>

<!-- KPI Cards Summary Row -->
<div class="row mb-4">
    <!-- Online Card -->
    <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
        <div class="card shadow-none border summary-card">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 48px; height: 48px; background-color: rgba(40, 199, 111, 0.1); color: #28c76f;">
                    <i class="bx bx-check-circle" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Online</small>
                    <h4 class="mb-0 fw-bold text-body mt-1" id="card-active-val" style="font-size: 1.6rem; line-height: 1.2;">0</h4>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Offline Card -->
    <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
        <div class="card shadow-none border summary-card">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 48px; height: 48px; background-color: rgba(133, 146, 163, 0.1); color: #8592a3;">
                    <i class="bx bx-minus-circle" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Offline</small>
                    <h4 class="mb-0 fw-bold text-body mt-1" id="card-offline-val" style="font-size: 1.6rem; line-height: 1.2;">0</h4>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transit Card -->
    <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
        <div class="card shadow-none border summary-card">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 48px; height: 48px; background-color: rgba(0, 186, 209, 0.12); color: #00bad1;">
                    <i class="bx bx-trip" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Transit</small>
                    <h4 class="mb-0 fw-bold text-body mt-1" id="card-transit-val" style="font-size: 1.6rem; line-height: 1.2;">0</h4>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Suspended Card -->
    <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
        <div class="card shadow-none border summary-card">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 48px; height: 48px; background-color: rgba(255, 62, 29, 0.1); color: #ff3e1d;">
                    <i class="bx bx-block" style="font-size: 1.4rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Suspended</small>
                    <h4 class="mb-0 fw-bold text-body mt-1" id="card-suspended-val" style="font-size: 1.6rem; line-height: 1.2;">0</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search Toolbar -->
<div class="card shadow-none border mb-4" style="border-radius: 12px;">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 flex-wrap">
            <!-- Search Input & Status Pills -->
            <div class="d-flex align-items-center gap-3 flex-grow-1 flex-wrap">
                <!-- Search bar -->
                <div class="input-group input-group-merge border rounded overflow-hidden" style="width: 320px; border-color: #e0e2e7 !important; border-radius: 8px !important;">
                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted" style="font-size: 1.1rem;"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent ps-1" placeholder="Search by name, ID, phone..." id="search-driver" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                </div>
                
                <!-- Status pills -->
                <div class="d-flex align-items-center gap-2 flex-wrap" id="status-filter-pills">
                    <button type="button" class="btn btn-pill filter-pill active" data-status="all">All</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="online">Online</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="transit">Transit</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="offline">Offline</button>
                    @if ($type !== 'zone')
                    <button type="button" class="btn btn-pill filter-pill" data-status="pending">Pending Review</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="rejected">Rejected</button>
                    @endif
                    <button type="button" class="btn btn-pill filter-pill" data-status="suspended">Suspended</button>
                </div>
            </div>
            
            <!-- Sort and Filter actions -->
            <div class="d-flex align-items-center gap-2">
                <!-- Sort dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 border" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 16px; font-size: 0.88rem; height: 38px; color: #566a7f; background-color: #ffffff;">
                        <span id="current-sort-label">Sort: Newest</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item active" href="javascript:void(0);" data-sort="newest" onclick="handleSort('newest', 'Sort: Newest')">Sort: Newest</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="oldest" onclick="handleSort('oldest', 'Sort: Oldest')">Sort: Oldest</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="name-asc" onclick="handleSort('name-asc', 'Name (A-Z)')">Name (A-Z)</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="name-desc" onclick="handleSort('name-desc', 'Name (Z-A)')">Name (Z-A)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="deliveries-desc" onclick="handleSort('deliveries-desc', 'Most Deliveries')">Most Deliveries</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="rating-desc" onclick="handleSort('rating-desc', 'Highest Rating')">Highest Rating</a></li>
                    </ul>
                </div>
                
                <!-- Filters button -->
                <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2 border" id="advanced-filter-btn" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 16px; font-size: 0.88rem; height: 38px; color: #566a7f; background-color: #ffffff;">
                    <i class="bx bx-slider-alt" style="font-size: 1.1rem;"></i>
                    <span>Filters</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Header Stats Info -->
<div class="d-flex align-items-center justify-content-between mb-3 px-1">
    <div class="text-muted" style="font-size: 0.88rem;" id="showing-text">
        Showing <span class="fw-semibold text-body" id="showing-range">1-9</span> of <span class="fw-semibold text-body" id="showing-total">0</span> drivers
    </div>
    <div class="d-flex align-items-center gap-2">
        <!-- List / Grid View Toggles -->
        <button type="button" class="btn btn-icon btn-sm rounded" id="btn-view-list" onclick="toggleView('list')" style="color: #ff7a00; background-color: rgba(255, 122, 0, 0.1); width: 32px; height: 32px;">
            <i class="bx bx-list-ul" style="font-size: 1.15rem;"></i>
        </button>
        <button type="button" class="btn btn-icon btn-sm rounded" id="btn-view-grid" onclick="toggleView('grid')" style="color: #566a7f; background-color: transparent; width: 32px; height: 32px;">
            <i class="bx bx-grid-alt" style="font-size: 1.15rem;"></i>
        </button>
    </div>
</div>

<!-- Bulk selection actions -->
<div class="bulk-actions-bar" id="bulk-actions-bar">
    <div class="bulk-count">
        <i class="bx bx-check-square me-1 text-primary-orange" style="color: #ff7a00;"></i>
        <span id="bulk-selected-count">0</span> selected
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bx bx-wifi me-1"></i> Availability
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Set availability</h6></li>
                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkSetAvailability('Online')"><i class="bx bx-wifi me-2 text-success"></i> Online</a></li>
                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkSetAvailability('Transit')"><i class="bx bx-trip me-2" style="color:#00bad1;"></i> Transit</a></li>
                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkSetAvailability('Offline')"><i class="bx bx-wifi-off me-2 text-secondary"></i> Offline</a></li>
            </ul>
        </div>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bx bx-user-check me-1"></i> Account Status
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Set account status</h6></li>
                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkSetAccountStatus('Active')"><i class="bx bx-check-circle me-2 text-success"></i> Active</a></li>
                @if ($type !== 'zone')
                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkSetAccountStatus('Pending')"><i class="bx bx-time me-2 text-warning"></i> Pending Review</a></li>
                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkSetAccountStatus('Rejected')"><i class="bx bx-x-circle me-2 text-danger"></i> Rejected</a></li>
                @endif
                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkSetAccountStatus('Suspended')"><i class="bx bx-block me-2 text-danger"></i> Suspended</a></li>
            </ul>
        </div>
        <button type="button" class="btn btn-outline-danger" onclick="bulkDeleteDrivers()">
            <i class="bx bx-trash me-1"></i> Delete
        </button>
        <button type="button" class="btn btn-link text-muted px-2" onclick="clearDriverSelection()" title="Clear selection">
            Clear
        </button>
    </div>
</div>

<!-- List View Container (Table) -->
<div id="list-view-container">
    <div class="card shadow-none border" style="border-radius: 12px; background-color: #ffffff; overflow: hidden;">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0" id="drivers-table">
                <thead>
                    <tr class="table-light border-bottom">
                        <th style="width: 40px; padding: 16px 20px;">
                            <div class="form-check m-0">
                                <input class="form-check-input select-all-checkbox" type="checkbox" id="selectAll">
                            </div>
                        </th>
                        <th class="fw-bold">Driver</th>
                        <th class="fw-bold">Driver ID</th>
                        <th class="fw-bold">Status</th>
                        <th class="fw-bold" id="table-header-location">Assigned Store</th>
                        <th class="fw-bold">Rating</th>
                        <th class="fw-bold">Deliveries</th>
                        <th class="fw-bold">Joined</th>
                        <th class="fw-bold text-end" style="padding-right: 24px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="drivers-tbody" class="table-border-bottom-0">
                    <!-- Loaded dynamically via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Grid View Container (Cards) - Hidden by default -->
<div id="grid-view-container" class="row d-none">
    <!-- Loaded dynamically via JavaScript -->
</div>

<!-- Add / Edit Driver Modal (Figma Styled Tabs) -->
<div class="modal fade" id="driverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 620px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header border-bottom-0 pb-2 d-flex align-items-start justify-content-between p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon">
                        <i class="bx bx-user-plus" id="headerIconSymbol" style="font-size: 1.4rem;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0" id="modalTitle" style="font-size: 1.15rem;">Add New Driver</h5>
                        <small class="text-muted" id="modalSubtitle" style="font-size: 0.85rem;">Fill in the details to register a new driver</small>
                    </div>
                </div>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="driverForm">
                <input type="hidden" id="driver-action-type" value="add">
                <input type="hidden" id="edit-driver-id-hidden">
                
                <!-- Modal Tabs -->
                <ul class="nav modal-nav-tabs" role="tablist" id="modalTabsList">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#tab-personal-info" type="button" role="tab" aria-controls="tab-personal-info" aria-selected="true">
                            <i class="bx bx-user" style="font-size: 1.1rem;"></i> Personal Info
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="vehicle-tab" data-bs-toggle="tab" data-bs-target="#tab-vehicle-details" type="button" role="tab" aria-controls="tab-vehicle-details" aria-selected="false">
                            <i class="bx bx-car" style="font-size: 1.1rem;"></i> Vehicle Details
                        </button>
                    </li>
                    @if ($type === 'zone')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="service-area-tab" data-bs-toggle="tab" data-bs-target="#tab-service-area" type="button" role="tab" aria-controls="tab-service-area" aria-selected="false">
                            <i class="bx bx-map-pin" style="font-size: 1.1rem;"></i> Service Area
                        </button>
                    </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="docs-tab" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button" role="tab" aria-controls="tab-documents" aria-selected="false">
                            <i class="bx bx-file" style="font-size: 1.1rem;"></i> Documents
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="modal-body py-1 px-4">
                    <div class="tab-content p-0 border-0 shadow-none">
                        
                        <!-- TAB 1: Personal Info -->
                        <div class="tab-pane fade show active" id="tab-personal-info" role="tabpanel" aria-labelledby="personal-tab">
                            <!-- Profile Picture Row -->
                            <div class="profile-upload-wrapper">
                                <div class="avatar-preview-container" id="avatarPreviewBox" onclick="document.getElementById('driver-avatar-file').click()">
                                    <img id="avatarPreviewImg" src="" style="display: none;">
                                    <i class="bx bx-user avatar-placeholder-icon" id="avatarPlaceholderIcon"></i>
                                    <div class="avatar-upload-badge">
                                        <i class="bx bx-camera text-white" style="font-size: 0.85rem;"></i>
                                    </div>
                                </div>
                                <input type="file" id="driver-avatar-file" accept="image/*" class="d-none">
                                <div>
                                    <h6 class="mb-1 fw-bold text-body" style="font-size: 0.95rem;">Profile Photo</h6>
                                    <small class="text-muted d-block" style="font-size: 0.8rem;">Upload a clear face photo. JPG or PNG, max 5MB.</small>
                                </div>
                            </div>

                            <!-- Name Columns -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="driver-first-name" class="form-label text-body fw-semibold">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="driver-first-name" placeholder="e.g. Alex" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-last-name" class="form-label text-body fw-semibold">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="driver-last-name" placeholder="e.g. Smith" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                            </div>

                            <!-- Contact info (Email & Phone Number) -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="driver-email" class="form-label text-body fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-envelope text-muted" style="font-size: 1.1rem;"></i></span>
                                        <input type="email" class="form-control border-0 bg-transparent ps-2" id="driver-email" placeholder="driver@email.com" required style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-phone" class="form-label text-body fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-phone text-muted" style="font-size: 1.1rem;"></i></span>
                                        <span class="input-group-text border-0 bg-transparent pe-1 ps-1 fw-semibold text-body" style="font-size: 0.88rem;">+91</span>
                                        <div style="width: 1px; background-color: #d9dee3; margin: 8px 0; height: 22px;"></div>
                                        <input type="tel" class="form-control border-0 bg-transparent ps-2" id="driver-phone" placeholder="98765 43210" required pattern="[0-9]{10}" maxlength="10" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                    </div>
                                </div>
                            </div>

                            <!-- DOB & Gender -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="driver-dob" class="form-label text-body fw-semibold">Date of Birth</label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-calendar text-muted" style="font-size: 1.1rem;"></i></span>
                                        <input type="date" class="form-control border-0 bg-transparent ps-2" id="driver-dob" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-gender" class="form-label text-body fw-semibold">Gender</label>
                                    <select class="form-select" id="driver-gender" style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="" disabled selected>Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Home Address -->
                            <div class="mb-3">
                                <label for="driver-address" class="form-label text-body fw-semibold">Home Address</label>
                                <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-map-pin text-muted" style="font-size: 1.1rem;"></i></span>
                                    <input type="text" class="form-control border-0 bg-transparent ps-2" id="driver-address" placeholder="Street address, city, state, ZIP" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                </div>
                            </div>

                            <!-- Assigned Store/Zone & Shift Timing -->
                            <div class="row mb-3">
                                <!-- Zone Selector -->
                                <div class="col-md-6" id="zone-select-container">
                                    <label for="driver-zone" class="form-label text-body fw-semibold">Assigned Zone <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-pin text-muted" style="font-size: 1.1rem;"></i></span>
                                        <select class="form-select border-0 bg-transparent ps-2" id="driver-zone" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                            <option value="" disabled selected>Select zone</option>
                                            @foreach($zonesList as $zoneOption)
                                                <option value="{{ $zoneOption->id }}" data-code="{{ $zoneOption->code }}">{{ $zoneOption->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!-- Store Selector -->
                                <div class="col-md-6" id="store-select-container" style="display: none;">
                                    <label for="driver-store" class="form-label text-body fw-semibold">Assigned Store <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-store text-muted" style="font-size: 1.1rem;"></i></span>
                                        <select class="form-select border-0 bg-transparent ps-2" id="driver-store" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                            <option value="" disabled selected>Select store</option>
                                            @foreach($storesList as $storeOption)
                                                <option value="{{ $storeOption->id }}">{{ $storeOption->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!-- Shift timings -->
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-shift" class="form-label text-body fw-semibold">Shift Timing <span class="text-danger">*</span></label>
                                    <select class="form-select" id="driver-shift" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="Morning Shift (6:00 AM - 2:00 PM)" selected>Morning Shift (6:00 AM - 2:00 PM)</option>
                                        <option value="Evening Shift (2:00 PM - 10:00 PM)">Evening Shift (2:00 PM - 10:00 PM)</option>
                                        <option value="Night Shift (10:00 PM - 6:00 AM)">Night Shift (10:00 PM - 6:00 AM)</option>
                                        <option value="Full Time (Flexible)">Full Time (Flexible)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Working Days (shown with Shift Timing, same as approval view) -->
                            <div class="mb-3" id="working-days-section">
                                <label class="form-label text-body fw-semibold mb-2">Working Days</label>
                                <p class="text-muted small mb-2 mb-md-3">All days are selected by default. Click a day to include or exclude it.</p>
                                <div class="d-flex flex-wrap align-items-center" id="working-days-container" role="group" aria-label="Working days">
                                    <button type="button" class="day-badge active" data-day="Mon" title="Monday" aria-pressed="true">M</button>
                                    <button type="button" class="day-badge active" data-day="Tue" title="Tuesday" aria-pressed="true">T</button>
                                    <button type="button" class="day-badge active" data-day="Wed" title="Wednesday" aria-pressed="true">W</button>
                                    <button type="button" class="day-badge active" data-day="Thu" title="Thursday" aria-pressed="true">T</button>
                                    <button type="button" class="day-badge active" data-day="Fri" title="Friday" aria-pressed="true">F</button>
                                    <button type="button" class="day-badge active" data-day="Sat" title="Saturday" aria-pressed="true">S</button>
                                    <button type="button" class="day-badge active" data-day="Sun" title="Sunday" aria-pressed="true">S</button>
                                </div>
                            </div>

                            <!-- Account status + availability -->
                            <div class="row mb-4" id="driver-status-section">
                                <div class="col-md-6">
                                    <label for="driver-status" class="form-label text-body fw-semibold" id="driver-status-label">Initial Status</label>
                                    <select class="form-select" id="driver-status" style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        @if ($type === 'zone')
                                        <option value="Pending">Pending Review</option>
                                        <option value="Active" selected>Active</option>
                                        <option value="Suspended">Suspended</option>
                                        @else
                                        <option value="Pending">Pending Review</option>
                                        <option value="Active" selected>Active</option>
                                        <option value="Rejected">Rejected</option>
                                        <option value="Suspended">Suspended</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-6" id="driver-availability-section">
                                    <label for="driver-availability" class="form-label text-body fw-semibold">Availability</label>
                                    <select class="form-select" id="driver-availability" style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="Online">Online</option>
                                        <option value="Transit">Transit</option>
                                        <option value="Offline" selected>Offline</option>
                                    </select>
                                </div>
                            </div>


                        </div>

                        @if ($type === 'zone')
                        <!-- TAB: Service Area (Only for Zone-wise drivers) -->
                        <div class="tab-pane fade" id="tab-service-area" role="tabpanel" aria-labelledby="service-area-tab">
                            <div class="row mb-3 mt-2">
                                <div class="col-md-12">
                                    <label class="form-label text-body fw-semibold mb-2">Select Operations Service Areas (Multi-select)</label>
                                    <p class="text-muted small mb-3">Note: The primary assigned zone is selected in Personal Info. Other selected zones will be saved as secondary service areas.</p>
                                    
                                    <div class="row g-3" id="service-areas-grid">
                                        @foreach ($zonesList as $zoneOption)
                                        @php
                                            $key = $zoneOption->code;
                                            $label = $zoneOption->name;
                                        @endphp
                                        <div class="col-sm-6">
                                            <div class="p-3 border rounded d-flex align-items-center justify-content-between service-area-item-card" id="area-card-{{ $key }}" style="background-color: #f8fafc; border-color: #e2e8f0; border-radius: 8px; transition: all 0.2s ease;">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input modal-service-area-checkbox" type="checkbox" id="modal-area-{{ $key }}" value="{{ $key }}" data-label="{{ $label }}" onchange="handleModalAreaCheckboxChange('{{ $key }}')">
                                                    <label class="form-check-label fw-bold text-body" for="modal-area-{{ $key }}">{{ $label }}</label>
                                                </div>
                                                
                                                <!-- Primary Radio / Secondary Badge container -->
                                                <div class="d-flex align-items-center gap-2">
                                                    <!-- Set Primary radio -->
                                                    <div class="form-check mb-0 p-0" id="primary-radio-wrapper-{{ $key }}" style="display: none;">
                                                        <input class="form-check-input modal-primary-area-radio ms-0" type="radio" name="modalPrimaryArea" id="primary-radio-{{ $key }}" value="{{ $key }}" data-label="{{ $label }}" onchange="handleModalPrimaryRadioChange('{{ $key }}')">
                                                        <label class="form-check-label text-primary-orange fw-bold cursor-pointer" for="primary-radio-{{ $key }}" style="font-size: 0.75rem;">Set Primary</label>
                                                    </div>
                                                    <!-- Primary indicator badge -->
                                                    <span class="badge bg-label-primary px-2 py-1 rounded primary-badge-indicator" id="label-primary-{{ $key }}" style="display: none; font-size: 0.72rem;">Primary</span>
                                                    <!-- Secondary indicator badge -->
                                                    <span class="badge bg-label-secondary px-2 py-1 rounded secondary-badge-indicator" id="label-secondary-{{ $key }}" style="display: none; font-size: 0.72rem; background-color: rgba(133, 146, 163, 0.1); color: #8592a3;">Secondary</span>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- TAB 2: Vehicle Details -->
                        <div class="tab-pane fade" id="tab-vehicle-details" role="tabpanel" aria-labelledby="vehicle-tab">
                            <!-- Row 1: Vehicle No & Brand -->
                            <div class="row mb-3 mt-2">
                                <div class="col-md-6">
                                    <label for="driver-plate-number" class="form-label text-body fw-semibold">Vehicle No <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="driver-plate-number" placeholder="e.g. MH-12-KL-4567" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-vehicle-brand" class="form-label text-body fw-semibold">Brand <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="driver-vehicle-brand" placeholder="e.g. Honda" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                            </div>
                            <!-- Row 2: Model & Type -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="driver-vehicle-model" class="form-label text-body fw-semibold">Model <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="driver-vehicle-model" placeholder="e.g. Activa 6G" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-vehicle-type" class="form-label text-body fw-semibold">Type</label>
                                    <select class="form-select" id="driver-vehicle-type" style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="scooter" selected>Scooter/Motorcycle</option>
                                        <option value="car">Car</option>
                                        <option value="auto_rickshaw">Auto Rickshaw</option>
                                        <option value="cargo_van">Cargo Van</option>
                                        <option value="mini_truck">Mini Truck</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Row 3: Fuel Classification & License Number -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="driver-vehicle-fuel" class="form-label text-body fw-semibold">Fuel Classification</label>
                                    <select class="form-select" id="driver-vehicle-fuel" style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="ev" selected>EV</option>
                                        <option value="petrol">Petrol</option>
                                        <option value="diesel">Diesel</option>
                                        <option value="cng">CNG</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-license-number" class="form-label text-body fw-semibold">License Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="driver-license-number" placeholder="e.g. DL-14202300991" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: Documents -->
                        <div class="tab-pane fade" id="tab-documents" role="tabpanel" aria-labelledby="docs-tab">
                            <div class="row mb-3 mt-2">
                                <div class="col-md-6">
                                    <label for="driver-aadhaar-front" class="form-label text-body fw-semibold">Aadhaar Front <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="driver-aadhaar-front" accept="image/*,.pdf" required style="border-radius: 8px; font-size: 0.88rem;">
                                    {{-- <small class="text-muted">Name, DOB &amp; Address</small> --}}
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-aadhaar-back" class="form-label text-body fw-semibold">Aadhaar Back <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="driver-aadhaar-back" accept="image/*,.pdf" required style="border-radius: 8px; font-size: 0.88rem;">
                                    {{-- <small class="text-muted">Aadhaar Number &amp; QR</small> --}}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="driver-dl-front" class="form-label text-body fw-semibold">Driving License Front <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="driver-dl-front" accept="image/*,.pdf" required style="border-radius: 8px; font-size: 0.88rem;">
                                    {{-- <small class="text-muted">License No. &amp; Photo</small> --}}
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-dl-back" class="form-label text-body fw-semibold">Driving License Back <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="driver-dl-back" accept="image/*,.pdf" required style="border-radius: 8px; font-size: 0.88rem;">
                                    {{-- <small class="text-muted">Vehicle Categories</small> --}}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="driver-pan-card" class="form-label text-body fw-semibold">PAN Card <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="driver-pan-card" accept="image/*,.pdf" required style="border-radius: 8px; font-size: 0.88rem;">
                                    {{-- <small class="text-muted">Income Tax Department</small> --}}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="driver-vehicle-rc" class="form-label text-body fw-semibold">Vehicle RC <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="driver-vehicle-rc" accept="image/*,.pdf" required style="border-radius: 8px; font-size: 0.88rem;">
                                    {{-- <small class="text-muted">Registration Certificate</small> --}}
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="driver-vehicle-insurance" class="form-label text-body fw-semibold">Vehicle Insurance <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="driver-vehicle-insurance" accept="image/*,.pdf" required style="border-radius: 8px; font-size: 0.88rem;">
                                    {{-- <small class="text-muted">Valid Insurance Policy</small> --}}
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="consentCheck" checked required>
                                    <label class="form-check-label text-muted" for="consentCheck" style="font-size: 0.82rem;">
                                        I hereby consent to document validation and background checks by Deliverease security fleet auditors.
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Extra properties for Deliveries and Rating (Hidden by default, edited inside code) -->
                        <input type="hidden" id="driver-deliveries" value="0">
                        <input type="hidden" id="driver-rating" value="—">

                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="modal-footer border-top-0 d-flex justify-content-between align-items-center px-4 pb-4 pt-2">
                    <div class="d-flex align-items-center gap-1 text-warning" style="font-size: 0.82rem; font-weight: 500;">
                        <i class="bx bx-error-circle" style="font-size: 1.15rem;"></i>
                        <span>Fields marked <span class="text-danger">*</span> are required</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Cancel</button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="modal-back-btn" style="border-radius: 8px; font-weight: 600;">
                            <i class="bx bx-chevron-left" style="font-size: 1.15rem;"></i> Back
                        </button>
                        <button type="button" class="btn btn-primary-orange d-flex align-items-center gap-2" id="modal-next-btn" style="border-radius: 8px; padding: 10px 20px;">
                            <span>Next</span>
                            <i class="bx bx-chevron-right" style="font-size: 1.15rem;"></i>
                        </button>
                        <button type="submit" class="btn btn-primary-orange d-none align-items-center gap-2" id="save-driver-btn" style="border-radius: 8px; padding: 10px 20px;">
                            <i class="bx bx-user-plus" id="footerIconSymbol" style="font-size: 1.2rem;"></i>
                            <span id="save-btn-text">Add Driver</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Logic for Driver Management -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    function getToastStack() {
        let stack = document.getElementById('app-toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'app-toast-stack';
            stack.className = 'app-toast-stack';
            document.body.appendChild(stack);
        }
        return stack;
    }

    window.showToast = function(title, message, type) {
        const toastType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'success';
        const icons = {
            success: 'bx-check',
            error: 'bx-x',
            warning: 'bx-error',
            info: 'bx-info-circle',
        };
        const text = message || title || '';

        const toast = document.createElement('div');
        toast.className = `app-toast app-toast--${toastType}`;
        toast.setAttribute('role', 'status');
        toast.innerHTML = `
            <span class="app-toast__icon"><i class="bx ${icons[toastType]}"></i></span>
            <span class="app-toast__msg"></span>
        `;
        toast.querySelector('.app-toast__msg').textContent = text;

        const stack = getToastStack();
        stack.appendChild(toast);

        let timer;
        const dismiss = () => {
            if (toast.classList.contains('is-leaving')) return;
            clearTimeout(timer);
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), 220);
        };
        const schedule = (ms) => {
            clearTimeout(timer);
            timer = setTimeout(dismiss, ms);
        };

        schedule(2600);
        toast.addEventListener('mouseenter', () => clearTimeout(timer));
        toast.addEventListener('mouseleave', () => schedule(1200));
        toast.addEventListener('click', dismiss);
    };

    /** Account status for Pending/Rejected/Suspended; otherwise operational availability. */
    function listStatus(driver) {
        const account = driver.status || 'Pending';
        if (account === 'Pending' || account === 'Suspended' || account === 'Rejected') {
            return account;
        }
        if (driver.availability === 'Online') return 'Online';
        if (driver.availability === 'Transit') return 'Transit';
        return 'Offline';
    }

    /** Frontend label for account/availability status badges. */
    function formatStatusLabel(status) {
        return status === 'Pending' ? 'Pending Review' : status;
    }

    function normalizeDriverRecord(driver) {
        if (!driver) return driver;
        if (!driver.availability) {
            if (driver.status === 'Offline') {
                driver.availability = 'Offline';
                driver.status = 'Active';
            } else if (driver.status === 'Active') {
                driver.availability = 'Online';
            } else {
                driver.availability = 'Offline';
            }
        }
        return driver;
    }

    function canToggleAvailability(driver) {
        return (driver.status || '') === 'Active';
    }

    function availabilityMeta(availability) {
        if (availability === 'Online') {
            return { stateClass: 'online', icon: 'bx-wifi', label: 'Online' };
        }
        if (availability === 'Transit') {
            return { stateClass: 'transit', icon: 'bx-trip', label: 'Transit' };
        }
        return { stateClass: 'offline', icon: 'bx-wifi-off', label: 'Offline' };
    }

    function availabilityOptionsHtml(driver, { includeHeader = false } = {}) {
        if (!canToggleAvailability(driver)) {
            return `
                <a class="dropdown-item disabled" href="javascript:void(0);" tabindex="-1" aria-disabled="true">
                    <i class="bx bx-lock-alt me-2"></i> Availability locked
                </a>
            `;
        }

        const current = driver.availability || 'Offline';
        const header = includeHeader
            ? `<h6 class="dropdown-header">Availability</h6>`
            : '';

        const items = ['Online', 'Transit', 'Offline'].map((value) => {
            const meta = availabilityMeta(value);
            const isCurrent = value === current;
            if (isCurrent) {
                return `
                    <a class="dropdown-item active disabled" href="javascript:void(0);" tabindex="-1" aria-disabled="true">
                        <i class="bx ${meta.icon} me-2"></i> ${value}
                        <i class="bx bx-check float-end mt-1"></i>
                    </a>
                `;
            }
            return `
                <a class="dropdown-item" href="javascript:void(0);" onclick="changeDriverStatus('${driver.id}', '${value}')">
                    <i class="bx ${meta.icon} me-2"></i> ${value}
                </a>
            `;
        }).join('');

        return `${header}${items}`;
    }

    function availabilityToggleButtonHtml(driver) {
        const current = driver.availability || 'Offline';
        const enabled = canToggleAvailability(driver);
        const meta = availabilityMeta(current);
        const title = enabled
            ? 'Change availability'
            : 'Only Active drivers can change availability';

        if (!enabled) {
            return `
                <button type="button"
                    class="btn btn-sm btn-icon btn-outline-secondary rounded-circle btn-availability-toggle ${meta.stateClass}"
                    title="${title}"
                    disabled
                    style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bx ${meta.icon}" style="font-size: 1.15rem;"></i>
                </button>
            `;
        }

        return `
            <div class="dropdown availability-dropdown">
                <button type="button"
                    class="btn btn-sm btn-icon btn-outline-secondary rounded-circle btn-availability-toggle ${meta.stateClass}"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="true"
                    aria-expanded="false"
                    title="${title}"
                    style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bx ${meta.icon}" style="font-size: 1.15rem;"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    ${availabilityOptionsHtml(driver, { includeHeader: true })}
                </div>
            </div>
        `;
    }

    function availabilityToggleMenuItemHtml(driver) {
        return availabilityOptionsHtml(driver, { includeHeader: true });
    }

    // Drivers loaded via AJAX from the list endpoint
    let allDrivers = [];
    const selectedDriverIds = new Set();
    
    // Dynamic page type passed from Laravel route ('store' or 'zone')
    const pageType = "{{ $type }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const zoneAreas = @json(($zonesList ?? collect())->map(fn ($zone) => ['id' => $zone->id, 'code' => $zone->code, 'name' => $zone->name])->values());
    const driverApiRoutes = pageType === 'store'
        ? {
            list: @json(route('fleet-drivers-store.list')),
            store: @json(route('fleet-drivers-store.store')),
            update: @json(url('/fleet/drivers/store/__CODE__/update')),
            destroy: @json(url('/fleet/drivers/store/__CODE__')),
            status: @json(url('/fleet/drivers/store/__CODE__/status')),
        }
        : {
            list: @json(route('fleet-drivers-zone.list')),
            store: @json(route('fleet-drivers-zone.store')),
            update: @json(url('/fleet/drivers/zone/__CODE__/update')),
            destroy: @json(url('/fleet/drivers/zone/__CODE__')),
            status: @json(url('/fleet/drivers/zone/__CODE__/status')),
        };

    const DOCUMENT_FIELD_IDS = [
        'driver-aadhaar-front',
        'driver-aadhaar-back',
        'driver-dl-front',
        'driver-dl-back',
        'driver-pan-card',
        'driver-vehicle-rc',
        'driver-vehicle-insurance',
    ];

    function setDocumentFieldsRequired(required) {
        DOCUMENT_FIELD_IDS.forEach((id) => {
            const input = document.getElementById(id);
            if (input) {
                if (required) {
                    input.setAttribute('required', 'true');
                } else {
                    input.removeAttribute('required');
                }
            }
        });
    }

    function routeForCode(template, code) {
        return template.replace('__CODE__', encodeURIComponent(code));
    }

    async function parseJsonResponse(response) {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = data.message
                || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Request failed.');
            throw new Error(message);
        }
        return data;
    }

    function buildDriverFormData() {
        const formData = new FormData();
        const appendValue = (key, value) => {
            if (value !== null && value !== undefined && value !== '') {
                formData.append(key, value);
            }
        };

        appendValue('driver-first-name', document.getElementById('driver-first-name').value.trim());
        appendValue('driver-last-name', document.getElementById('driver-last-name').value.trim());
        appendValue('driver-email', document.getElementById('driver-email').value.trim());
        appendValue('driver-phone', document.getElementById('driver-phone').value.trim());
        appendValue('driver-dob', document.getElementById('driver-dob').value);
        appendValue('driver-gender', document.getElementById('driver-gender').value);
        appendValue('driver-address', document.getElementById('driver-address').value.trim());
        appendValue('driver-shift', document.getElementById('driver-shift').value);
        appendValue('driver-status', document.getElementById('driver-status').value);
        appendValue('driver-availability', document.getElementById('driver-availability').value);
        appendValue('driver-plate-number', document.getElementById('driver-plate-number').value.trim());
        appendValue('driver-vehicle-brand', document.getElementById('driver-vehicle-brand').value.trim());
        appendValue('driver-vehicle-model', document.getElementById('driver-vehicle-model').value.trim());
        appendValue('driver-vehicle-type', document.getElementById('driver-vehicle-type').value);
        appendValue('driver-vehicle-fuel', document.getElementById('driver-vehicle-fuel').value);
        appendValue('driver-license-number', document.getElementById('driver-license-number').value.trim());

        if (pageType === 'store') {
            appendValue('driver-store', document.getElementById('driver-store').value);
        } else {
            appendValue('driver-zone', document.getElementById('driver-zone').value);
            getSelectedServiceAreaCodes().forEach((code) => formData.append('service_areas[]', code));

            let partnerType = 'independent';
            const radInd = document.getElementById('modalPartnerTypeInd');
            if (radInd && !radInd.checked) {
                partnerType = 'third-party';
                appendValue('agency_name', document.getElementById('modalDriverAgencyName')?.value?.trim() || '');
                appendValue('agency_id', document.getElementById('modalDriverAgencyId')?.value?.trim() || '');
            }
            appendValue('partner_type', partnerType);
        }

        getWorkingDaysSelection().forEach((day) => formData.append('working_days[]', day));

        const avatarFile = document.getElementById('driver-avatar-file')?.files?.[0];
        if (avatarFile) {
            formData.append('driver-avatar-file', avatarFile);
        }

        DOCUMENT_FIELD_IDS.forEach((id) => {
            const file = document.getElementById(id)?.files?.[0];
            if (file) {
                formData.append(id, file);
            }
        });

        return formData;
    }

    function getSelectedServiceAreaCodes() {
        const zoneSelect = document.getElementById('driver-zone');
        const primaryCode = zoneSelect?.selectedOptions?.[0]?.dataset?.code || '';
        const codes = [];

        getServiceAreaDefinitions().forEach((area) => {
            const checkbox = document.getElementById(`modal-area-${area.key}`);
            if (checkbox?.checked && area.key !== primaryCode) {
                codes.push(area.key);
            }
        });

        return codes;
    }

    function getServiceAreaDefinitions() {
        return zoneAreas.map((zone) => ({ key: zone.code, val: zone.name }));
    }

    function resolveAvatarUrl(avatar) {
        if (!avatar) {
            return '/assets/img/avatars/1.png';
        }
        if (
            avatar.startsWith('data:image')
            || avatar.startsWith('http://')
            || avatar.startsWith('https://')
            || avatar.startsWith('/')
        ) {
            return avatar;
        }
        if (avatar.includes('/')) {
            return `/storage/${avatar.replace(/^storage\//, '')}`;
        }
        return `/assets/img/avatars/${avatar}`;
    }

    function applyStatsDelta(listStatusValue, direction) {
        const delta = direction === 'add' ? 1 : -1;
        statsState.total += delta;
        if (listStatusValue === 'Online') statsState.online += delta;
        else if (listStatusValue === 'Offline') statsState.offline += delta;
        else if (listStatusValue === 'Transit') statsState.transit += delta;
        else if (listStatusValue === 'Pending') statsState.pending += delta;
        else if (listStatusValue === 'Suspended') statsState.suspended += delta;
    }

    async function saveDriverToServer(actionType) {
        const saveBtn = document.getElementById('save-driver-btn');
        saveBtn.disabled = true;

        try {
            const formData = buildDriverFormData();
            const isEdit = actionType === 'edit';
            const code = document.getElementById('edit-driver-id-hidden').value;
            const url = isEdit ? routeForCode(driverApiRoutes.update, code) : driverApiRoutes.store;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await parseJsonResponse(response);
            const driver = data.driver;
            const shaped = normalizeDriverRecord(driver);
            const belongsOnFleetList = pageType !== 'zone'
                || ['Active', 'Suspended'].includes(shaped.status);

            if (isEdit) {
                const index = allDrivers.findIndex((d) => d.id === code);
                if (index > -1) {
                    const oldStatus = listStatus(allDrivers[index]);
                    if (belongsOnFleetList) {
                        if (oldStatus !== listStatus(shaped)) {
                            applyStatsDelta(oldStatus, 'remove');
                            applyStatsDelta(listStatus(shaped), 'add');
                        }
                        allDrivers[index] = shaped;
                    } else {
                        applyStatsDelta(oldStatus, 'remove');
                        allDrivers.splice(index, 1);
                    }
                } else if (belongsOnFleetList) {
                    allDrivers.unshift(shaped);
                    applyStatsDelta(listStatus(shaped), 'add');
                }
                showToast('Success', data.message || 'Driver profile updated successfully!', 'success');
            } else if (belongsOnFleetList) {
                allDrivers.unshift(shaped);
                applyStatsDelta(listStatus(shaped), 'add');
                showToast('Success', data.message || 'Driver profile created successfully!', 'success');
            } else {
                showToast('Success', data.message || 'Application submitted for approval.', 'success');
            }

            driverModal.hide();
            updateStatsUI();
            renderDrivers();
        } catch (error) {
            showToast('Error', error.message || 'Unable to save driver.', 'error');
        } finally {
            saveBtn.disabled = false;
        }
    }

    // Stats tracking — rebuilt after AJAX load
    let statsState = {
        online: 0,
        offline: 0,
        transit: 0,
        pending: 0,
        suspended: 0,
        total: 0
    };

    function rebuildStatsFromDrivers() {
        const filteredList = allDrivers.filter(d => d.type === pageType);
        statsState = {
            online: filteredList.filter(d => listStatus(d) === 'Online').length,
            offline: filteredList.filter(d => listStatus(d) === 'Offline').length,
            transit: filteredList.filter(d => listStatus(d) === 'Transit').length,
            pending: filteredList.filter(d => listStatus(d) === 'Pending').length,
            suspended: filteredList.filter(d => listStatus(d) === 'Suspended').length,
            total: filteredList.length
        };
    }

    function showDriversLoading() {
        const tbody = document.getElementById('drivers-tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>
                        Loading drivers...
                    </td>
                </tr>
            `;
        }
        const grid = document.getElementById('grid-view-container');
        if (grid && !grid.classList.contains('d-none')) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>
                    Loading drivers...
                </div>
            `;
        }
    }

    async function loadDrivers() {
        showDriversLoading();
        try {
            const response = await fetch(driverApiRoutes.list, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await parseJsonResponse(response);
            allDrivers = (data.drivers || []).map(normalizeDriverRecord);
            rebuildStatsFromDrivers();
            updateStatsUI();
            renderDrivers();
        } catch (error) {
            allDrivers = [];
            rebuildStatsFromDrivers();
            updateStatsUI();
            const tbody = document.getElementById('drivers-tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-5 text-danger">
                            <i class="bx bx-error-circle fs-3 mb-2 d-block"></i>
                            ${escapeHtml(error.message || 'Unable to load drivers.')}
                        </td>
                    </tr>
                `;
            }
            showToast('Error', error.message || 'Unable to load drivers.', 'error');
        }
    }

    let currentView = 'list'; // 'list' or 'grid'

    // Format helper functions
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
    }

    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const normalized = String(dateStr).slice(0, 10);
        const date = new Date(normalized + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return '';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    // Render Stats values
    function updateStatsUI() {
        document.getElementById('card-active-val').textContent = statsState.online;
        document.getElementById('card-offline-val').textContent = statsState.offline;
        document.getElementById('card-transit-val').textContent = statsState.transit;
        document.getElementById('card-suspended-val').textContent = statsState.suspended;
        
        document.getElementById('showing-total').textContent = statsState.total;
        document.getElementById('header-total-badge').textContent = statsState.total + ' Total';
    }

    // Main render function supporting both views
    function renderDrivers() {
        const query = document.getElementById('search-driver').value.toLowerCase().trim();
        const activePill = document.querySelector('.filter-pill.active');
        const statusFilter = activePill ? activePill.getAttribute('data-status') : 'all';
        const sortVal = document.querySelector('.dropdown-item.active')?.getAttribute('data-sort') || 'newest';
        
        // Filter by pageType and keyword search
        let filtered = allDrivers.filter(d => {
            if (d.type !== pageType) return false;
            
            const locText = pageType === 'store' ? (d.store || '') : (d.zone || '');
            const matchesSearch = d.name.toLowerCase().includes(query) || 
                                  d.id.toLowerCase().includes(query) || 
                                  d.phone.toLowerCase().includes(query) ||
                                  locText.toLowerCase().includes(query);
                                  
            const matchesStatus = (statusFilter === 'all') || (listStatus(d).toLowerCase() === statusFilter);
            
            return matchesSearch && matchesStatus;
        });
        
        // Sort
        filtered.sort((a, b) => {
            if (sortVal === 'newest') {
                return new Date(b.timestamp) - new Date(a.timestamp);
            } else if (sortVal === 'oldest') {
                return new Date(a.timestamp) - new Date(b.timestamp);
            } else if (sortVal === 'name-asc') {
                return a.name.localeCompare(b.name);
            } else if (sortVal === 'name-desc') {
                return b.name.localeCompare(a.name);
            } else if (sortVal === 'deliveries-desc') {
                return b.deliveries - a.deliveries;
            } else if (sortVal === 'rating-desc') {
                const rA = a.rating === '—' ? 0 : parseFloat(a.rating);
                const rB = b.rating === '—' ? 0 : parseFloat(b.rating);
                return rB - rA;
            }
            return 0;
        });

        // Set showing range text
        if (filtered.length === 0) {
            document.getElementById('showing-range').textContent = '0';
        } else {
            document.getElementById('showing-range').textContent = `1-${filtered.length}`;
        }

        // Render List View
        const tbody = document.getElementById('drivers-tbody');
        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bx bx-info-circle fs-3 mb-2 d-block"></i>
                        No drivers found matching criteria
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = filtered.map(d => {
                const ratingHtml = d.rating === '—' ? 
                    `<span class="text-muted d-flex align-items-center gap-1" style="font-size: 0.88rem; font-weight: 500;">
                        <i class="bx bx-star text-muted"></i> —
                    </span>` :
                    `<span class="text-warning d-flex align-items-center gap-1" style="font-size: 0.88rem; font-weight: 600;">
                        <i class="bx bxs-star" style="color: #ffab00;"></i> ${d.rating}
                    </span>`;
                    
                const shownStatus = listStatus(d);
                const statusClass = shownStatus.toLowerCase();
                const statusLabel = formatStatusLabel(shownStatus);
                const avatarSrc = resolveAvatarUrl(d.avatar);
                const locationText = pageType === 'store' ? escapeHtml(d.store) : escapeHtml(d.zone);
                
                return `
                    <tr class="driver-row border-bottom" id="row-${d.id}">
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <div class="form-check m-0">
                                <input class="form-check-input row-checkbox" type="checkbox" value="${d.id}" ${selectedDriverIds.has(d.id) ? 'checked' : ''}>
                            </div>
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-md me-3" style="width: 38px; height: 38px;">
                                    <img src="${avatarSrc}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width: 38px; height: 38px;">
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-body" style="font-size: 0.92rem; color: #32475c;">${escapeHtml(d.name)}</h6>
                                    <small class="text-muted d-block mt-0.5" style="font-size: 0.8rem;">${escapeHtml(d.phone)}</small>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <span class="text-body fw-semibold" style="font-size: 0.88rem; font-family: monospace;">${d.id}</span>
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <span class="status-badge-custom ${statusClass}">
                                <span class="dot"></span>
                                <span>${statusLabel}</span>
                            </span>
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <span class="text-body fw-semibold" style="font-size: 0.88rem;">${locationText}</span>
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            ${ratingHtml}
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <span class="text-body fw-bold" style="font-size: 0.88rem;">${formatNumber(d.deliveries)}</span>
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <span class="text-muted" style="font-size: 0.85rem;">${formatDate(d.timestamp)}</span>
                        </td>
                        <td style="padding: 14px 20px; vertical-align: middle; text-align: right; padding-right: 24px;">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                ${availabilityToggleButtonHtml(d)}
                                <a href="/fleet/drivers/${d.id}/profile?type=${pageType}" class="btn btn-sm btn-icon btn-outline-primary rounded-circle" title="View Driver Profile" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="bx bx-user" style="font-size: 1.15rem;"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-circle" onclick="openEditModal('${d.id}')" title="Edit Profile" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="bx bx-edit-alt" style="font-size: 1.15rem;"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" onclick="deleteDriver('${d.id}')" title="Delete Profile" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="bx bx-trash" style="font-size: 1.15rem;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Render Grid View
        const gridContainer = document.getElementById('grid-view-container');
        if (filtered.length === 0) {
            gridContainer.innerHTML = `
                <div class="col-12 text-center py-5 text-muted card shadow-none border m-3">
                    <i class="bx bx-info-circle fs-3 mb-2 d-block"></i>
                    No drivers found matching criteria
                </div>
            `;
        } else {
            gridContainer.innerHTML = filtered.map(d => {
                const ratingHtml = d.rating === '—' ? 
                    `<span class="text-muted"><i class="bx bx-star me-1"></i>—</span>` :
                    `<span class="text-warning fw-bold"><i class="bx bxs-star me-1" style="color: #ffab00;"></i>${d.rating}</span>`;
                    
                const shownStatus = listStatus(d);
                const statusClass = shownStatus.toLowerCase();
                const statusLabel = formatStatusLabel(shownStatus);
                const avatarSrc = resolveAvatarUrl(d.avatar);
                const locationLabel = pageType === 'store' ? 'Store:' : 'Zone:';
                const locationText = pageType === 'store' ? escapeHtml(d.store) : escapeHtml(d.zone);
                
                return `
                    <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                        <div class="card shadow-none border h-100 driver-grid-card" style="border-radius: 12px; background-color: #ffffff;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <!-- Card Header -->
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="form-check m-0">
                                        <input class="form-check-input row-checkbox" type="checkbox" value="${d.id}" ${selectedDriverIds.has(d.id) ? 'checked' : ''}>
                                        <span class="ms-1 text-muted fw-semibold" style="font-size: 0.8rem; font-family: monospace;">${d.id}</span>
                                    </div>
                                    <span class="status-badge-custom ${statusClass}">
                                        <span class="dot"></span>
                                        <span>${statusLabel}</span>
                                    </span>
                                </div>
                                
                                <!-- Card Info -->
                                <div class="text-center mb-3">
                                    <div class="avatar avatar-xl mx-auto mb-2" style="width: 54px; height: 54px;">
                                        <img src="${avatarSrc}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width: 54px; height: 54px;">
                                    </div>
                                    <h6 class="mb-0 fw-bold text-body" style="font-size: 0.95rem;">${escapeHtml(d.name)}</h6>
                                    <small class="text-muted">${escapeHtml(d.phone)}</small>
                                </div>
                                
                                <!-- Zone/Store & Stats -->
                                <div class="bg-light p-2 rounded mb-3" style="font-size: 0.82rem; border-radius: 8px;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">${locationLabel}</span>
                                        <span class="fw-semibold text-body">${locationText}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Deliveries:</span>
                                        <span class="fw-bold text-body">${formatNumber(d.deliveries)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Rating:</span>
                                        <span>${ratingHtml}</span>
                                    </div>
                                </div>
                                
                                <!-- Joined & Action buttons -->
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <span class="text-muted" style="font-size: 0.75rem;">Joined ${formatDate(d.timestamp)}</span>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-circle" data-bs-toggle="dropdown" aria-expanded="false" style="width: 28px; height: 28px; padding: 0; background: transparent; border: 1px solid #e0e2e7;">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            ${availabilityToggleMenuItemHtml(d)}
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="/fleet/drivers/${d.id}/profile?type=${pageType}"><i class="bx bx-user me-2"></i> View Driver Profile</a>
                                            <a class="dropdown-item" href="javascript:void(0);" onclick="openEditModal('${d.id}')"><i class="bx bx-edit-alt me-2"></i> Edit Profile</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteDriver('${d.id}')"><i class="bx bx-trash me-2"></i> Delete Profile</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Sync select-all + bulk bar with preserved selection
        syncSelectionUI();
    }

    // Toggle list vs grid layout view
    window.toggleView = function(view) {
        currentView = view;
        const btnList = document.getElementById('btn-view-list');
        const btnGrid = document.getElementById('btn-view-grid');
        const listContainer = document.getElementById('list-view-container');
        const gridContainer = document.getElementById('grid-view-container');
        
        if (view === 'list') {
            btnList.style.color = '#ff7a00';
            btnList.style.backgroundColor = 'rgba(255, 122, 0, 0.1)';
            btnGrid.style.color = '#566a7f';
            btnGrid.style.backgroundColor = 'transparent';
            
            listContainer.classList.remove('d-none');
            gridContainer.classList.add('d-none');
        } else {
            btnGrid.style.color = '#ff7a00';
            btnGrid.style.backgroundColor = 'rgba(255, 122, 0, 0.1)';
            btnList.style.color = '#566a7f';
            btnList.style.backgroundColor = 'transparent';
            
            gridContainer.classList.remove('d-none');
            listContainer.classList.add('d-none');
        }
        renderDrivers();
    };

    // Sort function handler
    window.handleSort = function(sortType, sortLabelText) {
        const items = document.querySelectorAll('.dropdown-item');
        items.forEach(item => {
            if (item.getAttribute('data-sort') === sortType) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        document.getElementById('current-sort-label').textContent = sortLabelText;
        renderDrivers();
    };

    // Search and status pills filters listeners
    document.getElementById('search-driver').addEventListener('input', renderDrivers);

    const filterPills = document.querySelectorAll('.filter-pill');
    filterPills.forEach(pill => {
        pill.addEventListener('click', function () {
            filterPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            renderDrivers();
        });
    });

    // Select all checkboxes toggle
    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            if (this.checked) {
                selectedDriverIds.add(cb.value);
            } else {
                selectedDriverIds.delete(cb.value);
            }
        });
        updateBulkActionsBar();
    });

    document.addEventListener('change', function (event) {
        const target = event.target;
        if (!target.classList || !target.classList.contains('row-checkbox')) {
            return;
        }
        if (target.checked) {
            selectedDriverIds.add(target.value);
        } else {
            selectedDriverIds.delete(target.value);
        }
        syncSelectionUI();
    });

    // Avatar file input listener for instant circular image preview
    document.getElementById('driver-avatar-file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                const previewImg = document.getElementById('avatarPreviewImg');
                const placeholderIcon = document.getElementById('avatarPlaceholderIcon');
                
                previewImg.src = evt.target.result;
                previewImg.style.display = 'block';
                placeholderIcon.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // Service area helpers for zone drivers (codes/names come from DB zones)
    function getAreaKeyFromLabel(label) {
        return zoneAreas.find((zone) => zone.name === label)?.code || '';
    }

    function getCurrentlyCheckedSecondaryKeys() {
        return getSelectedServiceAreaCodes();
    }

    function updateServiceAreaCheckboxes(selectedZone, checkedSecondaryKeys = []) {
        const areas = getServiceAreaDefinitions();
        const selectedZoneName = typeof selectedZone === 'string' && !/^\d+$/.test(selectedZone)
            ? selectedZone
            : zoneAreas.find((zone) => String(zone.id) === String(selectedZone))?.name || '';

        areas.forEach((area) => {
            const chk = document.getElementById(`modal-area-${area.key}`);
            const radio = document.getElementById(`primary-radio-${area.key}`);

            if (chk) {
                if (area.val === selectedZoneName) {
                    chk.checked = true;
                    if (radio) radio.checked = true;
                } else {
                    chk.checked = checkedSecondaryKeys.includes(area.key);
                    if (radio) radio.checked = false;
                }
            }
        });
        
        // Re-sync all visual statuses
        areas.forEach(area => {
            const chk = document.getElementById(`modal-area-${area.key}`);
            const card = document.getElementById(`area-card-${area.key}`);
            const wrapper = document.getElementById(`primary-radio-wrapper-${area.key}`);
            const secBadge = document.getElementById(`label-secondary-${area.key}`);
            const primBadge = document.getElementById(`label-primary-${area.key}`);
            const radio = document.getElementById(`primary-radio-${area.key}`);
            
            if (chk && chk.checked) {
                if (card) {
                    card.style.borderColor = '#ff7a00';
                    card.style.backgroundColor = 'rgba(255, 122, 0, 0.01)';
                }
                if (radio && radio.checked) {
                    if (wrapper) wrapper.style.display = 'none';
                    if (primBadge) primBadge.style.display = 'inline-block';
                    if (secBadge) secBadge.style.display = 'none';
                } else {
                    if (wrapper) wrapper.style.display = 'block';
                    if (primBadge) primBadge.style.display = 'none';
                    if (secBadge) secBadge.style.display = 'inline-block';
                }
            } else {
                if (card) {
                    card.style.borderColor = '#e2e8f0';
                    card.style.backgroundColor = '#f8fafc';
                }
                if (wrapper) wrapper.style.display = 'none';
                if (primBadge) primBadge.style.display = 'none';
                if (secBadge) secBadge.style.display = 'none';
            }
        });
    }

    window.handleModalAreaCheckboxChange = function(key) {
        const chk = document.getElementById(`modal-area-${key}`);
        const radio = document.getElementById(`primary-radio-${key}`);
        
        if (chk.checked) {
            // Check if any area is currently marked primary
            const checkedRadios = Array.from(document.querySelectorAll('.modal-primary-area-radio')).filter(r => r.checked);
            if (checkedRadios.length === 0) {
                radio.checked = true;
                handleModalPrimaryRadioChange(key);
            } else {
                // Keep current primary, show this checkbox as secondary
                updateServiceAreaCheckboxes(document.getElementById('driver-zone').value, getCurrentlyCheckedSecondaryKeys());
            }
        } else {
            const wasPrimary = radio.checked;
            radio.checked = false;
            
            if (wasPrimary) {
                // Find next checked checkbox and make it primary
                const checkedCheckboxes = Array.from(document.querySelectorAll('.modal-service-area-checkbox:checked'));
                if (checkedCheckboxes.length > 0) {
                    const nextKey = checkedCheckboxes[0].value;
                    const nextRadio = document.getElementById(`primary-radio-${nextKey}`);
                    if (nextRadio) {
                        nextRadio.checked = true;
                        handleModalPrimaryRadioChange(nextKey);
                    }
                } else {
                    // No zones left checked, clear the zone select and sync
                    const zoneSelect = document.getElementById('driver-zone');
                    if (zoneSelect) {
                        zoneSelect.value = '';
                    }
                    updateServiceAreaCheckboxes('', []);
                }
            } else {
                updateServiceAreaCheckboxes(document.getElementById('driver-zone').value, getCurrentlyCheckedSecondaryKeys());
            }
        }
    };

    window.handleModalPrimaryRadioChange = function(key) {
        const chk = document.getElementById(`modal-area-${key}`);
        const label = chk ? chk.getAttribute('data-label') : '';
        const zoneSelect = document.getElementById('driver-zone');

        if (zoneSelect) {
            const matchedOption = [...zoneSelect.options].find((option) => {
                return option.dataset.code === key || option.text === label;
            });

            if (matchedOption) {
                zoneSelect.value = matchedOption.value;
            }
        }

        updateServiceAreaCheckboxes(zoneSelect ? zoneSelect.value : label, getCurrentlyCheckedSecondaryKeys());
    };

    const zoneSelect = document.getElementById('driver-zone');
    if (zoneSelect) {
        zoneSelect.addEventListener('change', function () {
            const val = this.value;
            const currentSecondary = getCurrentlyCheckedSecondaryKeys();
            updateServiceAreaCheckboxes(val, currentSecondary);
        });
    }

    // Dynamic initial page layout adjustment
    if (pageType === 'store') {
        document.getElementById('store-select-container').style.display = 'block';
        document.getElementById('driver-store').setAttribute('required', 'true');
        document.getElementById('zone-select-container').style.display = 'none';
        document.getElementById('driver-zone').removeAttribute('required');
        
        document.getElementById('page-main-title').textContent = 'Store Drivers';
        document.getElementById('table-header-location').textContent = 'Assigned Store';
    } else {
        document.getElementById('zone-select-container').style.display = 'block';
        document.getElementById('driver-zone').setAttribute('required', 'true');
        document.getElementById('store-select-container').style.display = 'none';
        document.getElementById('driver-store').removeAttribute('required');
        
        document.getElementById('page-main-title').textContent = 'Zone-wise Drivers';
        document.getElementById('table-header-location').textContent = 'Assigned Zone';
    }
    
    // Partner Type selection modal handling
    // Partner Type selection modal handling is obsolete since all drivers are independent
    window.selectModalPartnerType = function(type) {};

    // Modal forms opening and action handling
    const driverModalEl = document.getElementById('driverModal');
    const driverModal = new bootstrap.Modal(driverModalEl);
    const driverForm = document.getElementById('driverForm');
    const modalBackBtn = document.getElementById('modal-back-btn');
    const modalNextBtn = document.getElementById('modal-next-btn');
    const saveDriverBtn = document.getElementById('save-driver-btn');

    function getModalTabButtons() {
        return Array.from(document.querySelectorAll('#modalTabsList .nav-link'));
    }

    function getActiveModalTabIndex() {
        return getModalTabButtons().findIndex((btn) => btn.classList.contains('active'));
    }

    function updateModalWizardButtons() {
        const tabs = getModalTabButtons();
        const index = Math.max(0, getActiveModalTabIndex());
        const isFirst = index <= 0;
        const isLast = index >= tabs.length - 1;
        const isAdd = document.getElementById('driver-action-type').value === 'add';

        modalBackBtn.classList.toggle('d-none', isFirst);
        modalNextBtn.classList.toggle('d-none', isLast);
        saveDriverBtn.classList.toggle('d-none', !isLast);
        saveDriverBtn.classList.toggle('d-flex', isLast);

        if (isLast) {
            document.getElementById('save-btn-text').textContent = isAdd ? 'Add Driver' : 'Save Changes';
            document.getElementById('footerIconSymbol').className = isAdd ? 'bx bx-user-plus' : 'bx bx-check';
        }
    }

    function validateActiveModalTab() {
        const activePane = document.querySelector('#driverModal .tab-pane.active');
        if (!activePane) {
            return true;
        }

        const fields = activePane.querySelectorAll('input, select, textarea');
        for (const field of fields) {
            if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }

        return true;
    }

    function goToModalTab(index) {
        const tabs = getModalTabButtons();
        if (index < 0 || index >= tabs.length) {
            return;
        }
        new bootstrap.Tab(tabs[index]).show();
    }

    modalNextBtn.addEventListener('click', function () {
        if (!validateActiveModalTab()) {
            return;
        }
        goToModalTab(getActiveModalTabIndex() + 1);
    });

    modalBackBtn.addEventListener('click', function () {
        goToModalTab(getActiveModalTabIndex() - 1);
    });

    getModalTabButtons().forEach((btn) => {
        btn.addEventListener('shown.bs.tab', updateModalWizardButtons);
    });

    const ALL_WORKING_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    function setWorkingDaysSelection(selectedDays) {
        const selected = Array.isArray(selectedDays) && selectedDays.length
            ? selectedDays
            : ALL_WORKING_DAYS.slice();
        const selectedSet = new Set(selected);

        document.querySelectorAll('#working-days-container .day-badge').forEach((btn) => {
            const day = btn.getAttribute('data-day');
            const isActive = selectedSet.has(day);
            btn.classList.toggle('active', isActive);
            btn.classList.toggle('inactive', !isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function getWorkingDaysSelection() {
        return Array.from(document.querySelectorAll('#working-days-container .day-badge.active'))
            .map((btn) => btn.getAttribute('data-day'))
            .filter(Boolean);
    }

    function normalizeWorkingDays(rawDays) {
        if (!Array.isArray(rawDays) || !rawDays.length) {
            return ALL_WORKING_DAYS.slice();
        }

        // Prefer explicit Mon/Tue keys when present
        const knownKeys = rawDays.filter((d) => ALL_WORKING_DAYS.includes(d));
        if (knownKeys.length) {
            return ALL_WORKING_DAYS.filter((d) => knownKeys.includes(d));
        }

        // Legacy approval-style letter arrays: first N weekdays active
        if (rawDays.every((d) => typeof d === 'string' && d.length === 1)) {
            return ALL_WORKING_DAYS.slice(0, Math.min(rawDays.length, ALL_WORKING_DAYS.length));
        }

        return ALL_WORKING_DAYS.slice();
    }

    const workingDaysContainer = document.getElementById('working-days-container');
    if (workingDaysContainer) {
        workingDaysContainer.addEventListener('click', function(e) {
            const btn = e.target.closest('.day-badge');
            if (!btn || !workingDaysContainer.contains(btn)) return;

            const willActivate = !btn.classList.contains('active');
            btn.classList.toggle('active', willActivate);
            btn.classList.toggle('inactive', !willActivate);
            btn.setAttribute('aria-pressed', willActivate ? 'true' : 'false');
        });
    }
    
    window.openAddModal = function() {
        const isStore = (pageType === 'store');
        document.getElementById('modalTitle').textContent = isStore ? 'Add Store Driver' : 'Add Zone Driver';
        document.getElementById('modalSubtitle').textContent = isStore ? 'Fill in the details to register a new store driver' : 'Fill in the details to register a new zone driver';
        document.getElementById('save-btn-text').textContent = isStore ? 'Add Driver' : 'Add Driver';
        document.getElementById('driver-action-type').value = 'add';
        
        document.getElementById('headerIconSymbol').className = 'bx bx-user-plus';
        document.getElementById('footerIconSymbol').className = 'bx bx-user-plus';
        
        driverForm.reset();
        
        // Reset avatar preview back to placeholder icon
        document.getElementById('avatarPreviewImg').style.display = 'none';
        document.getElementById('avatarPreviewImg').src = '';
        document.getElementById('avatarPlaceholderIcon').style.display = 'block';

        // Select correct elements on load
        if (isStore) {
            document.getElementById('store-select-container').style.display = 'block';
            document.getElementById('driver-store').setAttribute('required', 'true');
            document.getElementById('zone-select-container').style.display = 'none';
            document.getElementById('driver-zone').removeAttribute('required');
        } else {
            document.getElementById('zone-select-container').style.display = 'block';
            document.getElementById('driver-zone').setAttribute('required', 'true');
            document.getElementById('store-select-container').style.display = 'none';
            document.getElementById('driver-store').removeAttribute('required');
        }

        // Reset tabs to select Personal Info initially
        const firstTabEl = document.querySelector('#modalTabsList button:first-child');
        const tab = new bootstrap.Tab(firstTabEl);
        tab.show();
        updateModalWizardButtons();

        // Default hidden fields setup
        document.getElementById('driver-deliveries').value = 0;
        document.getElementById('driver-rating').value = '—';

        const statusSection = document.getElementById('driver-status-section');
        const statusLabel = document.getElementById('driver-status-label');
        const statusSelect = document.getElementById('driver-status');
        const availabilitySection = document.getElementById('driver-availability-section');
        const availabilitySelect = document.getElementById('driver-availability');
        if (isStore) {
            // Store drivers are created as Active + Offline; both can be changed later via edit.
            statusSection.style.display = 'none';
            statusSelect.removeAttribute('required');
            statusSelect.value = 'Active';
            availabilitySelect.value = 'Offline';
        } else {
            statusSection.style.display = '';
            statusLabel.textContent = 'Initial Status';
            statusSelect.setAttribute('required', 'true');
            statusSelect.value = 'Pending';
            availabilitySelect.value = 'Offline';
        }
        if (availabilitySection) {
            availabilitySection.style.display = isStore ? 'none' : '';
        }
        setDocumentFieldsRequired(true);

        // Working days: all selected by default
        setWorkingDaysSelection(ALL_WORKING_DAYS);
        
        if (!isStore) {
            updateServiceAreaCheckboxes('', []);
            selectModalPartnerType('independent');
            const agencyName = document.getElementById('modalDriverAgencyName');
            if (agencyName) agencyName.value = '';
            const agencyId = document.getElementById('modalDriverAgencyId');
            if (agencyId) agencyId.value = '';
        }

        driverModal.show();
        updateModalWizardButtons();
    };

    window.openEditModal = function(id) {
        const driver = allDrivers.find(d => d.id === id);
        if (!driver) return;
        
        const isStore = (pageType === 'store');
        document.getElementById('modalTitle').textContent = isStore ? 'Edit Store Driver' : 'Edit Zone Driver';
        document.getElementById('modalSubtitle').textContent = isStore ? 'Modify registration details for this store driver' : 'Modify registration details for this zone driver';
        document.getElementById('save-btn-text').textContent = 'Save Changes';
        document.getElementById('driver-action-type').value = 'edit';
        document.getElementById('edit-driver-id-hidden').value = id;
        
        document.getElementById('headerIconSymbol').className = 'bx bx-edit';
        document.getElementById('footerIconSymbol').className = 'bx bx-check';

        // Pre-fill Personal Info
        const nameParts = driver.name.split(' ');
        document.getElementById('driver-first-name').value = nameParts[0] || '';
        document.getElementById('driver-last-name').value = nameParts.slice(1).join(' ') || '';
        
        // Format email and phone
        document.getElementById('driver-email').value = driver.email || '';
        
        let rawPhone = driver.phone || '';
        if (rawPhone.startsWith('+91')) {
            rawPhone = rawPhone.replace('+91', '').trim().replace(/\s/g, '');
        }
        document.getElementById('driver-phone').value = rawPhone;
        
        document.getElementById('driver-dob').value = driver.dob || '';
        document.getElementById('driver-gender').value = driver.gender || '';
        document.getElementById('driver-address').value = driver.address || '';
        document.getElementById('driver-status').value = driver.status || 'Active';
        document.getElementById('driver-availability').value = driver.availability || 'Offline';
        document.getElementById('driver-shift').value = driver.shift || 'Morning Shift (6:00 AM - 2:00 PM)';
        setWorkingDaysSelection(normalizeWorkingDays(driver.working_days));

        const statusSection = document.getElementById('driver-status-section');
        const statusLabel = document.getElementById('driver-status-label');
        const availabilitySection = document.getElementById('driver-availability-section');
        statusSection.style.display = '';
        statusLabel.textContent = 'Account Status';
        document.getElementById('driver-status').setAttribute('required', 'true');
        if (availabilitySection) {
            availabilitySection.style.display = '';
        }

        // Select correct elements on load
        if (isStore) {
            document.getElementById('store-select-container').style.display = 'block';
            document.getElementById('driver-store').setAttribute('required', 'true');
            const storeSelect = document.getElementById('driver-store');
            if (driver.store_id) {
                storeSelect.value = String(driver.store_id);
            } else if (driver.store) {
                [...storeSelect.options].forEach((option) => {
                    if (option.text === driver.store) {
                        storeSelect.value = option.value;
                    }
                });
            }
            document.getElementById('zone-select-container').style.display = 'none';
            document.getElementById('driver-zone').removeAttribute('required');
            setDocumentFieldsRequired(false);
        } else {
            document.getElementById('zone-select-container').style.display = 'block';
            document.getElementById('driver-zone').setAttribute('required', 'true');
            const zoneSelect = document.getElementById('driver-zone');
            if (driver.zone_id) {
                zoneSelect.value = String(driver.zone_id);
            } else if (driver.zone) {
                [...zoneSelect.options].forEach((option) => {
                    if (option.text === driver.zone) {
                        zoneSelect.value = option.value;
                    }
                });
            }
            document.getElementById('store-select-container').style.display = 'none';
            document.getElementById('driver-store').removeAttribute('required');

            const primaryKey = driver.zone_code || getAreaKeyFromLabel(driver.zone);
            const secondaryAreas = (driver.service_areas || []).filter((key) => key !== primaryKey);
            updateServiceAreaCheckboxes(driver.zone_id || driver.zone, secondaryAreas);
            
            const partnerType = driver.partner_type || 'independent';
            selectModalPartnerType(partnerType);
            
            const agencyNameInput = document.getElementById('modalDriverAgencyName');
            const agencyIdInput = document.getElementById('modalDriverAgencyId');
            if (agencyNameInput) agencyNameInput.value = driver.agency_name || '';
            if (agencyIdInput) agencyIdInput.value = driver.agency_id || '';
            setDocumentFieldsRequired(false);
        }

        // Pre-fill Vehicle Details
        document.getElementById('driver-plate-number').value = driver.plate_number || '';
        document.getElementById('driver-vehicle-brand').value = driver.vehicle_brand || '';
        document.getElementById('driver-vehicle-model').value = driver.vehicle_model || '';
        document.getElementById('driver-vehicle-type').value = driver.vehicle_type || 'scooter';
        document.getElementById('driver-vehicle-fuel').value = driver.vehicle_fuel || 'EV';
        document.getElementById('driver-license-number').value = driver.license_number || '';

        // Pre-fill Hidden metrics
        document.getElementById('driver-deliveries').value = driver.deliveries || 0;
        document.getElementById('driver-rating').value = driver.rating || '—';

        // Setup Avatar Image Preview
        const previewImg = document.getElementById('avatarPreviewImg');
        const placeholderIcon = document.getElementById('avatarPlaceholderIcon');
        
        if (driver.avatar) {
            previewImg.src = resolveAvatarUrl(driver.avatar);
            previewImg.style.display = 'block';
            placeholderIcon.style.display = 'none';
        } else {
            previewImg.style.display = 'none';
            previewImg.src = '';
            placeholderIcon.style.display = 'block';
        }

        // Reset tabs to select Personal Info initially
        const firstTabEl = document.querySelector('#modalTabsList button:first-child');
        const tab = new bootstrap.Tab(firstTabEl);
        tab.show();

        driverModal.show();
        updateModalWizardButtons();
    };

    // Save driver profile form handler
    driverForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Enter key / accidental submit on earlier tabs advances to Next instead.
        const tabs = getModalTabButtons();
        const activeIndex = getActiveModalTabIndex();
        if (activeIndex < tabs.length - 1) {
            if (validateActiveModalTab()) {
                goToModalTab(activeIndex + 1);
            }
            return;
        }

        const actionType = document.getElementById('driver-action-type').value;
        saveDriverToServer(actionType);
    });

    function getSelectedDriverIds() {
        return Array.from(selectedDriverIds);
    }

    function updateBulkActionsBar() {
        const bar = document.getElementById('bulk-actions-bar');
        const countEl = document.getElementById('bulk-selected-count');
        const count = selectedDriverIds.size;
        if (countEl) countEl.textContent = String(count);
        if (bar) bar.classList.toggle('is-visible', count > 0);
    }

    function syncSelectionUI() {
        const checkboxes = Array.from(document.querySelectorAll('.row-checkbox'));
        checkboxes.forEach((cb) => {
            cb.checked = selectedDriverIds.has(cb.value);
        });
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && checkboxes.every((cb) => cb.checked);
            selectAll.indeterminate = checkboxes.some((cb) => cb.checked) && !selectAll.checked;
        }
        updateBulkActionsBar();
    }

    window.clearDriverSelection = function() {
        selectedDriverIds.clear();
        syncSelectionUI();
    };

    async function runBulkRequests(ids, requestFactory) {
        const results = await Promise.allSettled(ids.map((id) => requestFactory(id)));
        const failed = results.filter((result) => result.status === 'rejected').length;
        const succeeded = results.length - failed;
        return { succeeded, failed, total: results.length };
    }

    async function refreshDriversAfterBulk() {
        await loadDrivers();
        // Keep only IDs that still exist after refresh
        const existing = new Set(allDrivers.map((d) => d.id));
        Array.from(selectedDriverIds).forEach((id) => {
            if (!existing.has(id)) selectedDriverIds.delete(id);
        });
        syncSelectionUI();
    }

    window.bulkSetAvailability = async function(availability) {
        const ids = getSelectedDriverIds();
        if (!ids.length) return;

        const confirm = await Swal.fire({
            title: 'Update Availability?',
            text: `Set ${ids.length} driver${ids.length > 1 ? 's' : ''} to ${availability}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, update',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary me-2 px-3 py-2',
                cancelButton: 'btn btn-outline-secondary px-3 py-2'
            },
            buttonsStyling: false
        });
        if (!confirm.isConfirmed) return;

        const { succeeded, failed } = await runBulkRequests(ids, async (id) => {
            const response = await fetch(routeForCode(driverApiRoutes.status, id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ availability }),
            });
            return parseJsonResponse(response);
        });

        await refreshDriversAfterBulk();
        if (failed) {
            showToast('Partial update', `${succeeded} updated, ${failed} failed.`, 'warning');
        } else {
            showToast('Updated', `${succeeded} driver${succeeded > 1 ? 's' : ''} set to ${availability}.`, 'success');
        }
        clearDriverSelection();
    };

    window.bulkSetAccountStatus = async function(status) {
        const ids = getSelectedDriverIds();
        if (!ids.length) return;

        const label = status === 'Pending' ? 'Pending Review' : status;
        const confirm = await Swal.fire({
            title: 'Update Account Status?',
            text: `Set ${ids.length} driver${ids.length > 1 ? 's' : ''} to ${label}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, update',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary me-2 px-3 py-2',
                cancelButton: 'btn btn-outline-secondary px-3 py-2'
            },
            buttonsStyling: false
        });
        if (!confirm.isConfirmed) return;

        const payload = status === 'Pending' || status === 'Rejected'
            ? { status, availability: 'Offline' }
            : status === 'Active'
                ? { status: 'Active' }
                : { status };

        const { succeeded, failed } = await runBulkRequests(ids, async (id) => {
            const response = await fetch(routeForCode(driverApiRoutes.status, id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            return parseJsonResponse(response);
        });

        await refreshDriversAfterBulk();
        if (failed) {
            showToast('Partial update', `${succeeded} updated, ${failed} failed.`, 'warning');
        } else {
            showToast('Updated', `${succeeded} driver${succeeded > 1 ? 's' : ''} set to ${label}.`, 'success');
        }
        clearDriverSelection();
    };

    window.bulkDeleteDrivers = async function() {
        const ids = getSelectedDriverIds();
        if (!ids.length) return;

        const confirm = await Swal.fire({
            title: 'Delete Selected Drivers?',
            text: `This will permanently delete ${ids.length} driver profile${ids.length > 1 ? 's' : ''}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger me-2 px-3 py-2',
                cancelButton: 'btn btn-outline-secondary px-3 py-2'
            },
            buttonsStyling: false
        });
        if (!confirm.isConfirmed) return;

        const { succeeded, failed } = await runBulkRequests(ids, async (id) => {
            const response = await fetch(routeForCode(driverApiRoutes.destroy, id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            return parseJsonResponse(response);
        });

        selectedDriverIds.clear();
        await refreshDriversAfterBulk();
        if (failed) {
            showToast('Partial delete', `${succeeded} deleted, ${failed} failed.`, 'warning');
        } else {
            showToast('Deleted', `${succeeded} driver${succeeded > 1 ? 's' : ''} deleted.`, 'warning');
        }
    };

    // Inline status change handler (list badge values: Online/Transit/Offline/Pending/Suspended)
    window.changeDriverStatus = function(id, newStatus) {
        const payload = (newStatus === 'Online' || newStatus === 'Offline' || newStatus === 'Transit')
            ? { availability: newStatus }
            : { status: newStatus };
        const successLabel = (newStatus === 'Online' || newStatus === 'Offline' || newStatus === 'Transit')
            ? `Driver set to ${newStatus}.`
            : `Driver status updated to ${newStatus}.`;

        fetch(routeForCode(driverApiRoutes.status, id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(parseJsonResponse)
            .then((data) => {
                const driverIndex = allDrivers.findIndex((d) => d.id === id);
                if (driverIndex > -1) {
                    const oldStatus = listStatus(allDrivers[driverIndex]);
                    const shaped = normalizeDriverRecord(data.driver);
                    const belongsOnFleetList = pageType !== 'zone'
                        || ['Active', 'Suspended'].includes(shaped.status);

                    applyStatsDelta(oldStatus, 'remove');
                    if (belongsOnFleetList) {
                        applyStatsDelta(listStatus(shaped), 'add');
                        allDrivers[driverIndex] = shaped;
                    } else {
                        allDrivers.splice(driverIndex, 1);
                    }
                }
                updateStatsUI();
                renderDrivers();
                showToast('Updated', data.message || successLabel, 'info');
            })
            .catch((error) => showToast('Error', error.message || 'Unable to update status.', 'error'));
    };

    // Inline delete profile handler using SweetAlert2
    window.deleteDriver = function(id) {
        const driver = allDrivers.find(d => d.id === id);
        if (!driver) return;

        Swal.fire({
            title: 'Delete Driver?',
            text: `Are you sure you want to delete the profile of ${driver.name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger me-2 px-3 py-2',
                cancelButton: 'btn btn-outline-secondary px-3 py-2'
            },
            buttonsStyling: false
        }).then(async (result) => {
            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await fetch(routeForCode(driverApiRoutes.destroy, id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                await parseJsonResponse(response);

                const driverIndex = allDrivers.findIndex((d) => d.id === id);
                if (driverIndex > -1) {
                    applyStatsDelta(listStatus(allDrivers[driverIndex]), 'remove');
                    allDrivers.splice(driverIndex, 1);
                }

                updateStatsUI();
                renderDrivers();
                showToast('Deleted', 'Driver profile has been successfully deleted.', 'warning');
            } catch (error) {
                showToast('Error', error.message || 'Unable to delete driver.', 'error');
            }
        });
    };

    // Initial load via AJAX
    updateStatsUI();
    loadDrivers();
});
</script>
@endsection
