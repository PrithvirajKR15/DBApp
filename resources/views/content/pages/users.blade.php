@extends('layouts/contentNavbarLayout')

@section('title', 'Users')
@section('page-title', 'System Users')


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

    .is-invalid {
        border: 1px solid #dc3545 !important;
    }

    .input-group.is-invalid {
        border: 1px solid #dc3545 !important;
        border-radius: 8px;
    }

    .error-text {
        color: #dc3545;
        font-size: 12px;
        margin-top: 4px;
    }
</style>


<!-- KPI Cards + Add User Button -->
<div class="row mb-4 align-items-stretch">

    <!-- Active Card -->
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card shadow-none border summary-card h-100">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3"
                    style="width:48px;height:48px;background:rgba(40,199,111,.1);color:#28c76f;">
                    <i class="bx bx-check-circle" style="font-size:1.5rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block">Active</small>
                    <h4 class="mb-0 fw-bold" id="card-active-val">0</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Suspended Card -->
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card shadow-none border summary-card h-100">
            <div class="card-body d-flex align-items-center p-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-3"
                    style="width:48px;height:48px;background:rgba(255,62,29,.1);color:#ff3e1d;">
                    <i class="bx bx-block" style="font-size:1.4rem;"></i>
                </div>
                <div>
                    <small class="text-uppercase fw-semibold text-muted d-block">Suspended</small>
                    <h4 class="mb-0 fw-bold" id="card-suspended-val">0</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side Button -->
    <div class="col-lg-6 d-flex justify-content-end align-items-center mb-3">
        <button class="btn btn-primary-orange d-flex align-items-center gap-2"
            onclick="openAddModal()" id="add-driver-btn"
            style="padding:10px 20px;border-radius:8px;">
            <i class="bx bx-plus"></i>
            <span>Add Users</span>
        </button>
    </div>

</div>

<!-- Filters & Search Toolbar -->
<div class="card shadow-none border mb-4" style="border-radius: 12px;">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 flex-wrap">
            <!-- Search Input & Status Pills -->
            <div class="d-flex align-items-center gap-3 grow flex-wrap">
                <!-- Search bar -->
                <div class="input-group input-group-merge border rounded overflow-hidden" style="width: 320px; border-color: #e0e2e7 !important; border-radius: 8px !important;">
                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted" style="font-size: 1.1rem;"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent ps-1" placeholder="Search by name, ID, phone..." id="search-driver" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                </div>
                
                <!-- Status pills -->
                <div class="d-flex align-items-center gap-2 flex-wrap" id="status-filter-pills">
                    <button type="button" class="btn btn-pill filter-pill active" data-status="all">All</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="active">Active</button>
                    <button type="button" class="btn btn-pill filter-pill" data-status="inactive">Inactive</button>
                </div>
            </div>
            
            <!-- Sort and Filter actions -->
            <div class="d-flex align-items-center gap-2">
                <!-- Sort dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary d-flex align-items-center gap-2 border" type="button" id="sortDropdown" >
                        <span id="current-sort-label">Sort: Newest</span>
                    </button>
                    {{-- <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item active" href="javascript:void(0);" data-sort="newest" onclick="handleSort('newest', 'Sort: Newest')">Sort: Newest</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="oldest" onclick="handleSort('oldest', 'Sort: Oldest')">Sort: Oldest</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="name-asc" onclick="handleSort('name-asc', 'Name (A-Z)')">Name (A-Z)</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="name-desc" onclick="handleSort('name-desc', 'Name (Z-A)')">Name (Z-A)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="deliveries-desc" onclick="handleSort('deliveries-desc', 'Most Deliveries')">Most Deliveries</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-sort="rating-desc" onclick="handleSort('rating-desc', 'Highest Rating')">Highest Rating</a></li>
                    </ul> --}}
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Table Header Stats Info -->
<div class="d-flex align-items-center justify-content-between mb-3 px-1">
    <div class="text-muted" style="font-size: 0.88rem;" id="showing-text">
        Showing <span class="fw-semibold text-body" id="showing-range">1-9</span> of <span class="fw-semibold text-body" id="showing-total">0</span> drivers
    </div>
</div>

