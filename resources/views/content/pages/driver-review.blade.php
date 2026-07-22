@php
$isNavbar = false;
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Review Application: ' . $driver['name'])
@section('page-title', 'Review Application')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    /* Custom Styling for Driver Review Page */
    #service-area-map {
        height: 280px;
        width: 100%;
        border-radius: 8px;
        z-index: 1;
    }
    #service-area-map-wrap {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
    }
    #service-area-map-wrap .leaflet-control-attribution {
        font-size: 0.65rem;
    }
    .service-zone-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 1000;
        background: #fff;
        border: 1px solid #e0e2e7;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #23272e;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        pointer-events: none;
    }
    .service-zone-badge .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #ff7a00;
        flex-shrink: 0;
    }
    .service-map-live-badge {
        position: absolute;
        bottom: 12px;
        right: 12px;
        z-index: 1000;
        font-size: 0.68rem;
        letter-spacing: 0.5px;
        opacity: 0.9;
        border-radius: 4px;
        pointer-events: none;
    }
    .status-badge-custom {
        padding: 6px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-badge-custom.pending {
        background-color: #fffbeb !important;
        color: #b45309 !important;
    }
    .status-badge-custom.pending .dot {
        background-color: #d97706;
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .status-badge-custom.verified {
        background-color: #eff6ff !important;
        color: #1d4ed8 !important;
    }
    .status-badge-custom.approved {
        background-color: #ecfdf5 !important;
        color: #047857 !important;
    }
    .status-badge-custom.approved .dot {
        background-color: #059669;
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .status-badge-custom.suspended {
        background-color: #fef2f2 !important;
        color: #b91c1c !important;
    }
    .status-badge-custom.verified-kyc {
        background-color: #faf5ff !important;
        color: #7e22ce !important;
        border: 1px solid #f3e8ff;
    }

    .info-label {
        font-size: 0.78rem;
        color: #8592a3;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .info-value {
        font-size: 0.95rem;
        color: #23272e;
        font-weight: 600;
    }

    .edit-link {
        color: #ff7a00 !important;
        font-weight: 600;
        font-size: 0.88rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: opacity 0.15s;
    }
    .edit-link:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    /* Shift Preferences */
    .shift-card {
        border: 1px solid #e0e2e7;
        border-radius: 8px;
        padding: 14px 18px;
        flex: 1;
        background-color: #ffffff;
        transition: all 0.2s ease-in-out;
    }
    .shift-card.active {
        border-color: #ff7a00 !important;
        background-color: #fff8f2 !important;
    }

    /* Day representation */
    .day-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.82rem;
        margin-right: 6px;
    }
    .day-badge.active {
        background-color: #ff7a00;
        color: #ffffff;
    }
    .day-badge.inactive {
        background-color: #f1f5f9;
        color: #94a3b8;
    }

    /* Document cards layout */
    .doc-card {
        border: 1px solid #e0e2e7;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 12px;
        background-color: #ffffff;
        transition: all 0.15s;
    }
    .doc-card:hover {
        border-color: #cbd5e1;
    }
    .doc-icon-container {
        width: 42px;
        height: 42px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .doc-icon-container.orange {
        background-color: #fff7ed;
        color: #ea580c;
    }
    .doc-icon-container.blue {
        background-color: #eff6ff;
        color: #2563eb;
    }
    .doc-icon-container.purple {
        background-color: #faf5ff;
        color: #9333ea;
    }

    .doc-done-badge {
        background-color: #ecfdf5 !important;
        color: #059669 !important;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* Interactive Buttons */
    .btn-action-approve {
        background-color: #10b981 !important;
        border-color: #10b981 !important;
        color: #ffffff !important;
        border-radius: 8px;
        font-weight: 600;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-action-approve:hover {
        background-color: #059669 !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }

    .btn-action-request {
        background-color: #ffffff !important;
        border: 1px solid #f59e0b !important;
        color: #d97706 !important;
        border-radius: 8px;
        font-weight: 600;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-action-request:hover {
        background-color: #fffbeb !important;
    }

    .btn-action-reject {
        background-color: #ffffff !important;
        border: 1px solid #ef4444 !important;
        color: #dc2626 !important;
        border-radius: 8px;
        font-weight: 600;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-action-reject:hover {
        background-color: #fef2f2 !important;
    }

    .back-btn-review {
        color: #566a7f;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 20px;
        transition: all 0.15s;
    }
    .back-btn-review:hover {
        color: #ff7a00;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        100% { transform: scale(1.4); opacity: 0; }
    }
</style>

<!-- Back Navigation Link -->
<a href="{{ route('fleet-approvals') }}" class="back-btn-review">
    <i class="bx bx-left-arrow-alt" style="font-size: 1.3rem;"></i>
    <span>Back to Driver Registrations</span>
</a>

<div class="row">
    <!-- Left Column: Details Cards -->
    <div class="col-lg-8">
        
        <!-- Profile Header Card -->
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="avatar avatar-xl me-2" style="width: 72px; height: 72px;">
                        @php
                            $reviewAvatar = $driver['avatar'] ?? '1.png';
                            if (
                                ! str_starts_with($reviewAvatar, 'data:image')
                                && ! str_starts_with($reviewAvatar, 'http://')
                                && ! str_starts_with($reviewAvatar, 'https://')
                                && ! str_starts_with($reviewAvatar, '/')
                            ) {
                                $reviewAvatar = asset('assets/img/avatars/' . $reviewAvatar);
                            }
                        @endphp
                        <img src="{{ $reviewAvatar }}" alt="{{ $driver['name'] }}" class="rounded-circle" style="width: 72px; height: 72px; object-fit: cover;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="mb-0 fw-bold text-body" style="font-size: 1.45rem;">{{ $driver['name'] }}</h4>
                            
                            <!-- Status badge -->
                            <span class="status-badge-custom pending d-inline-flex" id="driver-status-badge">
                                <span class="dot"></span>
                                <span class="status-text">{{ $driver['status'] }}</span>
                            </span>
                            
                            <!-- Selfie verification tag -->
                            <span class="status-badge-custom verified-kyc d-inline-flex">
                                <i class="bx bx-camera" style="font-size: 0.95rem;"></i>
                                Selfie KYC Verified
                            </span>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.88rem; font-weight: 500;">
                            Application Date: {{ $driver['app_date'] ?? $driver['joined'] ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Info Card -->
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
            <div class="card-header border-bottom bg-transparent d-flex align-items-center justify-content-between py-3 px-4">
                <h5 class="mb-0 fw-bold text-body" style="font-size: 1.05rem;">Personal Information</h5>
                <a href="javascript:void(0);" class="edit-link" onclick="showToast('Edit mode opened for Personal Information', 'warning')">
                    <i class="bx bx-edit-alt" style="font-size: 1.05rem;"></i>
                    <span>Edit</span>
                </a>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Full Name</div>
                        <div class="info-value">{{ $driver['name'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Mobile Number</div>
                        <div class="info-value">{{ $driver['phone'] }}</div>
                    </div>
                    
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Email Address</div>
                        <div class="info-value">{{ $driver['email'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value">{{ $driver['dob'] }}</div>
                    </div>
                    
                    <div class="col-12">
                        <div class="info-label">Address / City</div>
                        <div class="info-value">{{ $driver['address'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Info Card -->
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
            <div class="card-header border-bottom bg-transparent d-flex align-items-center justify-content-between py-3 px-4">
                <h5 class="mb-0 fw-bold text-body" style="font-size: 1.05rem;">Vehicle Information</h5>
                <a href="javascript:void(0);" class="edit-link" onclick="showToast('Edit mode opened for Vehicle Information', 'warning')">
                    <i class="bx bx-edit-alt" style="font-size: 1.05rem;"></i>
                    <span>Edit</span>
                </a>
            </div>
            <div class="card-body p-4">
                <!-- Vehicle Verification summary block -->
                <div class="d-flex align-items-center gap-3 mb-4 p-3 border rounded" style="background-color: #f8fafc; border-radius: 8px;">
                    <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; color: #ff7a00;">
                        <i class="bx bx-cycling" style="font-size: 1.7rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold text-body" style="font-size: 0.95rem;">{{ $driver['vehicle_type'] }}</h6>
                        <div class="text-muted" style="font-size: 0.8rem; font-weight: 500;">{{ $driver['vehicle_brand'] }} {{ $driver['vehicle_model'] }}</div>
                    </div>
                    <div>
                        <span class="badge text-success fw-semibold py-1 px-3 d-flex align-items-center gap-1" style="background-color: #ecfdf5 !important; border-radius: 20px; font-size: 0.8rem;">
                            <i class="bx bx-check-circle" style="font-size: 0.95rem;"></i>
                            Verified
                        </span>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Vehicle No.</div>
                        <div class="info-value">{{ $driver['plate_number'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Brand</div>
                        <div class="info-value">{{ $driver['vehicle_brand'] }}</div>
                    </div>
                    
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Model</div>
                        <div class="info-value">{{ $driver['vehicle_model'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Type</div>
                        <div class="info-value text-capitalize">{{ $driver['vehicle_type'] }}</div>
                    </div>
                    
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Fuel Classification</div>
                        <div class="info-value">{{ $driver['vehicle_fuel'] }}</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Driving License</div>
                        <div class="info-value">{{ $driver['license_number'] }}</div>
                    </div>
                    
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Shift Timing</div>
                        <div class="info-value">{{ $driver['shift'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Area Preferences Card -->
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
            <div class="card-header border-bottom bg-transparent d-flex align-items-center justify-content-between py-3 px-4">
                <h5 class="mb-0 fw-bold text-body" style="font-size: 1.05rem;">Requested Service Area & Preferences</h5>
                <a href="javascript:void(0);" class="edit-link" onclick="showToast('Edit mode opened for Service Area & Preferences', 'warning')">
                    <i class="bx bx-edit-alt" style="font-size: 1.05rem;"></i>
                    <span>Edit</span>
                </a>
            </div>
            <div class="card-body p-4">
                <!-- Primary zone map (Leaflet, same style as Live Map) -->
                @php
                    $mapPrimaryZone = $driver['primary_zone'] ?? $driver['zone'] ?? 'Primary Zone';
                    $mapServiceArea = $driver['service_area'] ?? $driver['zone'] ?? $mapPrimaryZone;
                    $mapServiceRadius = $driver['service_radius'] ?? '10 km';
                @endphp
                <div id="service-area-map-wrap">
                    <div id="service-area-map"
                         data-zone="{{ $mapPrimaryZone }}"
                         data-service-area="{{ $mapServiceArea }}"
                         data-radius="{{ $mapServiceRadius }}"></div>
                    <div class="service-zone-badge">
                        <span class="dot"></span>
                        <span id="map-zone-text">{{ $mapPrimaryZone }}</span>
                    </div>
                    <span class="badge bg-dark text-white fw-semibold px-2 py-1 service-map-live-badge">PRIMARY ZONE</span>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Base Location</div>
                        <div class="info-value">{{ $driver['service_area'] ?? $driver['zone'] ?? 'N/A' }}</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Service Radius</div>
                        <div class="info-value d-flex align-items-center gap-1">
                            <span>{{ $driver['service_radius'] ?? '10 km' }}</span>
                            <i class="bx bx-transfer-alt text-muted" style="font-size: 0.95rem;"></i>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Primary Zone</div>
                        <div class="info-value">{{ $driver['primary_zone'] ?? $driver['zone'] ?? 'N/A' }}</div>
                    </div>
                    <div class="col-sm-6 col-md-6">
                        <div class="info-label">Coverage Areas</div>
                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap" id="coverage-container">
                            @foreach(($driver['coverage_areas'] ?? $driver['service_areas'] ?? []) as $area)
                            <span class="badge bg-light text-body border py-1 px-2" style="font-size: 0.75rem; font-weight: 600; border-radius: 4px; text-transform: capitalize;">{{ $area }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <hr class="my-4" style="border-color: #e2e8f0;">

                <!-- Preferred Shifts -->
                <div class="mb-4">
                    <div class="info-label mb-3" style="display: flex; align-items: center; gap: 6px;">
                        <i class="bx bx-time-five" style="font-size: 1rem;"></i>
                        <span>Preferred Shifts</span>
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        @php
                        $isMorning = strpos($driver['shift'] ?? '', 'Morning') !== false;
                        $isAfternoon = strpos($driver['shift'] ?? '', 'Afternoon') !== false || strpos($driver['shift'] ?? '', 'Evening') !== false || strpos($driver['shift'] ?? '', 'Full-time') !== false;
                        @endphp
                        <div class="shift-card {{ $isMorning ? 'active' : '' }}" id="shift-morning">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bx bx-sun" style="font-size: 1.15rem; color: {{ $isMorning ? '#ff7a00' : '#8592a3' }};"></i>
                                <span class="fw-bold text-body" style="font-size: 0.88rem;">Morning</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.78rem; font-weight: 500;">6:00 AM - 12:00 PM</div>
                        </div>
                        <div class="shift-card {{ $isAfternoon ? 'active' : '' }}" id="shift-afternoon">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bx bx-cloud" style="font-size: 1.15rem; color: {{ $isAfternoon ? '#ff7a00' : '#8592a3' }};"></i>
                                <span class="fw-bold text-body" style="font-size: 0.88rem;">Afternoon</span>
                            </div>
                            <div class="text-muted" style="font-size: 0.78rem; font-weight: 500;">12:00 PM - 6:00 PM</div>
                        </div>
                    </div>
                </div>

                <!-- Working Days -->
                <div>
                    <div class="info-label mb-2">Working Days</div>
                    <div class="d-flex mt-1" id="days-container">
                        @foreach(['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $index => $day)
                            @php
                            $isActive = false;
                            if (isset($driver['working_days'])) {
                                $isActive = ($index < count($driver['working_days']));
                            } else {
                                $isActive = ($index < 5); // default M-F active
                            }
                            @endphp
                            <span class="day-badge {{ $isActive ? 'active' : 'inactive' }}">{{ $day }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Card -->
        @php
            $docsTotal = 7;
            $docsDone = 5; // Vehicle RC & Insurance pending (mock)
        @endphp
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
            <div class="card-header border-bottom bg-transparent d-flex align-items-center justify-content-between py-3 px-4">
                <h5 class="mb-0 fw-bold text-body" style="font-size: 1.05rem;">Documents</h5>
                <span class="badge bg-warning-light text-warning fw-bold py-1 px-3" style="background-color: #fffbeb !important; border-radius: 20px; font-size: 0.8rem; border: 1px solid #fef3c7;">{{ $docsDone }}/{{ $docsTotal }} Done</span>
            </div>
            <div class="card-body p-4 pb-2">
                <div class="row">
                    <!-- Aadhaar Front -->
                    <div class="col-md-6">
                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-container orange">
                                    <i class="bx bx-card" style="font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-body" style="font-size: 0.88rem;">Aadhaar Front</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Name, DOB & Address</div>
                                </div>
                            </div>
                            <span class="doc-done-badge">
                                <i class="bx bx-check" style="font-size: 0.95rem;"></i>
                                Done
                            </span>
                        </div>
                    </div>

                    <!-- Aadhaar Back -->
                    <div class="col-md-6">
                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-container orange">
                                    <i class="bx bx-card" style="font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-body" style="font-size: 0.88rem;">Aadhaar Back</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Aadhaar Number & QR</div>
                                </div>
                            </div>
                            <span class="doc-done-badge">
                                <i class="bx bx-check" style="font-size: 0.95rem;"></i>
                                Done
                            </span>
                        </div>
                    </div>

                    <!-- Driving License Front -->
                    <div class="col-md-6">
                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-container blue">
                                    <i class="bx bx-id-card" style="font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-body" style="font-size: 0.88rem;">Driving License Front</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">License No. & Photo</div>
                                </div>
                            </div>
                            <span class="doc-done-badge">
                                <i class="bx bx-check" style="font-size: 0.95rem;"></i>
                                Done
                            </span>
                        </div>
                    </div>

                    <!-- Driving License Back -->
                    <div class="col-md-6">
                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-container blue">
                                    <i class="bx bx-id-card" style="font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-body" style="font-size: 0.88rem;">Driving License Back</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Vehicle Categories</div>
                                </div>
                            </div>
                            <span class="doc-done-badge">
                                <i class="bx bx-check" style="font-size: 0.95rem;"></i>
                                Done
                            </span>
                        </div>
                    </div>

                    <!-- PAN Card -->
                    <div class="col-md-6">
                        <div class="doc-card d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-container purple">
                                    <i class="bx bx-credit-card-front" style="font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-body" style="font-size: 0.88rem;">PAN Card</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Income Tax Department</div>
                                </div>
                            </div>
                            <span class="doc-done-badge">
                                <i class="bx bx-check" style="font-size: 0.95rem;"></i>
                                Done
                            </span>
                        </div>
                    </div>

                    <!-- Pending Vehicle RC -->
                    <div class="col-md-6">
                        <div class="doc-card d-flex align-items-center justify-content-between" style="border-style: dashed; background-color: #fafafa;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-container text-muted bg-light">
                                    <i class="bx bx-plus" style="font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-muted" style="font-size: 0.88rem;">Vehicle RC</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Not uploaded yet</div>
                                </div>
                            </div>
                            <span class="badge bg-label-secondary fw-semibold py-1 px-2" style="font-size: 0.78rem; border-radius: 4px;">Pending</span>
                        </div>
                    </div>

                    <!-- Pending Vehicle Insurance -->
                    <div class="col-md-6">
                        <div class="doc-card d-flex align-items-center justify-content-between" style="border-style: dashed; background-color: #fafafa;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="doc-icon-container text-muted bg-light">
                                    <i class="bx bx-plus" style="font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-muted" style="font-size: 0.88rem;">Vehicle Insurance</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Not uploaded yet</div>
                                </div>
                            </div>
                            <span class="badge bg-label-secondary fw-semibold py-1 px-2" style="font-size: 0.78rem; border-radius: 4px;">Pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Interactive Panel -->
    <div class="col-lg-4">
        
        <!-- Application Actions Panel -->
        <div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff; position: sticky; top: 20px;">
            <div class="card-body p-4">
                <h5 class="mb-4 fw-bold text-body" style="font-size: 1.2rem;">Application Actions</h5>
                
                <!-- Service Area Assignment -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted mb-2" style="font-size: 0.8rem; text-transform: uppercase;">Assign Service Area</label>
                    <select class="form-select" id="assigned-area-select" style="height: 44px; border-radius: 8px; border-color: #cbd5e1; font-weight: 500; font-size: 0.88rem;">
                        <option value="Pattom" selected>Pattom (Requested)</option>
                        <option value="Kesavadasapuram">Kesavadasapuram</option>
                        <option value="Kowdiar">Kowdiar</option>
                        <option value="Palayam">Palayam</option>
                        <option value="Technopark">Technopark</option>
                        <option value="Medical College">Medical College</option>
                        <option value="East Fort">East Fort</option>
                        <option value="Vizhinjam">Vizhinjam</option>
                    </select>
                </div>
                
                <!-- Remarks Notes -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted mb-2" style="font-size: 0.8rem; text-transform: uppercase;">Internal Remarks</label>
                    <textarea class="form-control" id="internal-remarks-text" rows="4" placeholder="Add notes about this application..." style="border-radius: 8px; border-color: #cbd5e1; font-size: 0.88rem; resize: none;"></textarea>
                </div>
                
                <!-- Decision Buttons -->
                <div class="d-flex flex-column gap-3">
                    <!-- Approve button -->
                    <button type="button" class="btn btn-action-approve w-100" id="btn-approve" onclick="processApplication('approve')">
                        <i class="bx bx-check" style="font-size: 1.25rem;"></i>
                        <span>Approve Application</span>
                    </button>
                    
                    <!-- Request Documents -->
                    <button type="button" class="btn btn-action-request w-100" id="btn-request" onclick="processApplication('request')">
                        <i class="bx bx-file" style="font-size: 1.15rem;"></i>
                        <span>Request Documents</span>
                    </button>
                    
                    <!-- Reject button -->
                    <button type="button" class="btn btn-action-reject w-100" id="btn-reject" onclick="processApplication('reject')">
                        <i class="bx bx-x" style="font-size: 1.25rem;"></i>
                        <span>Reject Application</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Toast Feedback -->
<div class="bs-toast toast toast-placement-ex m-2 fade bg-success top-0 end-0" id="success-toast" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; z-index: 1090;">
    <div class="toast-header">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Action Successful</div>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toast-message">
        Application has been approved successfully.
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const driverId = "{{ $driverId }}";

    // --- Primary Zone Map (same Leaflet setup as Live Map) ---
    (function initServiceAreaMap() {
        const mapEl = document.getElementById('service-area-map');
        if (!mapEl || typeof L === 'undefined') return;

        const zoneName = mapEl.dataset.zone || 'Primary Zone';
        const serviceArea = mapEl.dataset.serviceArea || zoneName;
        const radiusLabel = mapEl.dataset.radius || '10 km';
        const radiusKm = parseFloat(String(radiusLabel).replace(/[^\d.]/g, '')) || 10;

        // Zone polygons keyed by keyword (Trivandrum localities around 8.52, 76.94)
        const zoneCatalog = {
            pattom: {
                center: [8.5241, 76.9366],
                zoom: 14,
                coords: [
                    [8.5320, 76.9280],
                    [8.5320, 76.9450],
                    [8.5160, 76.9450],
                    [8.5160, 76.9280]
                ]
            },
            kesavadasapuram: {
                center: [8.5360, 76.9360],
                zoom: 14,
                coords: [
                    [8.5440, 76.9280],
                    [8.5440, 76.9440],
                    [8.5280, 76.9440],
                    [8.5280, 76.9280]
                ]
            },
            kowdiar: {
                center: [8.5089, 76.9652],
                zoom: 14,
                coords: [
                    [8.5160, 76.9570],
                    [8.5160, 76.9730],
                    [8.5020, 76.9730],
                    [8.5020, 76.9570]
                ]
            },
            palayam: {
                center: [8.5065, 76.9540],
                zoom: 14,
                coords: [
                    [8.5140, 76.9460],
                    [8.5140, 76.9620],
                    [8.4990, 76.9620],
                    [8.4990, 76.9460]
                ]
            },
            technopark: {
                center: [8.5580, 76.8810],
                zoom: 13,
                coords: [
                    [8.5700, 76.8680],
                    [8.5700, 76.8940],
                    [8.5460, 76.8940],
                    [8.5460, 76.8680]
                ]
            },
            'medical-college': {
                center: [8.5235, 76.9280],
                zoom: 14,
                coords: [
                    [8.5310, 76.9200],
                    [8.5310, 76.9360],
                    [8.5160, 76.9360],
                    [8.5160, 76.9200]
                ]
            },
            'east-fort': {
                center: [8.4830, 76.9475],
                zoom: 14,
                coords: [
                    [8.4910, 76.9390],
                    [8.4910, 76.9560],
                    [8.4750, 76.9560],
                    [8.4750, 76.9390]
                ]
            },
            vizhinjam: {
                center: [8.3780, 76.9910],
                zoom: 13,
                coords: [
                    [8.3900, 76.9780],
                    [8.3900, 77.0040],
                    [8.3660, 77.0040],
                    [8.3660, 76.9780]
                ]
            }
        };

        function resolveZone(name) {
            const key = String(name || '').toLowerCase();
            if (key.includes('kesavadasapuram')) return zoneCatalog.kesavadasapuram;
            if (key.includes('pattom') || key.includes('ulloor') || key.includes('murinjapalam')) return zoneCatalog.pattom;
            if (key.includes('kowdiar') || key.includes('vellayambalam') || key.includes('statue')) return zoneCatalog.kowdiar;
            if (key.includes('palayam') || key.includes('thampanoor')) return zoneCatalog.palayam;
            if (key.includes('technopark') || key.includes('sasthamangalam') || key.includes('peroorkada')) return zoneCatalog.technopark;
            if (key.includes('medical') || key.includes('kazhakkoottam')) return zoneCatalog['medical-college'];
            if (key.includes('east fort') || key.includes('east-fort') || key.includes('chalai')) return zoneCatalog['east-fort'];
            if (key.includes('vizhinjam') || key.includes('kovalam')) return zoneCatalog.vizhinjam;
            return zoneCatalog.pattom;
        }

        const zone = resolveZone(zoneName) || resolveZone(serviceArea);
        const map = L.map('service-area-map', {
            zoomControl: false,
            attributionControl: true
        }).setView(zone.center, zone.zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        const deliveryZone = L.polygon(zone.coords, {
            color: '#ff3e1d',
            fillColor: '#ff3e1d',
            fillOpacity: 0.08,
            weight: 2,
            dashArray: '6, 6'
        }).addTo(map);

        deliveryZone.bindPopup(
            '<strong>' + zoneName + '</strong><br>Primary delivery zone<br>Service radius: ' + radiusLabel
        );

        // Soft service-radius ring around zone center
        L.circle(zone.center, {
            radius: radiusKm * 1000,
            color: '#ff7a00',
            fillColor: '#ff7a00',
            fillOpacity: 0.04,
            weight: 1.5,
            dashArray: '4, 6'
        }).addTo(map);

        const pinIcon = L.divIcon({
            className: '',
            html: '<div style="display:flex;flex-direction:column;align-items:center;">' +
                  '<i class="bx bxs-map-pin" style="color:#ff7a00;font-size:28px;line-height:1;filter:drop-shadow(0 2px 3px rgba(0,0,0,.25));"></i>' +
                  '</div>',
            iconSize: [28, 28],
            iconAnchor: [14, 28]
        });
        L.marker(zone.center, { icon: pinIcon })
            .addTo(map)
            .bindPopup('<strong>' + zoneName + '</strong><br>' + serviceArea);

        // Fit to zone polygon so the primary area is fully visible
        setTimeout(function () {
            map.invalidateSize();
            map.fitBounds(deliveryZone.getBounds(), { padding: [24, 24] });
        }, 150);
    })();
    
    // Driver data from database (passed by controller)
    const serverDriver = @json($driver);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const statusUrl = @json(url('/fleet/approvals/' . $driverId . '/status'));
    
    // Toast setup
    const toastEl = document.getElementById('success-toast');
    const toastMsg = document.getElementById('toast-message');
    const toast = new bootstrap.Toast(toastEl);
    
    function showToast(message, type = 'success') {
        toastMsg.innerText = message;
        toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');
        if (type === 'success') {
            toastEl.classList.add('bg-success');
        } else if (type === 'danger') {
            toastEl.classList.add('bg-danger');
        } else {
            toastEl.classList.add('bg-warning');
        }
        toast.show();
    }
    
    // Bind to window for general access
    window.showToast = showToast;

    function syncStatusBadge(status) {
        const badge = document.getElementById('driver-status-badge');
        if (!badge) return;
        const text = badge.querySelector('.status-text');
        const normalized = status === 'Pending' ? 'Pending Review' : (status === 'Active' ? 'Active' : status);
        if (text) text.innerText = normalized;

        badge.className = 'status-badge-custom d-inline-flex';
        if (status === 'Pending') badge.classList.add('pending');
        else if (status === 'Active') badge.classList.add('approved');
        else if (status === 'Rejected') badge.classList.add('suspended');
        else if (status === 'Suspended') badge.classList.add('suspended');
    }

    if (serverDriver) {
        syncStatusBadge(serverDriver.status || 'Pending');

        const remarksKey = `remarks_${driverId}`;
        if (localStorage.getItem(remarksKey)) {
            document.getElementById('internal-remarks-text').value = localStorage.getItem(remarksKey);
        }

        const shiftMorning = document.getElementById('shift-morning');
        const shiftAfternoon = document.getElementById('shift-afternoon');
        const shift = serverDriver.shift || '';
        if (shiftMorning && shiftAfternoon) {
            if (shift.toLowerCase().includes('morning')) {
                shiftMorning.classList.add('active');
                shiftAfternoon.classList.remove('active');
            } else if (shift.toLowerCase().includes('evening') || shift.toLowerCase().includes('afternoon')) {
                shiftAfternoon.classList.add('active');
                shiftMorning.classList.remove('active');
            }
        }

        const daysContainer = document.getElementById('days-container');
        if (daysContainer && Array.isArray(serverDriver.working_days)) {
            const labels = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
            const dayKeys = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            daysContainer.innerHTML = dayKeys.map((key, index) => {
                const isActive = serverDriver.working_days.includes(key);
                return `<span class="day-badge ${isActive ? 'active' : 'inactive'}">${labels[index]}</span>`;
            }).join('');
        }

        const coverageContainer = document.getElementById('coverage-container');
        const coverageAreas = serverDriver.service_areas || [];
        if (coverageContainer && Array.isArray(coverageAreas) && coverageAreas.length) {
            coverageContainer.innerHTML = coverageAreas.map((area) =>
                `<span class="badge bg-light text-body border py-1 px-2" style="font-size: 0.75rem; font-weight: 600; border-radius: 4px;">${area}</span>`
            ).join('');
        }

        const select = document.getElementById('assigned-area-select');
        if (select) {
            const areaName = serverDriver.zone || serverDriver.store || '';
            for (let option of select.options) {
                if (option.value === areaName || option.text.includes(areaName)) {
                    option.selected = true;
                    break;
                }
            }
        }
    }
    
    // Save remarks when typed (local note only for now)
    document.getElementById('internal-remarks-text').addEventListener('input', function(e) {
        if (driverId) {
            localStorage.setItem(`remarks_${driverId}`, e.target.value);
        }
    });
    
    // Decision processors — persist to database
    window.processApplication = async function(action) {
        if (!driverId) {
            showToast('Driver data not initialized.', 'danger');
            return;
        }

        if (action === 'request') {
            showToast("Document request has been sent to the driver's mobile app.", 'warning');
            return;
        }

        const status = action === 'approve' ? 'Active' : 'Rejected';
        const approveBtn = document.getElementById('btn-approve');
        const rejectBtn = document.getElementById('btn-reject');
        if (approveBtn) approveBtn.disabled = true;
        if (rejectBtn) rejectBtn.disabled = true;

        try {
            const response = await fetch(statusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Unable to update application status.');
            }

            syncStatusBadge(status);
            const name = serverDriver?.name || 'Driver';
            if (action === 'approve') {
                showToast(`${name} approved successfully! Redirecting...`, 'success');
            } else {
                showToast(`${name} application rejected/suspended. Redirecting...`, 'danger');
            }

            setTimeout(() => {
                window.location.href = "{{ route('fleet-approvals') }}";
            }, 1000);
        } catch (error) {
            showToast(error.message || 'Unable to update application status.', 'danger');
            if (approveBtn) approveBtn.disabled = false;
            if (rejectBtn) rejectBtn.disabled = false;
        }
    };
});
</script>
@endsection
