@php
$isNavbar = false;

$backType = request()->query('type', $driver['type'] ?? 'store');
$backUrl = $backType === 'zone' ? route('fleet-drivers-zone') : route('fleet-drivers-store');
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Manage Driver: ' . $driver['id'])
@section('page-title', 'Manage Driver')

@section('content')
<style>
    /* Styling according to Figma designs */
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

    /* Back Link & Header Row */
    .back-link {
        color: #566a7f;
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: color 0.15s ease;
    }
    .back-link:hover {
        color: #ff7a00;
    }

    /* Driver Profile Left Card */
    .driver-avatar-wrapper {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto;
    }
    .driver-avatar-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    .driver-status-dot {
        position: absolute;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid #ffffff;
        bottom: 4px;
        right: 4px;
    }
    .driver-status-dot.active {
        background-color: #28c76f;
    }
    .driver-status-dot.offline {
        background-color: #8592a3;
    }
    .driver-status-dot.transit {
        background-color: #00bad1;
    }
    .driver-status-dot.pending {
        background-color: #ffab00;
    }
    .driver-status-dot.suspended {
        background-color: #ff3e1d;
    }
    .driver-status-dot.rejected {
        background-color: #ea5455;
    }

    /* Vertical Navigation Pills */
    .nav-profile-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #566a7f;
        font-weight: 500;
        border-radius: 8px;
        margin-bottom: 6px;
        transition: all 0.2s ease;
        cursor: pointer;
        background: transparent;
        border: none;
        width: 100%;
        text-align: left;
    }
    .nav-profile-item:hover {
        background-color: #f8fafc;
        color: #32475c;
    }
    .nav-profile-item.active {
        background-color: #f0f2ff;
        color: #4f46e5;
        font-weight: 600;
    }
    .nav-profile-item i {
        font-size: 1.25rem;
    }

    /* Stats KPI grid */
    .kpi-card {
        border-radius: 12px;
        background-color: #ffffff;
        border: 1px solid #e0e2e7;
        padding: 16px;
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    .kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #8592a3;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #32475c;
        line-height: 1.2;
    }
    .kpi-meta {
        font-size: 0.8rem;
        margin-top: 4px;
        font-weight: 500;
    }
    .kpi-meta.success {
        color: #28c76f;
    }
    .kpi-meta.danger {
        color: #ff3e1d;
    }
    .kpi-meta.muted {
        color: #8592a3;
    }

    /* Status Badges */
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

    /* Inputs Styling */
    .profile-input-box {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        color: #475569 !important;
        font-size: 0.88rem !important;
        font-weight: 500 !important;
        padding: 10px 14px !important;
        height: 38px !important;
    }
    .profile-input-box:focus {
        background-color: #ffffff !important;
        border-color: #ff7a00 !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 122, 0, 0.1) !important;
    }

    /* Areas Styling */
    .area-badge {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        width: 100%;
        margin-bottom: 8px;
        font-weight: 500;
        color: #475569;
        font-size: 0.88rem;
    }
    .area-badge.checked {
        border-color: #cbd5e1;
    }

    /* Switch Custom Orange */
    .form-check-input:checked {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
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

<!-- Top Navigation & Title Bar -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ $backUrl }}" class="back-link">
            <i class="bx bx-arrow-back" style="font-size: 1.2rem;"></i>
            <span>Back to Drivers</span>
        </a>
        <span class="text-muted">|</span>
        <h4 class="mb-0 fw-bold text-body" style="font-size: 1.4rem;">Manage Driver: <span id="header-driver-id">{{ $driver['id'] }}</span></h4>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- Notification Bell -->
        <div class="bell-notification">
            <i class="bx bx-bell" style="font-size: 1.3rem;"></i>
            <span class="notification-dot animate-pulse"></span>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Quick Profile Card & Navigation -->
    <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
        <!-- Profile Quick Card -->
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
            <div class="card-body text-center p-4">
                <div class="driver-avatar-wrapper mb-3">
                    @php
                    $avatarSrc = $driver['avatar'] ?? '1.png';
                    if (
                        ! str_starts_with($avatarSrc, 'data:image')
                        && ! str_starts_with($avatarSrc, 'http://')
                        && ! str_starts_with($avatarSrc, 'https://')
                        && ! str_starts_with($avatarSrc, '/')
                    ) {
                        $avatarSrc = asset('assets/img/avatars/' . $avatarSrc);
                    }
                    $statusClass = strtolower($driver['status'] ?? 'pending');
                    $sidebarStatusText = $driver['status'] ?? 'Pending';
                    if ($sidebarStatusText === 'Pending') {
                        $sidebarStatusText = 'Pending Review';
                    } elseif ($sidebarStatusText === 'Active') {
                        $avail = $driver['availability'] ?? 'Offline';
                        $sidebarStatusText = in_array($avail, ['Online', 'Transit', 'Offline'], true) ? $avail : 'Offline';
                        $statusClass = strtolower($sidebarStatusText);
                    }
                    @endphp
                    <img src="{{ $avatarSrc }}" alt="Driver Avatar" class="rounded-circle" id="sidebar-avatar">
                    <span class="driver-status-dot {{ $statusClass }}" id="sidebar-status-dot"></span>
                </div>
                
                <h5 class="fw-bold text-body mb-1" id="sidebar-driver-name">{{ $driver['name'] }}</h5>
                <p class="text-muted mb-3" style="font-size: 0.88rem;" id="sidebar-driver-id-label">{{ $driver['id'] }}</p>
                
                <span class="status-badge-custom {{ $statusClass }}" id="sidebar-status-badge">
                    <span class="dot"></span>
                    <span id="sidebar-status-text">{{ $sidebarStatusText }}</span>
                </span>
            </div>
        </div>

        <!-- Navigation Tab Menu -->
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff; padding: 8px;">
            <button class="nav-profile-item active" onclick="switchProfileTab('personal-info')">
                <i class="bx bx-user"></i>
                <span>Personal Info</span>
            </button>
            <button class="nav-profile-item" onclick="switchProfileTab('vehicle-areas')">
                <i class="bx bx-car"></i>
                <span>Vehicle & Areas</span>
            </button>
            <button class="nav-profile-item" onclick="switchProfileTab('performance')">
                <i class="bx bx-line-chart"></i>
                <span>Performance</span>
            </button>
            <button class="nav-profile-item" onclick="switchProfileTab('delivery-history')">
                <i class="bx bx-history"></i>
                <span>Delivery History</span>
            </button>
            <button class="nav-profile-item" onclick="switchProfileTab('settings')">
                <i class="bx bx-cog"></i>
                <span>Settings</span>
            </button>
        </div>
    </div>

    <!-- Right Column: Details & Tabs Content -->
    <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
        
        <!-- Row of Summary Stats Cards (Visible on most tabs) -->
        <div class="row g-3 mb-4">
            <!-- Deliveries Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Deliveries Completed</div>
                    <div class="kpi-value" id="kpi-deliveries-val">{{ number_format($driver['deliveries']) }}</div>
                    <div class="kpi-meta success" id="kpi-deliveries-change">{{ $driver['deliveries_change'] ?? '↑ 12% this month' }}</div>
                </div>
            </div>
            
            <!-- Rating Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Customer Rating</div>
                    <div class="kpi-value d-flex align-items-center gap-1">
                        <span id="kpi-rating-val">{{ $driver['rating'] }}</span>
                        @if ($driver['rating'] !== '—')
                        <i class="bx bxs-star text-warning" style="font-size: 1.3rem;"></i>
                        @endif
                    </div>
                    <div class="kpi-meta muted" id="kpi-rating-reviews">{{ $driver['rating_reviews'] ?? 'From 850 reviews' }}</div>
                </div>
            </div>
            
            <!-- Earnings Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Total Earnings</div>
                    <div class="kpi-value" id="kpi-earnings-val">{{ $driver['earnings'] ?? '$8,450.00' }}</div>
                    <div class="kpi-meta success" id="kpi-earnings-change">{{ $driver['earnings_change'] ?? '+$420 this week' }}</div>
                </div>
            </div>
            
            <!-- Failed Deliveries Card -->
            <div class="col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Failed Deliveries</div>
                    <div class="kpi-value" id="kpi-failed-val">{{ $driver['failed_deliveries'] ?? '12' }}</div>
                    <div class="kpi-meta danger" id="kpi-failed-rate">{{ $driver['failure_rate'] ?? '0.9% failure rate' }}</div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT 1: Personal Info (Default) -->
        <div id="tab-personal-info-content" class="tab-pane-content">
            <!-- Personal Information Form Card -->
            <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
                <div class="card-header border-bottom-0 pb-2 d-flex align-items-center justify-content-between p-4 bg-transparent">
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 1.1rem;">Personal Information</h5>
                    <button class="btn btn-link text-primary-orange p-0 d-flex align-items-center gap-1 border-0 bg-transparent fw-semibold" onclick="enablePersonalInfoEditing()" id="edit-details-btn">
                        <i class="bx bx-edit-alt" style="font-size: 1.15rem;"></i>
                        <span>Edit Details</span>
                    </button>
                    <button class="btn btn-sm btn-primary-orange px-3 d-none" onclick="savePersonalInfoChanges()" id="save-details-btn" style="border-radius: 6px;">
                        Save
                    </button>
                </div>
                
                <div class="card-body px-4 pb-4 pt-1">
                    <form id="personal-info-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Full Name</label>
                                <input type="text" class="form-control profile-input-box" id="info-fullname" value="{{ $driver['name'] }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Mobile Number</label>
                                <input type="text" class="form-control profile-input-box" id="info-phone" value="{{ $driver['phone'] }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Email Address</label>
                                <input type="email" class="form-control profile-input-box" id="info-email" value="{{ $driver['email'] }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Registration Date</label>
                                <input type="text" class="form-control profile-input-box" id="info-joined" value="{{ $driver['joined'] }}" disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Full Address</label>
                                <input type="text" class="form-control profile-input-box" id="info-address" value="{{ $driver['address'] }}" disabled>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Operational Details Card -->
            <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
                <div class="card-header border-bottom-0 pb-2 p-4 bg-transparent">
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 1.1rem;">Operational Details</h5>
                </div>
                
                <div class="card-body px-4 pb-4 pt-1">
                    <div class="row g-4">
                        @if ($driver['type'] === 'zone')
                        @php
                            $isThirdParty = ($driver['partner_type'] ?? 'independent') === 'third-party';
                        @endphp
                        <!-- Partner Type Choices -->
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold mb-3" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">Partner Type</label>

                            @if ($isThirdParty)
                            <div class="partner-card mb-3 p-3 border rounded d-flex align-items-start justify-content-between position-relative active" id="cardPartnerThird" style="background-color: #ffffff; border-color: #ff7a00 !important; border-radius: 12px; transition: all 0.2s ease; cursor: default;">
                                <div class="d-flex align-items-start gap-3 w-100">
                                    <div class="partner-icon-box d-flex align-items-center justify-content-center" style="background-color: #fff8f2; color: #ff7a00; width: 44px; height: 44px; border-radius: 10px;">
                                        <i class="bx bx-buildings" style="font-size: 1.4rem;"></i>
                                    </div>
                                    <div>
                                        <span class="d-block fw-bold text-body" style="font-size: 0.95rem; line-height: 1.2;">Third Party Partner</span>
                                        <span class="d-block text-muted" style="font-size: 0.8rem; margin-top: 4px; line-height: 1.4;">Partnered via agency / fleet provider.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded p-3" style="border-radius: 12px;">
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-4 text-muted">Agency</dt>
                                    <dd class="col-sm-8 fw-semibold">{{ $driver['agency_name'] ?? '—' }}</dd>
                                    <dt class="col-sm-4 text-muted">Hub</dt>
                                    <dd class="col-sm-8 fw-semibold">{{ $driver['agency_hub_name'] ?? '—' }}</dd>
                                    <dt class="col-sm-4 text-muted">Reg. No.</dt>
                                    <dd class="col-sm-8 fw-semibold">{{ $driver['agency_registration_number'] ?? '—' }}</dd>
                                </dl>
                            </div>
                            @else
                            <div class="partner-card mb-3 p-3 border rounded d-flex align-items-start justify-content-between position-relative active" id="cardPartnerInd" style="background-color: #ffffff; border-color: #ff7a00 !important; border-radius: 12px; transition: all 0.2s ease; cursor: default;">
                                <div class="d-flex align-items-start gap-3 w-100 pe-4">
                                    <div class="partner-icon-box d-flex align-items-center justify-content-center" style="background-color: #fff8f2; color: #ff7a00; width: 44px; height: 44px; border-radius: 10px;">
                                        <i class="bx bx-user" style="font-size: 1.4rem;"></i>
                                    </div>
                                    <div>
                                        <span class="d-block fw-bold text-body" style="font-size: 0.95rem; line-height: 1.2;">Independent Partner</span>
                                        <span class="d-block text-muted" style="font-size: 0.8rem; margin-top: 4px; line-height: 1.4;">Direct contractor managing their own schedule.</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Assigned Service Areas -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <label class="form-label text-muted fw-bold mb-0" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">Assigned Service Areas</label>
                                <a href="javascript:void(0);" class="fw-semibold" style="font-size: 0.82rem; color: #ff7a00; text-decoration: none;" onclick="openChangeAreasModal()">Change Areas</a>
                            </div>
                            
                            @php
                            $allAreas = [
                                'pattom' => 'Pattom',
                                'kesavadasapuram' => 'Kesavadasapuram',
                                'ulloor' => 'Ulloor',
                                'murinjapalam' => 'Murinjapalam',
                                'kowdiar' => 'Kowdiar',
                                'palayam' => 'Palayam',
                                'thampanoor' => 'Thampanoor',
                                'vellayambalam' => 'Vellayambalam',
                                'statue' => 'Statue',
                                'sasthamangalam' => 'Sasthamangalam',
                                'technopark' => 'Technopark',
                                'peroorkada' => 'Peroorkada',
                                'medical-college' => 'Medical College',
                                'kazhakkoottam' => 'Kazhakkoottam',
                                'east-fort' => 'East Fort',
                                'vizhinjam' => 'Vizhinjam',
                                'kovalam' => 'Kovalam',
                            ];
                            $assignedAreas = $driver['service_areas'] ?? [];
                            $primaryZone = $driver['zone'] ?? '';
                            @endphp

                            @foreach ($allAreas as $key => $label)
                                @php
                                $isPrimary = ($primaryZone === $label);
                                $isSecondary = in_array($key, $assignedAreas) && !$isPrimary;
                                $isChecked = $isPrimary || $isSecondary;
                                @endphp
                                <div class="area-badge">
                                    <div class="d-flex align-items-center gap-2">
                                        <input class="form-check-input" type="checkbox" id="area-{{ $key }}" {{ $isChecked ? 'checked' : '' }} disabled>
                                        <label class="form-check-label fw-semibold text-body" for="area-{{ $key }}">{{ $label }}</label>
                                    </div>
                                    @if ($isPrimary)
                                        <span class="badge bg-label-primary px-2 py-1 rounded" style="font-size: 0.72rem;">Primary</span>
                                    @elseif ($isSecondary)
                                        <span class="badge bg-label-secondary px-2 py-1 rounded" style="font-size: 0.72rem; background-color: rgba(133, 146, 163, 0.1); color: #8592a3;">Secondary</span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">—</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @else
                        <!-- Store details -->
                        <div class="col-12">
                            <label class="form-label text-muted fw-bold mb-3" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px;">Assigned Store Details</label>
                            <div class="p-4 border rounded d-flex align-items-center gap-4" style="background-color: #f8fafc; border-color: #e2e8f0; border-radius: 8px;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="background-color: rgba(255, 122, 0, 0.1); width: 60px; height: 60px; color: #ff7a00;">
                                    <i class="bx bx-store" style="font-size: 2.2rem;"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-body mb-1">{{ $driver['store'] }}</h5>
                                    <p class="text-muted mb-0" style="font-size: 0.88rem;">This driver is assigned exclusively to the store listed above and fulfills orders dispatched from this location.</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Account Status & Actions Card -->
            <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
                <div class="card-header border-bottom-0 pb-2 p-4 bg-transparent">
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 1.1rem;">Account Status & Actions</h5>
                </div>
                
                <div class="card-body px-4 pb-4 pt-1">
                    @php
                        $accountStatus = $driver['status'] ?? 'Pending';
                        $availability = $driver['availability'] ?? 'Offline';
                        $isSuspended = $accountStatus === 'Suspended';
                        $isRejected = $accountStatus === 'Rejected';
                        $isPending = $accountStatus === 'Pending';
                        $isActiveAccount = $accountStatus === 'Active';
                        $isOnline = $isActiveAccount && $availability === 'Online';
                        $isTransit = $isActiveAccount && $availability === 'Transit';
                        $isOffline = $isActiveAccount && $availability === 'Offline';
                        $statusLabelClass = ($isSuspended || $isRejected)
                            ? 'text-danger'
                            : ($isPending ? 'text-warning' : ($isOnline ? 'text-success' : ($isTransit ? 'text-info' : 'text-secondary')));
                    @endphp
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border rounded p-3" style="background-color: #ffffff; border-color: #e2e8f0; border-radius: 8px;">
                        <div>
                            <div style="font-size: 0.95rem;">
                                <strong>Current Status:</strong>
                                <span class="fw-bold {{ $statusLabelClass }}" id="action-status-label"
                                      data-account-status="{{ $accountStatus }}"
                                      data-availability="{{ $availability }}">
                                    @if ($isSuspended)
                                        Suspended
                                    @elseif ($isRejected)
                                        Rejected
                                    @elseif ($isPending)
                                        Pending Review
                                    @elseif ($isActiveAccount)
                                        {{ $availability }}
                                    @else
                                        {{ $accountStatus }}
                                    @endif
                                </span>
                            </div>
                            <small class="text-muted d-block mt-1" id="action-status-desc">
                                @if ($isSuspended)
                                Driver account is temporarily suspended. They can be reactivated or deleted.
                                @elseif ($isRejected)
                                Zone driver application was rejected and is not an active fleet member.
                                @elseif ($isPending)
                                Zone driver application is awaiting admin approval.
                                @elseif ($isOnline)
                                Driver is currently online and accepting orders.
                                @elseif ($isTransit)
                                Driver is on a delivery trip and currently in transit.
                                @elseif ($isOffline)
                                Driver is currently offline and not receiving orders.
                                @else
                                Driver account status needs attention.
                                @endif
                            </small>
                        </div>
                        
                        <div class="d-flex align-items-center gap-2 flex-wrap" id="driver-action-buttons">
                            <button type="button" class="btn btn-outline-success {{ $isActiveAccount && ! $isOnline ? '' : 'd-none' }}" onclick="updateDriverStatusInline('Online')" style="border-radius: 8px; font-size: 0.85rem; font-weight: 600;" id="btn-action-online">Mark Online</button>
                            <button type="button" class="btn btn-outline-info {{ $isActiveAccount && ! $isTransit ? '' : 'd-none' }}" onclick="updateDriverStatusInline('Transit')" style="border-radius: 8px; font-size: 0.85rem; font-weight: 600; border-color: #00bad1 !important; color: #00bad1 !important;" id="btn-action-transit">Mark Transit</button>
                            <button type="button" class="btn btn-outline-secondary {{ $isActiveAccount && ! $isOffline ? '' : 'd-none' }}" onclick="updateDriverStatusInline('Offline')" style="border-radius: 8px; font-size: 0.85rem; font-weight: 600;" id="btn-action-offline">Mark Offline</button>
                            <button type="button" class="btn btn-outline-success {{ $isSuspended || $isPending || $isRejected ? '' : 'd-none' }}" onclick="updateDriverStatusInline('Active')" style="border-radius: 8px; font-size: 0.85rem; font-weight: 600;" id="btn-action-activate">{{ $isPending || $isRejected ? 'Approve Driver' : 'Activate Driver' }}</button>
                            <button type="button" class="btn btn-outline-danger {{ $isPending ? '' : 'd-none' }}" onclick="updateDriverStatusInline('Rejected')" style="border-radius: 8px; font-size: 0.85rem; font-weight: 600;" id="btn-action-reject">Reject Application</button>
                            <button type="button" class="btn btn-outline-warning {{ $isActiveAccount ? '' : 'd-none' }}" onclick="updateDriverStatusInline('Suspended')" style="border-radius: 8px; font-size: 0.85rem; font-weight: 600; border-color: #ffab00 !important; color: #ffab00 !important;" id="btn-action-suspend">Suspend Driver</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT 2: Vehicle & Areas (Hidden initially) -->
        <div id="tab-vehicle-areas-content" class="tab-pane-content d-none">
            <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
                <div class="card-header border-bottom-0 pb-2 p-4 bg-transparent">
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 1.1rem;">Vehicle Details</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Vehicle Number</label>
                            <input type="text" class="form-control profile-input-box" value="{{ $driver['plate_number'] }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Vehicle Brand & Model</label>
                            <input type="text" class="form-control profile-input-box" value="{{ $driver['vehicle_brand'] }} {{ $driver['vehicle_model'] }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Vehicle Type</label>
                            <input type="text" class="form-control profile-input-box text-capitalize" value="{{ $driver['vehicle_type'] }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Fuel Classification</label>
                            <input type="text" class="form-control profile-input-box" value="{{ $driver['vehicle_fuel'] }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Driving License Number</label>
                            <input type="text" class="form-control profile-input-box" value="{{ $driver['license_number'] }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body fw-semibold" style="font-size: 0.85rem;">Shift Timing</label>
                            <input type="text" class="form-control profile-input-box" value="{{ $driver['shift'] }}" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT 3: Performance (Hidden initially) -->
        <div id="tab-performance-content" class="tab-pane-content d-none">
            <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
                <div class="card-header border-bottom-0 pb-2 p-4 bg-transparent">
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 1.1rem;">Performance Summary</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-1">
                    <div class="row g-4">
                        <!-- Ratings metrics breakdown -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Customer Rating Breakdown</h6>
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-body fw-semibold me-2" style="width: 30px;">5 ★</span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 82%;" aria-valuenow="82" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="text-muted ms-2" style="width: 40px; font-size: 0.85rem;">82%</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-body fw-semibold me-2" style="width: 30px;">4 ★</span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 12%;" aria-valuenow="12" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="text-muted ms-2" style="width: 40px; font-size: 0.85rem;">12%</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-body fw-semibold me-2" style="width: 30px;">3 ★</span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 4%;" aria-valuenow="4" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="text-muted ms-2" style="width: 40px; font-size: 0.85rem;">4%</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-body fw-semibold me-2" style="width: 30px;">2 ★</span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 1%;" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="text-muted ms-2" style="width: 40px; font-size: 0.85rem;">1%</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="text-body fw-semibold me-2" style="width: 30px;">1 ★</span>
                                <div class="progress flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 1%;" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="text-muted ms-2" style="width: 40px; font-size: 0.85rem;">1%</span>
                            </div>
                        </div>

                        <!-- Completion & Accept Rates -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Operational Indicators</h6>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-body fw-semibold" style="font-size: 0.85rem;">Order Acceptance Rate</span>
                                    <span class="fw-bold text-success" style="font-size: 0.85rem;">98.4%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: 98.4%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-body fw-semibold" style="font-size: 0.85rem;">On-Time Delivery Rate</span>
                                    <span class="fw-bold text-primary" style="font-size: 0.85rem;">95.2%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" style="width: 95.2%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-body fw-semibold" style="font-size: 0.85rem;">Cancellation Rate</span>
                                    <span class="fw-bold text-danger" style="font-size: 0.85rem;">0.8%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" style="width: 0.8%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT 4: Delivery History (Hidden initially) -->
        <div id="tab-delivery-history-content" class="tab-pane-content d-none">
            <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff; overflow: hidden;">
                <div class="card-header border-bottom-0 pb-2 p-4 bg-transparent d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 1.1rem;">Recent Deliveries</h5>
                    <span class="badge bg-label-primary rounded-pill">Total: 1,248</span>
                </div>
                
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr class="table-light">
                                <th class="fw-bold">Order ID</th>
                                <th class="fw-bold">Customer</th>
                                <th class="fw-bold">Destination</th>
                                <th class="fw-bold">Date</th>
                                <th class="fw-bold">Amount</th>
                                <th class="fw-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="fw-semibold">#ORD-8924</span></td>
                                <td>Mike Johnson</td>
                                <td>12 Pattom Lane, Thiruvananthapuram</td>
                                <td>Today, 02:45 PM</td>
                                <td>$45.50</td>
                                <td><span class="badge bg-label-success rounded-pill" style="background-color: rgba(40, 199, 111, 0.1) !important; color: #28c76f !important;">Delivered</span></td>
                            </tr>
                            <tr>
                                <td><span class="fw-semibold">#ORD-8910</span></td>
                                <td>Sarah Connor</td>
                                <td>Kowdiar Palace Rd, Thiruvananthapuram</td>
                                <td>Yesterday, 11:30 AM</td>
                                <td>$28.00</td>
                                <td><span class="badge bg-label-success rounded-pill" style="background-color: rgba(40, 199, 111, 0.1) !important; color: #28c76f !important;">Delivered</span></td>
                            </tr>
                            <tr>
                                <td><span class="fw-semibold">#ORD-8854</span></td>
                                <td>Robert Davis</td>
                                <td>Palayam Market Rd, Thiruvananthapuram</td>
                                <td>14 Jul 2026, 06:12 PM</td>
                                <td>$37.20</td>
                                <td><span class="badge bg-label-success rounded-pill" style="background-color: rgba(40, 199, 111, 0.1) !important; color: #28c76f !important;">Delivered</span></td>
                            </tr>
                            <tr>
                                <td><span class="fw-semibold">#ORD-8821</span></td>
                                <td>Alice Cooper</td>
                                <td>Technopark Phase 1, Kazhakkoottam</td>
                                <td>13 Jul 2026, 01:20 PM</td>
                                <td>$52.10</td>
                                <td><span class="badge bg-label-success rounded-pill" style="background-color: rgba(40, 199, 111, 0.1) !important; color: #28c76f !important;">Delivered</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT 5: Settings (Hidden initially) -->
        <div id="tab-settings-content" class="tab-pane-content d-none">
            <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
                <div class="card-header border-bottom-0 pb-2 p-4 bg-transparent">
                    <h5 class="mb-0 fw-bold text-body" style="font-size: 1.1rem;">Account Settings & Preferences</h5>
                </div>
                <div class="card-body px-4 pb-4 pt-1">
                    <!-- Notification Settings -->
                    <h6 class="fw-bold mb-3 mt-2">Notification Preferences</h6>
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="sett-notify-order" checked>
                            <label class="form-check-label text-body fw-semibold" for="sett-notify-order">Notify driver on new order assignment</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="sett-notify-status" checked>
                            <label class="form-check-label text-body fw-semibold" for="sett-notify-status">Email report on weekly payout completion</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="sett-notify-audit">
                            <label class="form-check-label text-body fw-semibold" for="sett-notify-audit">SMS alerts for emergency route restrictions</label>
                        </div>
                    </div>

                    <!-- Critical Zone / Safety Actions -->
                    <hr class="my-4">
                    <h6 class="fw-bold text-danger mb-2">Danger Zone</h6>
                    <p class="text-muted small mb-3">Once deleted, the driver profile and delivery logs cannot be recovered.</p>
                    <button type="button" class="btn btn-outline-danger" onclick="triggerProfileDeletion()" style="border-radius: 8px; font-weight: 600;">Delete Driver Profile</button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Change Service Areas -->