<!-- List View Container (Table) -->
<div id="list-view-container">
    <div class="card shadow-none border" style="border-radius: 12px; background-color: #ffffff; overflow: hidden;">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover mb-0" id="drivers-table">
                <thead>
                    <tr class="table-light border-bottom" style="background-color: #fcfcfc;">
                        {{-- <th style="width: 40px; padding: 16px 20px;">
                            <div class="form-check m-0">
                                <input class="form-check-input select-all-checkbox" type="checkbox" id="selectAll">
                            </div>
                        </th> --}}
                        <th class="fw-bold">Name</th>
                        <th class="fw-bold">Email Address</th>
                        <th class="fw-bold">Phone</th>
                        <th class="fw-bold" id="table-header-location">Assigned Store</th>
                        <th class="fw-bold">Role</th>
                        <th class="fw-bold">Joined</th>
                        <th class="fw-bold text-end" style="padding-right: 24px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-tbody" class="table-border-bottom-0">
                    <!-- Loaded dynamically via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="d-flex justify-content-between align-items-center mt-3">

    <div class="text-muted" id="pagination-info"></div>

    <nav>
        <ul class="pagination mb-0" id="pagination"></ul>
    </nav>

</div>


<!-- Add / Edit Driver Modal (Figma Styled Tabs) -->
<div class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 620px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header border-bottom-0 pb-2 d-flex align-items-start justify-content-between p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon">
                        <i class="bx bx-user-plus" id="headerIconSymbol" style="font-size: 1.4rem;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0" id="modalTitle" style="font-size: 1.15rem;">Add New User</h5>
                        <small class="text-muted" id="modalSubtitle" style="font-size: 0.85rem;">Fill in the details to register a new user</small>
                    </div>
                </div>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="{{ route('user.store') }}" enctype="multipart/form-data" id="userForm" novalidate>
                @csrf
                <input type="hidden" id="user-action-type" value="add">
                <input type="hidden" id="edit-user-id-hidden">
                
                <!-- Tabs Content -->
                <div class="modal-body py-1 px-4">
                    <div class="tab-content p-0 border-0 shadow-none">
                        
                        <!-- TAB 1: Personal Info -->
                        <div class="tab-pane fade show active" id="tab-personal-info" role="tabpanel" aria-labelledby="personal-tab">
                            <!-- Profile Picture Row -->
                            <div class="profile-upload-wrapper">
                                <div class="avatar-preview-container" id="avatarPreviewBox" onclick="document.getElementById('user-avatar-file').click()">
                                    <img id="avatarPreviewImg" src="" style="display: none;">
                                    <i class="bx bx-user avatar-placeholder-icon" id="avatarPlaceholderIcon"></i>
                                    <div class="avatar-upload-badge">
                                        <i class="bx bx-camera text-white" style="font-size: 0.85rem;"></i>
                                    </div>
                                </div>
                                <input type="file" id="user-avatar-file" name="user-avatar-file" accept="image/*" class="d-none">
                                <div>
                                    <h6 class="mb-1 fw-bold text-body" style="font-size: 0.95rem;">Profile Photo</h6>
                                    <small class="text-muted d-block" style="font-size: 0.8rem;">Upload a clear face photo. JPG or PNG, max 5MB.</small>
                                </div>
                            </div>

                            <!-- Name Columns -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="user-first-name" class="form-label text-body fw-semibold">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="user-first-name" name="user-first-name" placeholder="e.g. Alex" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="user-last-name" class="form-label text-body fw-semibold">Last Name </label>
                                    <input type="text" class="form-control" id="user-last-name" name="user-last-name" placeholder="e.g. Smith" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                </div>
                            </div>

                            <!-- Contact info (Email & Phone Number) -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="user-email" class="form-label text-body fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-envelope text-muted" style="font-size: 1.1rem;"></i></span>
                                        <input type="email" class="form-control border-0 bg-transparent ps-2" id="user-email" name="user-email" placeholder="user@email.com" required style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="user-phone" class="form-label text-body fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-phone text-muted" style="font-size: 1.1rem;"></i></span>
                                        <span class="input-group-text border-0 bg-transparent pe-1 ps-1 fw-semibold text-body" style="font-size: 0.88rem;">+91</span>
                                        <div style="width: 1px; background-color: #d9dee3; margin: 8px 0; height: 22px;"></div>
                                        <input type="tel" class="form-control border-0 bg-transparent ps-2" id="user-phone" name="user-phone" placeholder="98765 43210" required pattern="[0-9]{10}" maxlength="10" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                    </div>
                                </div>
                            </div>

                            <!-- DOB & Gender -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="user-dob" class="form-label text-body fw-semibold">Date of Birth</label>
                                    <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                        <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-calendar text-muted" style="font-size: 1.1rem;"></i></span>
                                        <input type="date" class="form-control border-0 bg-transparent ps-2" id="user-dob" name="user-dob" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label for="user-gender" class="form-label text-body fw-semibold">Gender</label>
                                    <select class="form-select" id="user-gender" name="user-gender" style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="" disabled selected>Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Home Address -->
                            <div class="mb-3">
                                <label for="user-address" class="form-label text-body fw-semibold">Home Address</label>
                                <div class="input-group input-group-merge border rounded overflow-hidden" style="border-color: #d9dee3 !important;">
                                    <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-map-pin text-muted" style="font-size: 1.1rem;"></i></span>
                                    <input type="text" class="form-control border-0 bg-transparent ps-2" id="user-address" name="user-address" placeholder="Street address, city, state, ZIP" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
                                </div>
                            </div>

                            

                            <!-- Role -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="user-role" class="form-label text-body fw-semibold">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="user-role" name="user-role" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="" disabled selected>Select Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="user-store" class="form-label text-body fw-semibold">Store </label>
                                    <select class="form-select" id="user-store" name="user-store" required style="border-radius: 8px; font-size: 0.88rem; height: 38px;">
                                        <option value="" disabled selected>Select Store</option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="password" class="form-label text-body fw-semibold">
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            placeholder="Enter Password"
                                            required
                                            style="border-radius: 8px 0 0 8px; font-size: 0.88rem; height: 38px;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label text-body fw-semibold">
                                        Confirm Password <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control"
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            placeholder="Confirm Password"
                                            required
                                            style="border-radius: 8px 0 0 8px; font-size: 0.88rem; height: 38px;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>


                        </div>

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
                            <span id="save-btn-text">Add User</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Logic for Driver Management -->
