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
    .status-badge-custom.active {
        background-color: rgba(40, 199, 111, 0.1) !important;
        color: #28c76f !important;
    }
    .status-badge-custom.active .dot {
        background-color: #28c76f;
    }
    .status-badge-custom.offline {
        background-color: rgba(133, 146, 163, 0.1) !important;
        color: #8592a3 !important;
    }
    .status-badge-custom.offline .dot {
        background-color: #8592a3;
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
    .dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
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
</style>

@php
$drivers = include resource_path('views/content/pages/drivers-data.php');
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
    <!-- Active Card -->
    <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
        <div class="card shadow-none border summary-card">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 48px; height: 48px; background-color: rgba(40, 199, 111, 0.1); color: #28c76f;">
                    <i class="bx bx-check-circle" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Active</small>
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
    
    <!-- Pending Card -->
    <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
        <div class="card shadow-none border summary-card">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 48px; height: 48px; background-color: rgba(255, 171, 0, 0.1); color: #ffab00;">
                    <i class="bx bx-time" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pending</small>
                    <h4 class="mb-0 fw-bold text-body mt-1" id="card-pending-val" style="font-size: 1.6rem; line-height: 1.2;">0</h4>
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
                    <button type="button" class="btn btn-pill filter-pill" data-status="active">Active</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="offline">Offline</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="pending">Pending</button>
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

<!-- List View Container (Table) -->
<div id="list-view-container">
    <div class="card shadow-none border" style="border-radius: 12px; background-color: #ffffff; overflow: hidden;">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0" id="drivers-table">
                <thead>
                    <tr class="table-light border-bottom" style="background-color: #fcfcfc;">
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
                                            <option value="Downtown Zone">Downtown Zone</option>
                                            <option value="Northwest District">Northwest District</option>
                                            <option value="Southeast Hub">Southeast Hub</option>
                                            <option value="Uptown Area">Uptown Area</option>
                                            <option value="East Side">East Side</option>
                                            <option value="West End">West End</option>
                                            <option value="Midtown">Midtown</option>
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
                                            <option value="Amanora Mall Store">Amanora Mall Store</option>
                                            <option value="Phoenix Marketcity Store">Phoenix Marketcity Store</option>
                                            <option value="MG Road Express">MG Road Express</option>
                                            <option value="Baner Delivery Hub">Baner Delivery Hub</option>
                                            <option value="Koregaon Park Store">Koregaon Park Store</option>
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

                            <!-- Initial Status -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="driver-status" class="form-label text-body fw-semibold">Initial Status</label>
                                    <select class="form-select" id="driver-status" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="Pending" selected>Pending Review</option>
                                        <option value="Active">Active</option>
                                        <option value="Offline">Offline</option>
                                        <option value="Suspended">Suspended</option>
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
                                    
                                    <div class="row g-3">
                                        @php
                                        $allAreas = [
                                            'downtown' => 'Downtown Zone',
                                            'northwest' => 'Northwest District',
                                            'southeast' => 'Southeast Hub',
                                            'uptown' => 'Uptown Area',
                                            'east' => 'East Side',
                                            'west' => 'West End',
                                            'midtown' => 'Midtown'
                                        ];
                                        @endphp
                                        @foreach ($allAreas as $key => $label)
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
                        <button type="submit" class="btn btn-primary-orange d-flex align-items-center gap-2" id="save-driver-btn" style="border-radius: 8px; padding: 10px 20px;">
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
    // SweetAlert2 Toast configuration
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    window.showToast = function(title, message, type) {
        let swalIcon = 'success';
        if (type === 'warning') swalIcon = 'warning';
        else if (type === 'info') swalIcon = 'info';
        else if (type === 'error') swalIcon = 'error';

        Toast.fire({
            icon: swalIcon,
            title: message || title
        });
    };

    // Initial mock drivers array injected from Blade PHP
    const allDrivers = @json($drivers);
    
    // Dynamic page type passed from Laravel route ('store' or 'zone')
    const pageType = "{{ $type }}"; 

    // Filter array to match current page type
    const initialFilteredList = allDrivers.filter(d => d.type === pageType);

    // Stats tracking matching filtered drivers
    let statsState = {
        active: initialFilteredList.filter(d => d.status === 'Active').length,
        offline: initialFilteredList.filter(d => d.status === 'Offline').length,
        pending: initialFilteredList.filter(d => d.status === 'Pending').length,
        suspended: initialFilteredList.filter(d => d.status === 'Suspended').length,
        total: initialFilteredList.length
    };

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
        const date = new Date(dateStr + 'T00:00:00');
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    // Render Stats values
    function updateStatsUI() {
        document.getElementById('card-active-val').textContent = statsState.active;
        document.getElementById('card-offline-val').textContent = statsState.offline;
        document.getElementById('card-pending-val').textContent = statsState.pending;
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
                                  
            const matchesStatus = (statusFilter === 'all') || (d.status.toLowerCase() === statusFilter);
            
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
                    
                const statusClass = d.status.toLowerCase();
                const avatarSrc = d.avatar.startsWith('data:image') ? d.avatar : `/assets/img/avatars/${d.avatar}`;
                const locationText = pageType === 'store' ? escapeHtml(d.store) : escapeHtml(d.zone);
                
                return `
                    <tr class="driver-row border-bottom" id="row-${d.id}">
                        <td style="padding: 14px 20px; vertical-align: middle;">
                            <div class="form-check m-0">
                                <input class="form-check-input row-checkbox" type="checkbox" value="${d.id}">
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
                                <span>${d.status}</span>
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
                    
                const statusClass = d.status.toLowerCase();
                const avatarSrc = d.avatar.startsWith('data:image') ? d.avatar : `/assets/img/avatars/${d.avatar}`;
                const locationLabel = pageType === 'store' ? 'Store:' : 'Zone:';
                const locationText = pageType === 'store' ? escapeHtml(d.store) : escapeHtml(d.zone);
                
                return `
                    <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                        <div class="card shadow-none border h-100 driver-grid-card" style="border-radius: 12px; background-color: #ffffff;">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <!-- Card Header -->
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="form-check m-0">
                                        <input class="form-check-input row-checkbox" type="checkbox" value="${d.id}">
                                        <span class="ms-1 text-muted fw-semibold" style="font-size: 0.8rem; font-family: monospace;">${d.id}</span>
                                    </div>
                                    <span class="status-badge-custom ${statusClass}">
                                        <span class="dot"></span>
                                        <span>${d.status}</span>
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

        // Reinforce select all checkbox state
        document.getElementById('selectAll').checked = false;
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
        });
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

    // Helper mappings and logic for multi-selectable Service Areas (Zone-wise drivers only)
    function getAreaKeyFromLabel(label) {
        const mapping = {
            'Downtown Zone': 'downtown',
            'Northwest District': 'northwest',
            'Southeast Hub': 'southeast',
            'Uptown Area': 'uptown',
            'East Side': 'east',
            'West End': 'west',
            'Midtown': 'midtown'
        };
        return mapping[label] || '';
    }

    function getCurrentlyCheckedSecondaryKeys() {
        const keys = [];
        const areas = ['downtown', 'northwest', 'southeast', 'uptown', 'east', 'west', 'midtown'];
        areas.forEach(k => {
            const chk = document.getElementById(`modal-area-${k}`);
            const radio = document.getElementById(`primary-radio-${k}`);
            if (chk && chk.checked && !(radio && radio.checked)) {
                keys.push(k);
            }
        });
        return keys;
    }

    function updateServiceAreaCheckboxes(selectedZone, checkedSecondaryKeys = []) {
        const areas = [
            { id: 'modal-area-downtown', val: 'Downtown Zone', key: 'downtown' },
            { id: 'modal-area-northwest', val: 'Northwest District', key: 'northwest' },
            { id: 'modal-area-southeast', val: 'Southeast Hub', key: 'southeast' },
            { id: 'modal-area-uptown', val: 'Uptown Area', key: 'uptown' },
            { id: 'modal-area-east', val: 'East Side', key: 'east' },
            { id: 'modal-area-west', val: 'West End', key: 'west' },
            { id: 'modal-area-midtown', val: 'Midtown', key: 'midtown' }
        ];
        
        areas.forEach(area => {
            const chk = document.getElementById(area.id);
            const radio = document.getElementById(`primary-radio-${area.key}`);
            
            if (chk) {
                if (area.val === selectedZone) {
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
            const chk = document.getElementById(area.id);
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
        if (zoneSelect && label) {
            zoneSelect.value = label;
        }
        
        updateServiceAreaCheckboxes(label, getCurrentlyCheckedSecondaryKeys());
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

        // Default hidden fields setup
        document.getElementById('driver-deliveries').value = 0;
        document.getElementById('driver-rating').value = '—';
        document.getElementById('driver-status').value = 'Pending';

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
        document.getElementById('driver-shift').value = driver.shift || 'Morning Shift (6:00 AM - 2:00 PM)';
        setWorkingDaysSelection(normalizeWorkingDays(driver.working_days));

        // Select correct elements on load
        if (isStore) {
            document.getElementById('store-select-container').style.display = 'block';
            document.getElementById('driver-store').setAttribute('required', 'true');
            document.getElementById('driver-store').value = driver.store || '';
            document.getElementById('zone-select-container').style.display = 'none';
            document.getElementById('driver-zone').removeAttribute('required');
        } else {
            document.getElementById('zone-select-container').style.display = 'block';
            document.getElementById('driver-zone').setAttribute('required', 'true');
            document.getElementById('driver-zone').value = driver.zone || '';
            document.getElementById('store-select-container').style.display = 'none';
            document.getElementById('driver-store').removeAttribute('required');
            
            const primaryKey = getAreaKeyFromLabel(driver.zone);
            const secondaryAreas = driver.service_areas ? driver.service_areas.filter(k => k !== primaryKey) : [];
            updateServiceAreaCheckboxes(driver.zone, secondaryAreas);
            
            const partnerType = driver.partner_type || 'independent';
            selectModalPartnerType(partnerType);
            
            const agencyNameInput = document.getElementById('modalDriverAgencyName');
            const agencyIdInput = document.getElementById('modalDriverAgencyId');
            if (agencyNameInput) agencyNameInput.value = driver.agency_name || '';
            if (agencyIdInput) agencyIdInput.value = driver.agency_id || '';
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
        
        if (driver.avatar && (driver.avatar.startsWith('data:image') || driver.avatar.match(/\.(png|jpg|jpeg|gif)$/i))) {
            const avatarSrc = driver.avatar.startsWith('data:image') ? driver.avatar : `/assets/img/avatars/${driver.avatar}`;
            previewImg.src = avatarSrc;
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
    };

    // Save driver profile form handler
    driverForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const actionType = document.getElementById('driver-action-type').value;
        const firstName = document.getElementById('driver-first-name').value.trim();
        const lastName = document.getElementById('driver-last-name').value.trim();
        const name = firstName + (lastName ? ' ' + lastName : '');
        
        const rawPhone = document.getElementById('driver-phone').value.trim();
        const phone = '+91 ' + rawPhone.substring(0, 5) + ' ' + rawPhone.substring(5);
        
        const email = document.getElementById('driver-email').value.trim();
        const dob = document.getElementById('driver-dob').value;
        const gender = document.getElementById('driver-gender').value;
        const address = document.getElementById('driver-address').value.trim();
        const status = document.getElementById('driver-status').value;
        const shift = document.getElementById('driver-shift').value;
        const workingDays = getWorkingDaysSelection();
        
        // Location fields
        const isStore = (pageType === 'store');
        const store = isStore ? document.getElementById('driver-store').value : 'None';
        const zone = isStore ? 'None' : document.getElementById('driver-zone').value;

        // Retrieve checked service areas
        let serviceAreas = [];
        if (!isStore) {
            const areas = ['downtown', 'northwest', 'southeast', 'uptown', 'east', 'west', 'midtown'];
            areas.forEach(k => {
                const chk = document.getElementById(`modal-area-${k}`);
                if (chk && chk.checked) {
                    serviceAreas.push(chk.value);
                }
            });
        }

        // Vehicle values
        const plateNumber = document.getElementById('driver-plate-number').value.trim();
        const vehicleBrand = document.getElementById('driver-vehicle-brand').value.trim();
        const vehicleModel = document.getElementById('driver-vehicle-model').value.trim();
        const vehicleType = document.getElementById('driver-vehicle-type').value;
        const vehicleFuel = document.getElementById('driver-vehicle-fuel').value;
        const licenseNumber = document.getElementById('driver-license-number').value.trim();
        
        const deliveries = parseInt(document.getElementById('driver-deliveries').value) || 0;
        const rating = document.getElementById('driver-rating').value.trim();

        // Retrieve photo preview source
        let avatar = '1.png';
        const previewImg = document.getElementById('avatarPreviewImg');
        if (previewImg.style.display === 'block' && previewImg.src) {
            avatar = previewImg.src;
        }

        // Retrieve Partner Type
        let partnerType = 'independent';
        let agencyName = '';
        let agencyId = '';
        if (!isStore) {
            const radInd = document.getElementById('modalPartnerTypeInd');
            if (radInd && !radInd.checked) {
                partnerType = 'third-party';
                const nameInput = document.getElementById('modalDriverAgencyName');
                const idInput = document.getElementById('modalDriverAgencyId');
                agencyName = nameInput ? nameInput.value.trim() : '';
                agencyId = idInput ? idInput.value.trim() : '';
            }
        }

        if (actionType === 'add') {
            const randomId = 'DRV-' + Math.floor(4400 + Math.random() * 600);
            const timestamp = new Date().toISOString().split('T')[0];
            
            // If image is default placeholder, give a random default avatar name
            if (!avatar || avatar.endsWith('drivers')) {
                const randomAvatarNum = Math.floor(1 + Math.random() * 8);
                avatar = randomAvatarNum + '.png';
            }

            const newDriver = {
                id: randomId,
                name: name,
                phone: phone,
                avatar: avatar,
                status: status,
                type: pageType,
                store: store,
                zone: zone,
                service_areas: serviceAreas,
                rating: rating,
                deliveries: deliveries,
                timestamp: timestamp,
                email: email,
                dob: dob,
                gender: gender,
                address: address,
                plate_number: plateNumber,
                vehicle_brand: vehicleBrand,
                vehicle_model: vehicleModel,
                vehicle_type: vehicleType,
                vehicle_fuel: vehicleFuel,
                license_required: 'Yes',
                license_number: licenseNumber,
                shift: shift,
                working_days: workingDays,
                partner_type: partnerType,
                agency_name: agencyName,
                agency_id: agencyId
            };
            
            allDrivers.unshift(newDriver);
            
            // Adjust stats values
            statsState.total++;
            if (status === 'Active') statsState.active++;
            else if (status === 'Offline') statsState.offline++;
            else if (status === 'Pending') statsState.pending++;
            else if (status === 'Suspended') statsState.suspended++;
            
            showToast('Success', 'Driver profile created successfully!', 'success');
            
        } else {
            const id = document.getElementById('edit-driver-id-hidden').value;
            const driverIndex = allDrivers.findIndex(d => d.id === id);
            if (driverIndex > -1) {
                const oldStatus = allDrivers[driverIndex].status;
                const oldAvatar = allDrivers[driverIndex].avatar;
                
                // Keep old avatar if no new selection happened
                if (avatar.startsWith('http://') || avatar.startsWith('https://')) {
                    if (avatar.includes('/assets/img/avatars/')) {
                        avatar = oldAvatar;
                    }
                }

                // Adjust stats values on status change
                if (oldStatus !== status) {
                    if (oldStatus === 'Active') statsState.active--;
                    else if (oldStatus === 'Offline') statsState.offline--;
                    else if (oldStatus === 'Pending') statsState.pending--;
                    else if (oldStatus === 'Suspended') statsState.suspended--;
                    
                    if (status === 'Active') statsState.active++;
                    else if (status === 'Offline') statsState.offline++;
                    else if (status === 'Pending') statsState.pending++;
                    else if (status === 'Suspended') statsState.suspended++;
                }
                
                allDrivers[driverIndex].name = name;
                allDrivers[driverIndex].phone = phone;
                allDrivers[driverIndex].avatar = avatar;
                allDrivers[driverIndex].status = status;
                allDrivers[driverIndex].store = store;
                allDrivers[driverIndex].zone = zone;
                allDrivers[driverIndex].service_areas = serviceAreas;
                allDrivers[driverIndex].deliveries = deliveries;
                allDrivers[driverIndex].rating = rating;
                allDrivers[driverIndex].email = email;
                allDrivers[driverIndex].dob = dob;
                allDrivers[driverIndex].gender = gender;
                allDrivers[driverIndex].address = address;
                allDrivers[driverIndex].plate_number = plateNumber;
                allDrivers[driverIndex].vehicle_brand = vehicleBrand;
                allDrivers[driverIndex].vehicle_model = vehicleModel;
                allDrivers[driverIndex].vehicle_type = vehicleType;
                allDrivers[driverIndex].vehicle_fuel = vehicleFuel;
                allDrivers[driverIndex].license_required = 'Yes';
                allDrivers[driverIndex].license_number = licenseNumber;
                allDrivers[driverIndex].shift = shift;
                allDrivers[driverIndex].working_days = workingDays;
                allDrivers[driverIndex].partner_type = partnerType;
                allDrivers[driverIndex].agency_name = agencyName;
                allDrivers[driverIndex].agency_id = agencyId;
            }
            
            showToast('Success', 'Driver profile updated successfully!', 'success');
        }
        
        driverModal.hide();
        updateStatsUI();
        renderDrivers();
    });

    // Inline status change handler
    window.changeDriverStatus = function(id, newStatus) {
        const driverIndex = allDrivers.findIndex(d => d.id === id);
        if (driverIndex > -1) {
            const oldStatus = allDrivers[driverIndex].status;
            if (oldStatus === newStatus) return;
            
            if (oldStatus === 'Active') statsState.active--;
            else if (oldStatus === 'Offline') statsState.offline--;
            else if (oldStatus === 'Pending') statsState.pending--;
            else if (oldStatus === 'Suspended') statsState.suspended--;
            
            if (newStatus === 'Active') statsState.active++;
            else if (newStatus === 'Offline') statsState.offline++;
            else if (newStatus === 'Pending') statsState.pending++;
            else if (newStatus === 'Suspended') statsState.suspended++;
            
            allDrivers[driverIndex].status = newStatus;
            
            updateStatsUI();
            renderDrivers();
            showToast('Status Updated', `Driver status updated to ${newStatus}.`, 'info');
        }
    };

    // Inline delete profile handler using SweetAlert2
    window.deleteDriver = function(id) {
        const driver = allDrivers.find(d => d.id === id);
        if (!driver) return;

        Swal.fire({
            title: 'Delete Driver?',
            text: `Are you sure you want to permanently delete the profile of ${driver.name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger me-2 px-3 py-2',
                cancelButton: 'btn btn-outline-secondary px-3 py-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const driverIndex = allDrivers.findIndex(d => d.id === id);
                if (driverIndex > -1) {
                    const status = allDrivers[driverIndex].status;
                    
                    statsState.total--;
                    if (status === 'Active') statsState.active--;
                    else if (status === 'Offline') statsState.offline--;
                    else if (status === 'Pending') statsState.pending--;
                    else if (status === 'Suspended') statsState.suspended--;
                    
                    allDrivers.splice(driverIndex, 1);
                    
                    updateStatsUI();
                    renderDrivers();
                    showToast('Deleted', 'Driver profile has been successfully deleted.', 'warning');
                }
            }
        });
    };

    // Initial render call
    updateStatsUI();
    renderDrivers();
});
</script>
@endsection