<div class="modal fade" id="changeAreasModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-body">Modify Service Areas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changeAreasForm" onsubmit="saveAssignedAreas(event)">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <p class="text-muted small">Select the operational zones where the driver is active to deliver orders.</p>
                    </div>
                    @php
                    $allAreas = [
                        'pattom' => 'Pattom',
                        'kesavadasapuram' => 'Kesavadasapuram',
                        'ulloor' => 'Ulloor',
                        'murinjapalam' => 'Murinjapalam',
                        'kowdiar' => 'Kowdiar',
                        'palayam' => 'Palayam',
                        'thampanoor' => 'Thampanoor',
                        'vellayambalam' => 'Vellayambalam',
                        'statue' => 'Statue',
                        'sasthamangalam' => 'Sasthamangalam',
                        'technopark' => 'Technopark',
                        'peroorkada' => 'Peroorkada',
                        'medical-college' => 'Medical College',
                        'kazhakkoottam' => 'Kazhakkoottam',
                        'east-fort' => 'East Fort',
                        'vizhinjam' => 'Vizhinjam',
                        'kovalam' => 'Kovalam',
                    ];
                    $assignedAreas = $driver['service_areas'] ?? [];
                    $primaryZone = $driver['zone'] ?? '';
                    @endphp

                    @foreach ($allAreas as $key => $label)
                        @php
                        $isPrimary = ($primaryZone === $label);
                        $isSecondary = in_array($key, $assignedAreas) && !$isPrimary;
                        $isChecked = $isPrimary || $isSecondary;
                        @endphp
                        <div class="p-3 border rounded d-flex align-items-center justify-content-between service-area-item-card mb-2" id="profile-area-card-{{ $key }}" style="background-color: #f8fafc; border-color: #e2e8f0; border-radius: 8px; transition: all 0.2s ease;">
                            <div class="form-check mb-0">
                                <input class="form-check-input profile-area-checkbox-modal" type="checkbox" id="chk-{{ $key }}" value="{{ $key }}" data-label="{{ $label }}" onchange="handleProfileAreaCheckboxChange('{{ $key }}')" {{ $isChecked ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-body" for="chk-{{ $key }}">{{ $label }}</label>
                            </div>
                            
                            <!-- Primary Radio / Secondary Badge container -->
                            <div class="d-flex align-items-center gap-2">
                                <!-- Set Primary radio -->
                                <div class="form-check mb-0 p-0" id="profile-primary-radio-wrapper-{{ $key }}" style="display: {{ $isChecked && !$isPrimary ? 'block' : 'none' }};">
                                    <input class="form-check-input profile-primary-area-radio ms-0" type="radio" name="profilePrimaryArea" id="profile-primary-radio-{{ $key }}" value="{{ $key }}" data-label="{{ $label }}" onchange="handleProfilePrimaryRadioChange('{{ $key }}')" {{ $isPrimary ? 'checked' : '' }}>
                                    <label class="form-check-label text-primary-orange fw-bold cursor-pointer" for="profile-primary-radio-{{ $key }}" style="font-size: 0.75rem;">Set Primary</label>
                                </div>
                                <!-- Primary indicator badge -->
                                <span class="badge bg-label-primary px-2 py-1 rounded profile-primary-badge-indicator" id="profile-label-primary-{{ $key }}" style="display: {{ $isPrimary ? 'inline-block' : 'none' }}; font-size: 0.72rem;">Primary</span>
                                <!-- Secondary indicator badge -->
                                <span class="badge bg-label-secondary px-2 py-1 rounded profile-secondary-badge-indicator" id="profile-label-secondary-{{ $key }}" style="display: {{ $isSecondary ? 'inline-block' : 'none' }}; font-size: 0.72rem; background-color: rgba(133, 146, 163, 0.1); color: #8592a3;">Secondary</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary-orange" style="border-radius: 8px;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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

    function showToast(message, icon = 'success') {
        const type = ['success', 'error', 'warning', 'info'].includes(icon) ? icon : 'success';
        const icons = {
            success: 'bx-check',
            error: 'bx-x',
            warning: 'bx-error',
            info: 'bx-info-circle',
        };

        const toast = document.createElement('div');
        toast.className = `app-toast app-toast--${type}`;
        toast.setAttribute('role', 'status');
        toast.innerHTML = `
            <span class="app-toast__icon"><i class="bx ${icons[type]}"></i></span>
            <span class="app-toast__msg"></span>
        `;
        toast.querySelector('.app-toast__msg').textContent = message;

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
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const profileUpdateUrl = @json(url('/fleet/drivers/' . $driverId . '/profile'));
    const profileStatusUrl = @json(url('/fleet/drivers/' . $driverId . '/status'));

    // Switch between profile navigation tabs
    function switchProfileTab(tabName) {
        // Toggle active class on nav links
        const navItems = document.querySelectorAll('.nav-profile-item');
        navItems.forEach(item => item.classList.remove('active'));
        
        // Find which index matches the clicked item
        const tabsMapping = ['personal-info', 'vehicle-areas', 'performance', 'delivery-history', 'settings'];
        const activeIndex = tabsMapping.indexOf(tabName);
        if (activeIndex > -1) {
            navItems[activeIndex].classList.add('active');
        }

        // Toggle active pane visibility
        const panes = document.querySelectorAll('.tab-pane-content');
        panes.forEach(pane => pane.classList.add('d-none'));
        
        const activePane = document.getElementById(`tab-${tabName}-content`);
        if (activePane) {
            activePane.classList.remove('d-none');
        }
    }

    let isEditMode = false;

    // Enable inputs editing for Personal Information
    function enablePersonalInfoEditing() {
        isEditMode = true;
        
        document.getElementById('info-fullname').removeAttribute('disabled');
        document.getElementById('info-phone').removeAttribute('disabled');
        document.getElementById('info-email').removeAttribute('disabled');
        document.getElementById('info-address').removeAttribute('disabled');
        
        const radThird = document.getElementById('partnerTypeThird');
        const isThirdParty = radThird && radThird.checked;
        
        const agencyName = document.getElementById('info-agency-name');
        if (agencyName && isThirdParty) agencyName.removeAttribute('disabled');
        const agencyId = document.getElementById('info-agency-id');
        if (agencyId && isThirdParty) agencyId.removeAttribute('disabled');
        
        const cardInd = document.getElementById('cardPartnerInd');
        const cardThird = document.getElementById('cardPartnerThird');
        if (cardInd) cardInd.style.cursor = 'pointer';
        if (cardThird) cardThird.style.cursor = 'pointer';
        
        document.getElementById('edit-details-btn').classList.add('d-none');
        document.getElementById('save-details-btn').classList.remove('d-none');
    }

    function lockPersonalInfoInputs() {
        isEditMode = false;

        document.getElementById('info-fullname').setAttribute('disabled', 'true');
        document.getElementById('info-phone').setAttribute('disabled', 'true');
        document.getElementById('info-email').setAttribute('disabled', 'true');
        document.getElementById('info-address').setAttribute('disabled', 'true');

        const agencyName = document.getElementById('info-agency-name');
        if (agencyName) agencyName.setAttribute('disabled', 'true');
        const agencyId = document.getElementById('info-agency-id');
        if (agencyId) agencyId.setAttribute('disabled', 'true');

        const cardInd = document.getElementById('cardPartnerInd');
        const cardThird = document.getElementById('cardPartnerThird');
        if (cardInd) cardInd.style.cursor = 'default';
        if (cardThird) cardThird.style.cursor = 'default';

        document.getElementById('edit-details-btn').classList.remove('d-none');
        document.getElementById('save-details-btn').classList.add('d-none');
    }

    // Save edited Personal Details changes
    async function savePersonalInfoChanges() {
        const saveBtn = document.getElementById('save-details-btn');
        const fullname = document.getElementById('info-fullname').value.trim();
        const phone = document.getElementById('info-phone').value.trim();
        const email = document.getElementById('info-email').value.trim();
        const address = document.getElementById('info-address').value.trim();

        if (!fullname || !phone || !email) {
            showToast('Name, phone, and email are required.', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const response = await fetch(profileUpdateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    name: fullname,
                    phone: phone,
                    email: email,
                    address: address,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.status) {
                const firstError = data.errors
                    ? Object.values(data.errors).flat()[0]
                    : null;
                throw new Error(firstError || data.message || 'Failed to update profile.');
            }

            const driver = data.driver || {};
            if (driver.name) {
                document.getElementById('info-fullname').value = driver.name;
                document.getElementById('sidebar-driver-name').textContent = driver.name;
            }
            if (driver.phone) {
                document.getElementById('info-phone').value = driver.phone;
            }
            if (driver.email) {
                document.getElementById('info-email').value = driver.email;
            }
            if (driver.address !== undefined) {
                document.getElementById('info-address').value = driver.address || '';
            }

            lockPersonalInfoInputs();
            showToast(data.message || 'Personal information updated successfully!');
        } catch (error) {
            showToast(error.message || 'Failed to update profile.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    }

    // Update status inline
    function updateDriverStatusInline(newStatus) {
        const labels = {
            Online: 'online',
            Offline: 'offline',
            Transit: 'in transit',
            Active: 'active',
            Suspended: 'suspended',
            Pending: 'pending review',
            Rejected: 'rejected',
        };
        const label = labels[newStatus] || newStatus.toLowerCase();

        Swal.fire({
            title: 'Change Driver Status?',
            text: `Are you sure you want to mark this driver as ${label}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary me-2 px-3 py-2',
                cancelButton: 'btn btn-outline-secondary px-3 py-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                persistStatusUpdate(newStatus);
            }
        });
    }

    function statusPayloadFor(action) {
        if (action === 'Online' || action === 'Offline' || action === 'Transit') {
            return { availability: action };
        }
        if (action === 'Suspended') {
            return { status: 'Suspended', availability: 'Offline' };
        }
        if (action === 'Rejected') {
            return { status: 'Rejected', availability: 'Offline' };
        }
        if (action === 'Pending') {
            return { status: 'Pending', availability: 'Offline' };
        }
        if (action === 'Active') {
            return { status: 'Active', availability: 'Offline' };
        }
        return { status: action };
    }

    async function persistStatusUpdate(newStatus) {
        try {
            const response = await fetch(profileStatusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(statusPayloadFor(newStatus)),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.status) {
                throw new Error(data.message || 'Failed to update status.');
            }

            const accountStatus = data.driver?.status || (['Active', 'Suspended', 'Pending', 'Rejected'].includes(newStatus) ? newStatus : null);
            const availability = data.driver?.availability
                || (['Online', 'Offline', 'Transit'].includes(newStatus) ? newStatus : null);

            applyStatusUpdate(accountStatus, availability);
        } catch (error) {
            showToast(error.message || 'Failed to update status.', 'error');
        }
    }

    function setButtonVisible(id, visible) {
        const btn = document.getElementById(id);
        if (!btn) return;
        btn.classList.toggle('d-none', !visible);
    }

    function refreshActionButtons(accountStatus, availability) {
        const isSuspended = accountStatus === 'Suspended';
        const isRejected = accountStatus === 'Rejected';
        const isActive = accountStatus === 'Active';
        const isPending = accountStatus === 'Pending';
        const isOnline = isActive && availability === 'Online';
        const isTransit = isActive && availability === 'Transit';
        const isOffline = isActive && availability === 'Offline';

        setButtonVisible('btn-action-online', isActive && !isOnline);
        setButtonVisible('btn-action-transit', isActive && !isTransit);
        setButtonVisible('btn-action-offline', isActive && !isOffline);
        setButtonVisible('btn-action-activate', isSuspended || isPending || isRejected);
        setButtonVisible('btn-action-reject', isPending);
        setButtonVisible('btn-action-suspend', isActive);

        const activateBtn = document.getElementById('btn-action-activate');
        if (activateBtn) {
            activateBtn.textContent = (isPending || isRejected) ? 'Approve Driver' : 'Activate Driver';
        }
    }

    // Apply status update UI updates
    function applyStatusUpdate(accountStatus, availability) {
        const statusLabel = document.getElementById('action-status-label');
        const prevAccount = statusLabel?.dataset.accountStatus || 'Active';
        const prevAvailability = statusLabel?.dataset.availability || 'Offline';

        const nextAccount = accountStatus || prevAccount;
        const nextAvailability = availability || prevAvailability;

        statusLabel.dataset.accountStatus = nextAccount;
        statusLabel.dataset.availability = nextAvailability;

        let displayStatus = nextAccount;
        let desc = 'Driver account status needs attention.';
        let labelClass = 'text-secondary fw-bold';
        let sidebarStatus = nextAccount;

        if (nextAccount === 'Suspended') {
            displayStatus = 'Suspended';
            sidebarStatus = 'Suspended';
            labelClass = 'text-danger fw-bold';
            desc = 'Driver account is temporarily suspended. They can be reactivated or deleted.';
        } else if (nextAccount === 'Rejected') {
            displayStatus = 'Rejected';
            sidebarStatus = 'Rejected';
            labelClass = 'text-danger fw-bold';
            desc = 'Zone driver application was rejected and is not an active fleet member.';
        } else if (nextAccount === 'Active') {
            displayStatus = nextAvailability || 'Offline';
            sidebarStatus = displayStatus;
            if (nextAvailability === 'Online') {
                labelClass = 'text-success fw-bold';
                desc = 'Driver is currently online and accepting orders.';
            } else if (nextAvailability === 'Transit') {
                labelClass = 'text-info fw-bold';
                desc = 'Driver is on a delivery trip and currently in transit.';
            } else {
                labelClass = 'text-secondary fw-bold';
                desc = 'Driver is currently offline and not receiving orders.';
            }
        } else if (nextAccount === 'Pending') {
            displayStatus = 'Pending Review';
            sidebarStatus = 'Pending';
            labelClass = 'text-warning fw-bold';
            desc = 'Zone driver application is awaiting admin approval.';
        }

        const dot = document.getElementById('sidebar-status-dot');
        dot.className = 'driver-status-dot ' + sidebarStatus.toLowerCase();

        const badge = document.getElementById('sidebar-status-badge');
        badge.className = 'status-badge-custom ' + sidebarStatus.toLowerCase();
        document.getElementById('sidebar-status-text').textContent = displayStatus;

        statusLabel.className = labelClass;
        statusLabel.textContent = displayStatus;

        document.getElementById('action-status-desc').textContent = desc;
        refreshActionButtons(nextAccount, nextAvailability);

        showToast(`Driver status updated to ${displayStatus}!`, 'info');
    }

    // Modal Operations
    let changeAreasModal = null;
    function getChangeAreasModal() {
        if (!changeAreasModal) {
            changeAreasModal = new bootstrap.Modal(document.getElementById('changeAreasModal'));
        }
        return changeAreasModal;
    }
    
    function openChangeAreasModal() {
        getChangeAreasModal().show();
    }

    window.handleProfileAreaCheckboxChange = function(key) {
        const chk = document.getElementById(`chk-${key}`);
        const card = document.getElementById(`profile-area-card-${key}`);
        const wrapper = document.getElementById(`profile-primary-radio-wrapper-${key}`);
        const secBadge = document.getElementById(`profile-label-secondary-${key}`);
        const primBadge = document.getElementById(`profile-label-primary-${key}`);
        const radio = document.getElementById(`profile-primary-radio-${key}`);
        
        if (chk.checked) {
            if (card) {
                card.style.borderColor = '#ff7a00';
                card.style.backgroundColor = 'rgba(255, 122, 0, 0.01)';
            }
            
            // Check if any area is currently marked primary
            const checkedRadios = Array.from(document.querySelectorAll('.profile-primary-area-radio')).filter(r => r.checked);
            if (checkedRadios.length === 0) {
                if (radio) radio.checked = true;
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
            
            const wasPrimary = radio ? radio.checked : false;
            if (radio) radio.checked = false;
            
            if (wasPrimary) {
                // Find next checked checkbox and make it primary
                const checkedCheckboxes = Array.from(document.querySelectorAll('.profile-area-checkbox-modal:checked'));
                if (checkedCheckboxes.length > 0) {
                    const nextKey = checkedCheckboxes[0].value;
                    const nextRadio = document.getElementById(`profile-primary-radio-${nextKey}`);
                    if (nextRadio) {
                        nextRadio.checked = true;
                        
                        // Update visual status of new primary area
                        const nextWrapper = document.getElementById(`profile-primary-radio-wrapper-${nextKey}`);
                        const nextPrimBadge = document.getElementById(`profile-label-primary-${nextKey}`);
                        const nextSecBadge = document.getElementById(`profile-label-secondary-${nextKey}`);
                        if (nextWrapper) nextWrapper.style.display = 'none';
                        if (nextPrimBadge) nextPrimBadge.style.display = 'inline-block';
                        if (nextSecBadge) nextSecBadge.style.display = 'none';
                    }
                }
            }
        }
    };

    window.handleProfilePrimaryRadioChange = function(key) {
        const keys = ['pattom', 'kesavadasapuram', 'ulloor', 'murinjapalam', 'kowdiar', 'palayam', 'thampanoor', 'vellayambalam', 'statue', 'sasthamangalam', 'technopark', 'peroorkada', 'medical-college', 'kazhakkoottam', 'east-fort', 'vizhinjam', 'kovalam'];
        
        keys.forEach(k => {
            const chk = document.getElementById(`chk-${k}`);
            const wrapper = document.getElementById(`profile-primary-radio-wrapper-${k}`);
            const secBadge = document.getElementById(`profile-label-secondary-${k}`);
            const primBadge = document.getElementById(`profile-label-primary-${k}`);
            const radio = document.getElementById(`profile-primary-radio-${k}`);
            
            if (chk && chk.checked) {
                if (k === key) {
                    if (radio) radio.checked = true;
                    if (wrapper) wrapper.style.display = 'none';
                    if (primBadge) primBadge.style.display = 'inline-block';
                    if (secBadge) secBadge.style.display = 'none';
                } else {
                    if (radio) radio.checked = false;
                    if (wrapper) wrapper.style.display = 'block';
                    if (primBadge) primBadge.style.display = 'none';
                    if (secBadge) secBadge.style.display = 'inline-block';
                }
            } else {
                if (radio) radio.checked = false;
                if (wrapper) wrapper.style.display = 'none';
                if (primBadge) primBadge.style.display = 'none';
                if (secBadge) secBadge.style.display = 'none';
            }
        });
    };

    function saveAssignedAreas(e) {
        e.preventDefault();
        
        const keys = ['pattom', 'kesavadasapuram', 'ulloor', 'murinjapalam', 'kowdiar', 'palayam', 'thampanoor', 'vellayambalam', 'statue', 'sasthamangalam', 'technopark', 'peroorkada', 'medical-college', 'kazhakkoottam', 'east-fort', 'vizhinjam', 'kovalam'];
        
        keys.forEach(k => {
            const chkModal = document.getElementById(`chk-${k}`);
            const chkPage = document.getElementById(`area-${k}`);
            const radioModal = document.getElementById(`profile-primary-radio-${k}`);
            
            if (chkModal && chkPage) {
                chkPage.checked = chkModal.checked;
                
                const badge = chkPage.closest('.area-badge').querySelector('.badge, span.text-muted');
                if (badge) {
                    if (chkModal.checked && radioModal && radioModal.checked) {
                        badge.className = 'badge bg-label-primary px-2 py-1 rounded';
                        badge.style.fontSize = '0.72rem';
                        badge.style.backgroundColor = '';
                        badge.style.color = '';
                        badge.textContent = 'Primary';
                    } else if (chkModal.checked) {
                        badge.className = 'badge bg-label-secondary px-2 py-1 rounded';
                        badge.style.fontSize = '0.72rem';
                        badge.style.backgroundColor = 'rgba(133, 146, 163, 0.1)';
                        badge.style.color = '#8592a3';
                        badge.textContent = 'Secondary';
                    } else {
                        badge.className = 'text-muted';
                        badge.style.fontSize = '0.8rem';
                        badge.style.backgroundColor = 'transparent';
                        badge.style.color = '';
                        badge.textContent = '—';
                    }
                }
            }
        });
        
        getChangeAreasModal().hide();
        showToast('Assigned service areas modified successfully!');
    }

    // Delete Profile trigger in Settings Tab
    function triggerProfileDeletion() {
        Swal.fire({
            title: 'Delete Driver Permanently?',
            text: 'Are you absolutely sure? This action is irreversible and will delete all driver records.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete permanently!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger me-2 px-3 py-2',
                cancelButton: 'btn btn-outline-secondary px-3 py-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Driver profile has been deleted.', 'warning');
                setTimeout(() => {
                    window.location.href = "{{ $backUrl }}";
                }, 1000);
            }
        });
    }

    // Partner type is display-only on profile (change via Edit on drivers list).
    function selectPartnerType(type, silent = false) {}
</script>
@endsection