<script>
let userModal;

document.addEventListener('DOMContentLoaded', function () {
    userModal = new bootstrap.Modal(document.getElementById('usersModal'));
});

function openAddModal() {

    const form = document.getElementById('userForm');

    // Reset form
    form.reset();

    // IMPORTANT: reset action back to create
    form.action = "{{ route('user.store') }}";

    // Reset mode
    document.getElementById('user-action-type').value = 'add';
    document.getElementById('edit-user-id-hidden').value = '';

    document.getElementById('modalTitle').innerText = 'Add New User';
    document.getElementById('modalSubtitle').innerText = 'Fill in the details to register a new user';
    document.getElementById('save-btn-text').innerText = 'Add User';

    document.getElementById('headerIconSymbol').className = "bx bx-user-plus";
    document.getElementById('footerIconSymbol').className = "bx bx-user-plus";

    document.getElementById('password').required = true;
    document.getElementById('password_confirmation').required = true;


    // Reset avatar
    document.getElementById('avatarPreviewImg').src = '';
    document.getElementById('avatarPreviewImg').style.display = 'none';
    document.getElementById('avatarPlaceholderIcon').style.display = 'block';


    userModal.show();
}

const avatarInput = document.getElementById('user-avatar-file');
const avatarImg = document.getElementById('avatarPreviewImg');
const avatarPlaceholder = document.getElementById('avatarPlaceholderIcon');

avatarInput.addEventListener('change', function (e) {

    const file = e.target.files[0];

    if (!file) return;

    // Allow only image files
    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image.');
        this.value = '';
        return;
    }

    // Max 5MB
    if (file.size > 5 * 1024 * 1024) {
        alert('Image size must be less than 5MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {

        avatarImg.src = event.target.result;
        avatarImg.style.display = 'block';

        avatarPlaceholder.style.display = 'none';

    };

    reader.readAsDataURL(file);

});
document.getElementById("userForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const form = this;

    document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    document.querySelectorAll(".error-text").forEach(el => el.remove());

    let firstInvalid = null;

    function showError(field, message) {
        const target = field.closest(".input-group") || field;

        target.classList.add("is-invalid");

        if (!firstInvalid) {
            firstInvalid = field;
        }

        const error = document.createElement("div");
        error.className = "error-text text-danger mt-1";
        error.innerText = message;

        target.parentNode.appendChild(error);
    }

    function validateRequired(id, message) {
        const field = document.getElementById(id);

        if (!field.value.trim()) {
            showError(field, message);
            return false;
        }

        return true;
    }

    // First Name
    const firstName = document.getElementById("user-first-name");
    if (validateRequired("user-first-name", "First name is required")) {
        if (firstName.value.trim().length < 3) {
            showError(firstName, "First name must be at least 3 characters.");
        }
    }

    // Email
    const email = document.getElementById("user-email");
    if (validateRequired("user-email", "Email is required")) {

        const emailRegex =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(email.value.trim())) {
            showError(email, "Please enter a valid email address.");
        }
    }

    // Phone
    const phone = document.getElementById("user-phone");
    if (validateRequired("user-phone", "Phone number is required")) {

        if (!/^\d{10}$/.test(phone.value.trim())) {
            showError(phone, "Phone number must contain exactly 10 digits.");
        }
    }

    // Role
    validateRequired("user-role", "Role is required");

    // Store
    const role = document.getElementById("user-role").value.toLowerCase();
    //console.log("selected role:", role);
    // if (role === "store_admin") {
    if (role === "2") {
        validateRequired("user-store", "Store is required");
    }

    // Password validation
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("password_confirmation");

    const mode = document.getElementById('user-action-type').value;


    if (mode === "add") {

        // Required during add

        if (validateRequired("password", "Password is required")) {

            const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&^#()_\-+=])[A-Za-z\d@$!%*?&^#()_\-+=]{8,}$/;


            if (!passwordRegex.test(password.value.trim())) {

                showError(
                    password,
                    "Password must be at least 8 characters and include uppercase, lowercase, number, and special character."
                );

            }
        }


        if (validateRequired("password_confirmation", "Confirm password is required")) {

            if (password.value !== confirmPassword.value) {
                showError(confirmPassword, "Passwords do not match.");
            }

        }

    }
    else {

        // Edit mode
        // Only validate if user entered password

        if(password.value.trim() !== "") {


            const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&^#()_\-+=])[A-Za-z\d@$!%*?&^#()_\-+=]{8,}$/;


            if (!passwordRegex.test(password.value.trim())) {

                showError(
                    password,
                    "Password must be at least 8 characters and include uppercase, lowercase, number, and special character."
                );

            }


            if(password.value !== confirmPassword.value) {

                showError(
                    confirmPassword,
                    "Passwords do not match."
                );

            }

        }

    }

    if (firstInvalid) {
        firstInvalid.focus();
        firstInvalid.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });

        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: "Do you want to save this user?",
        //icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Save",
        cancelButtonText: "Cancel"
    }).then((result) => {

        if (result.isConfirmed) {

            let formData = new FormData(form);

            let action = form.action;

            let mode = document.getElementById('user-action-type').value;

            let actionText = mode === "edit" 
                ? "Updating user..." 
                : "Adding user...";


            // Processing Alert
            Swal.fire({
                title: actionText,
                text: "Please wait while we save the information.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });


            fetch(action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {


                Swal.close();


                if(data.status){

                    Swal.fire({
                        toast:true,
                        position:'top-end',
                        icon:'success',
                        title:data.message,
                        showConfirmButton:false,
                        timer:2500,
                        timerProgressBar:true
                    });

                    const modalElement = document.getElementById('usersModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);

                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    userModal.hide();

                    loadUsers();
                }
                else {

                    Swal.fire({
                        icon:'error',
                        title:'Failed',
                        text:data.message ?? 'Unable to save user'
                    });

                }

            })
            .catch(error=>{

                Swal.close();

                Swal.fire({
                    icon:'error',
                    title:'Error',
                    text:'Something went wrong while saving user'
                });

                console.log(error);

            });

        }

    });

});

document.querySelectorAll("#userForm input,#userForm select,#userForm textarea").forEach(field=>{

    field.addEventListener("input",function(){

        this.classList.remove("is-invalid");

        if(this.closest(".input-group")){
            this.closest(".input-group").classList.remove("is-invalid");
        }

        const error=this.parentNode.querySelector(".error-text")
            || this.parentNode.parentNode.querySelector(".error-text");

        if(error){
            error.remove();
        }

    });

    field.addEventListener("change",function(){

        this.classList.remove("is-invalid");

        if(this.closest(".input-group")){
            this.closest(".input-group").classList.remove("is-invalid");
        }

        const error=this.parentNode.querySelector(".error-text")
            || this.parentNode.parentNode.querySelector(".error-text");

        if(error){
            error.remove();
        }

    });

});
function togglePassword(inputId, button) {
    //console.log('hai');
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}

function toggleUserStatus(id,status)
{
    //console.log("Current status : ", status);

    let action = status === 'Active'
        ? 'deactivate'
        : 'activate';

    //console.log("Current action : ", action);


    Swal.fire({
        title:"Are you sure?",
        text:`Do you want to ${action} this user?`,
        icon:"warning",
        showCancelButton:true,
        confirmButtonText:"Yes",
        cancelButtonText:"Cancel"
    })
    .then((result)=>{


        if(result.isConfirmed)
        {

            fetch(`/users/${id}/toggle-status`,{

                method:"POST",

                headers:{
                    "X-CSRF-TOKEN":
                    document.querySelector('input[name="_token"]').value,

                    "Accept":"application/json"
                }

            })
            .then(res=>res.json())
            .then(data=>{


                if(data.status)
                {

                    Swal.fire({

                        toast:true,
                        position:"top-end",
                        icon:"success",
                        title:data.message,
                        showConfirmButton:false,
                        timer:2000

                    });


                    loadUsers();

                }

            });


        }


    });


}


let currentPage = 1;
let currentSearch = '';
let currentStatus = 'all';
let currentSort = 'newest';

function loadUsers(page = 1) {

    fetch(`{{ route('users.list') }}?page=${page}&search=${currentSearch}&status=${currentStatus}&sort=${currentSort}`)
    .then(res => res.json())
    .then(res => {

        // <td>
        //    <input class="form-check-input row-checkbox" type="checkbox">
        // </td>

        const tbody = document.getElementById('users-tbody');

        tbody.innerHTML = '';


        res.users.forEach(user => {

            tbody.innerHTML += `
            <tr>

            

            <td>
                <img src="${user.avatar}" 
                class="rounded-circle me-2"
                width="40"
                height="40">

                ${user.name}
            </td>

            <td>${user.email}</td>

            <td>${user.phone}</td>

            <td>${user.store}</td>

            <td>${user.role}</td>

            <td>${user.joined}</td>

            <td>
                <div class="d-flex gap-2">

                    <button class="btn btn-sm btn-icon btn-outline-primary"
                        onclick="editUser(${user.id})"
                        title="Edit">
                        <i class="bx bx-edit"></i>
                    </button>


                    <button class="btn btn-sm btn-icon ${user.status === 'Active' ? 'btn-outline-danger' : 'btn-outline-success'}"
                        onclick="toggleUserStatus(${user.id}, '${user.status}')"
                        title="${user.status === 'Active' ? 'Deactivate' : 'Activate'}">

                        <i class="bx ${user.status === 'Active' ? 'bx-user-x' : 'bx-user-check'}"></i>

                    </button>
                    <pre>${user.status}</pre>

                </div>
            </td>

            </tr>
            `;
        });


        document.getElementById('showing-range').innerHTML =
            `${res.from}-${res.to}`;

        document.getElementById('showing-total').innerHTML =
            res.total;


        renderPagination(res);


    });
}
function renderPagination(res) {

    let html = '';

    let current = res.current_page;
    let last = res.last_page;


    // Previous button
    html += `
    <li class="page-item ${current == 1 ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0)" onclick="loadUsers(${current - 1})">
            Previous
        </a>
    </li>`;


    // Page 1
    html += `
    <li class="page-item ${current == 1 ? 'active' : ''}">
        <a class="page-link" href="javascript:void(0)" onclick="loadUsers(1)">
            1
        </a>
    </li>`;


    // Middle pages
    if (current > 3) {
        html += `
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>`;
    }


    let start = Math.max(2, current - 1);
    let end = Math.min(last - 1, current + 1);


    for (let i = start; i <= end; i++) {

        html += `
        <li class="page-item ${current == i ? 'active' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadUsers(${i})">
                ${i}
            </a>
        </li>`;
    }


    // Ellipsis before last
    if (current < last - 2) {

        html += `
        <li class="page-item disabled">
            <span class="page-link">...</span>
        </li>`;
    }


    // Last page
    if (last > 1) {

        html += `
        <li class="page-item ${current == last ? 'active' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadUsers(${last})">
                ${last}
            </a>
        </li>`;
    }


    // Next button
    html += `
    <li class="page-item ${current == last ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0)" onclick="loadUsers(${current + 1})">
            Next
        </a>
    </li>`;


    document.getElementById('pagination').innerHTML = html;
}
document.getElementById('search-driver')
.addEventListener('keyup',function(){

    currentSearch=this.value;

    loadUsers(1);

});
document.querySelectorAll('.filter-pill')
.forEach(btn=>{

btn.addEventListener('click',function(){

    document.querySelectorAll('.filter-pill')
    .forEach(x=>x.classList.remove('active'));

    this.classList.add('active');


    currentStatus=this.dataset.status;

    loadUsers(1);

});

});
document.addEventListener('DOMContentLoaded', function () {

    userModal = new bootstrap.Modal(document.getElementById('usersModal'));

    loadUsers();

});




function editUser(id)
{
    fetch(`/users/${id}/edit`)
        .then(res => res.json())
        .then(res => {

            let user = res.user;


            // change modal mode
            document.getElementById('user-action-type').value = 'edit';
            document.getElementById('edit-user-id-hidden').value = user.id;


            // Header change
            document.getElementById('modalTitle').innerHTML = "Edit User";
            document.getElementById('modalSubtitle').innerHTML = "Update user information";


            document.getElementById('headerIconSymbol').className = "bx bx-edit";
            document.getElementById('footerIconSymbol').className = "bx bx-save";
            document.getElementById('save-btn-text').innerHTML = "Update User";


            // Fill inputs

            let name = user.name.split(' ');

            document.getElementById('user-first-name').value = name[0] ?? '';

            document.getElementById('user-last-name').value = name.slice(1).join(' ') ?? '';

            document.getElementById('user-email').value = user.email;

            document.getElementById('user-phone').value = user.mobile;

            document.getElementById('user-dob').value = user.dob ?? '';

            document.getElementById('user-gender').value = user.gender ?? '';

            document.getElementById('user-address').value = user.address ?? '';

            document.getElementById('user-role').value = user.role_id;

            document.getElementById('user-store').value = user.store_id ?? '';



            // image preview

            if(user.image)
            {
                document.getElementById('avatarPreviewImg').src =
                    `/storage/${user.image}`;

                document.getElementById('avatarPreviewImg').style.display='block';

                document.getElementById('avatarPlaceholderIcon').style.display='none';
            }



            // hide password in edit

            document.getElementById('password').required = false;
            document.getElementById('password_confirmation').required = false;


            new bootstrap.Modal(
                document.getElementById('usersModal')
            ).show();

        });
}


document.getElementById('userForm')
.addEventListener('submit',function(){

    let mode = document.getElementById('user-action-type').value;

    if(mode === 'edit')
    {
        let id=document.getElementById('edit-user-id-hidden').value;

        this.action = `/users/${id}/update`;
    }

});
</script>
@if ($errors->any())
<script>
document.addEventListener("DOMContentLoaded", function () {

    const errors = @json($errors->all());

    Swal.fire({
        //icon: 'error',
        title: 'Validation Failed',
        html: `
            <div style="text-align:left;">
                <p style="margin-bottom:10px;">Please correct the following:</p>
                <ul style="padding-left:20px;margin:0;">
                    ${errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            </div>
        `,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6',
        width: '420px',
        allowOutsideClick: false,
        focusConfirm: true
    });

});
</script>
@endif
@if(session('success'))
<script>
document.addEventListener("DOMContentLoaded", function () {

    Swal.fire({
        toast: true,
        position: 'top-end',
        //icon: 'success',
        title: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });

});
</script>
@endif
@endsection