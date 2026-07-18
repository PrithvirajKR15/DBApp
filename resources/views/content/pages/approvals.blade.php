@php
$isNavbar = false;
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Driver Registrations')
@section('page-title', 'Driver Registrations')

@section('content')
<style>
    /* Styling to match the Figma design */
    .bell-notification-btn {
        position: relative;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        width: 38px;
        height: 38px;
        background-color: #ffffff;
        border: 1px solid #e0e2e7;
        color: #566a7f;
        transition: all 0.2s ease-in-out;
    }
    .bell-notification-btn:hover {
        background-color: #f8f9fa;
        border-color: #d1d3e2;
        color: #ff7a00;
    }

    /* Custom status badges matching figma design */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 20px;
        line-height: 1.2;
    }

    .status-badge.pending {
        background-color: #fffbeb !important; /* light yellow */
        color: #b45309 !important; /* dark yellow */
    }
    .status-badge.pending .dot {
        background-color: #d97706; /* solid orange-yellow */
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .status-badge.verified {
        background-color: #eff6ff !important; /* light blue */
        color: #1d4ed8 !important; /* dark blue */
    }
    .status-badge.verified .icon {
        color: #2563eb;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .status-badge.approved {
        background-color: #ecfdf5 !important; /* light green */
        color: #047857 !important; /* dark green */
    }
    .status-badge.approved .dot {
        background-color: #059669;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .status-badge.suspended {
        background-color: #fef2f2 !important; /* light red */
        color: #b91c1c !important; /* dark red */
    }
    .status-badge.suspended .icon {
        color: #dc2626;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .status-subtext {
        font-size: 0.75rem;
        color: #8592a3;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
        font-weight: 500;
    }
    .status-subtext i {
        font-size: 0.85rem;
    }

    /* Service area badges */
    .area-badge-pill {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
        padding: 5px 10px !important;
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        border-radius: 6px !important;
        border: none;
        letter-spacing: 0.2px;
    }

    /* Row Hover styles and general */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.015) !important;
    }

    .table-hover tbody tr {
        transition: background-color 0.15s ease-in-out;
    }

    /* Checkbox alignment */
    .form-check-input {
        cursor: pointer;
    }
    .form-check-input:checked {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
    }

    /* Ellipsis Actions button styling */
    .btn-action-trigger {
        background: none;
        border: none;
        color: #8592a3;
        padding: 6px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-action-trigger:hover {
        background-color: #f1f5f9;
        color: #566a7f;
    }

    /* Pagination design matching screenshot */
    .pagination .page-item .page-link {
        color: #566a7f;
        border-radius: 6px;
        padding: 6px 12px;
        font-weight: 500;
        border: 1px solid #e0e2e7;
        margin: 0 2px;
        background-color: #ffffff;
        transition: all 0.15s;
    }
    .pagination .page-item.active .page-link {
        background-color: transparent !important;
        border-color: #ff7a00 !important;
        color: #ff7a00 !important;
        font-weight: 600;
        box-shadow: none;
    }
    .pagination .page-item .page-link:hover {
        background-color: #f8f9fa;
        border-color: #d1d3e2;
        color: #ff7a00;
    }
    .pagination .page-item.disabled .page-link {
        background-color: #f8f9fa;
        border-color: #e0e2e7;
        color: #b2c0cd;
    }

    /* Custom filter button style */
    .filter-dropdown-btn {
        transition: all 0.15s ease-in-out;
    }
    .filter-dropdown-btn:hover {
        background-color: #f8f9fa !important;
        border-color: #cbd5e1 !important;
    }
</style>

<!-- Page Header Section -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
        <h3 class="mb-0 fw-bold text-body" style="font-size: 1.6rem; font-family: 'Public Sans', sans-serif;">Driver Registrations</h3>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- Search bar -->
        <div class="input-group input-group-merge border rounded overflow-hidden" style="width: 300px; border-color: #e0e2e7 !important; border-radius: 8px !important; background-color: #ffffff;">
            <span class="input-group-text border-0 bg-transparent ps-3"><i class="bx bx-search text-muted" style="font-size: 1.1rem;"></i></span>
            <input type="text" class="form-control border-0 bg-transparent ps-1" placeholder="Search drivers by name, ID..." id="search-driver-input" style="box-shadow: none; font-size: 0.88rem; height: 38px;">
        </div>

        <!-- Notification Bell -->
        <div class="bell-notification-btn" id="bell-btn">
            <i class="bx bx-bell" style="font-size: 1.25rem;"></i>
        </div>
        
        <!-- Export Button -->
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2 border bg-white" id="export-btn" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 16px; font-size: 0.88rem; height: 38px; color: #566a7f;">
            <i class="bx bx-download" style="font-size: 1.1rem;"></i>
            <span>Export</span>
        </button>
    </div>
</div>

<!-- Filters and Actions Toolbar -->
<div class="card shadow-none border mb-4" style="border-radius: 12px; background-color: #ffffff;">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 flex-wrap">
            <!-- Left Side Filters -->
            <div class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap">
                <!-- All Service Areas Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center border bg-white filter-dropdown-btn" type="button" id="areaFilterBtn" data-bs-toggle="dropdown" aria-expanded="false" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 14px; font-size: 0.88rem; height: 38px; color: #566a7f; min-width: 170px; justify-content: space-between;">
                        <span id="selected-area-label">All Service Areas</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="areaFilterBtn">
                        <li><a class="dropdown-item active" href="javascript:void(0);" data-value="all" onclick="setFilter('area', 'all', 'All Service Areas')">All Service Areas</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="Downtown Zone" onclick="setFilter('area', 'Downtown Zone', 'Downtown Zone')">Downtown Zone</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="Northwest District" onclick="setFilter('area', 'Northwest District', 'Northwest District')">Northwest District</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="Southeast Hub" onclick="setFilter('area', 'Southeast Hub', 'Southeast Hub')">Southeast Hub</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="Uptown Area" onclick="setFilter('area', 'Uptown Area', 'Uptown Area')">Uptown Area</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="East Side" onclick="setFilter('area', 'East Side', 'East Side')">East Side</a></li>
                    </ul>
                </div>

                <!-- Pending Review (12) Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center border bg-white filter-dropdown-btn" type="button" id="statusFilterBtn" data-bs-toggle="dropdown" aria-expanded="false" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 14px; font-size: 0.88rem; height: 38px; color: #566a7f; min-width: 190px; justify-content: space-between;">
                        <span id="selected-status-label">Pending Review (12)</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="statusFilterBtn">
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="all" onclick="setFilter('status', 'all', 'All Statuses')">All Statuses</a></li>
                        <li><a class="dropdown-item active" href="javascript:void(0);" data-value="Pending" onclick="setFilter('status', 'Pending', 'Pending Review (12)')">Pending Review</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="Approved" onclick="setFilter('status', 'Approved', 'Approved')">Approved</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="Suspended" onclick="setFilter('status', 'Suspended', 'Suspended')">Suspended</a></li>
                    </ul>
                </div>

                <!-- Last 7 Days Button -->
                <button class="btn btn-outline-secondary d-flex align-items-center gap-2 border bg-white" type="button" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 14px; font-size: 0.88rem; height: 38px; color: #566a7f;">
                    <i class="bx bx-calendar text-muted" style="font-size: 1.15rem;"></i>
                    <span>Last 7 Days</span>
                </button>
            </div>

            <!-- Right Side Bulk Actions -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted fw-semibold me-1" style="font-size: 0.85rem; white-space: nowrap;">Bulk Actions:</span>
                <button type="button" class="btn btn-success d-flex align-items-center justify-content-center fw-semibold border-0" id="bulk-approve-btn" onclick="bulkAction('approve')" style="background-color: #10b981 !important; color: #ffffff !important; border-radius: 8px; padding: 8px 16px; font-size: 0.88rem; height: 38px; transition: all 0.2s; opacity: 0.65;" disabled>
                    Approve Selected
                </button>
                <button type="button" class="btn btn-danger d-flex align-items-center justify-content-center fw-semibold border-0" id="bulk-reject-btn" onclick="bulkAction('reject')" style="background-color: #ef4444 !important; color: #ffffff !important; border-radius: 8px; padding: 8px 16px; font-size: 0.88rem; height: 38px; transition: all 0.2s; opacity: 0.65;" disabled>
                    Reject Selected
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card shadow-none border" style="border-radius: 12px; background-color: #ffffff; overflow: hidden;">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover mb-0" id="approvals-table" style="vertical-align: middle;">
            <thead>
                <tr class="table-light border-bottom" style="background-color: #f8fafc;">
                    <th style="width: 40px; padding: 16px 20px;">
                        <div class="form-check m-0">
                            <input class="form-check-input select-all-checkbox" type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)">
                        </div>
                    </th>
                    <th class="fw-bold text-muted py-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">DRIVER DETAILS</th>
                    <th class="fw-bold text-muted py-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">PARTNER & VEHICLE</th>
                    <th class="fw-bold text-muted py-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">SERVICE AREA</th>
                    <th class="fw-bold text-muted py-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">REGISTRATION DATE</th>
                    <th class="fw-bold text-muted py-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">STATUS</th>
                    <th class="fw-bold text-muted py-3 text-end pe-4" style="font-size: 0.8rem; letter-spacing: 0.5px; width: 80px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody id="approvals-tbody" class="table-border-bottom-0">
                <!-- Dynamic rows here -->
            </tbody>
        </table>
    </div>

    <!-- Blank State -->
    <div id="blank-state" class="text-center p-5 d-none">
        <i class="bx bx-check-shield text-success display-1 mb-3"></i>
        <h4 class="fw-bold">All caught up!</h4>
        <p class="text-muted">No pending registrations match your criteria.</p>
    </div>

    <!-- Card Footer for Pagination -->
    <div class="card-footer border-top bg-white d-flex align-items-center justify-content-between py-3 px-4">
        <div class="text-muted" style="font-size: 0.88rem;" id="pagination-info">
            Showing <span class="fw-semibold text-body" id="showing-start">1</span> to <span class="fw-semibold text-body" id="showing-end">10</span> of <span class="fw-semibold text-body" id="showing-total">42</span> entries
        </div>
        <nav aria-label="Page navigation" class="m-0">
            <ul class="pagination pagination-sm mb-0 justify-content-end gap-1" id="pagination-controls">
                <!-- Dynamic pagination buttons -->
            </ul>
        </nav>
    </div>
</div>

<!-- Success/Toast Feedback -->
<div class="bs-toast toast toast-placement-ex m-2 fade bg-success top-0 end-0" id="success-toast" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; z-index: 1090;">
    <div class="toast-header">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Action Successful</div>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toast-message">
        Driver application processed successfully.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Exact drivers from figma screenshot (first 4)
    const firstFour = [
      {
        id: 'RE6-7821',
        name: 'James Wilson',
        phone: '+1 (555) 234-5678',
        avatar: '5.png',
        partnerType: 'Independent',
        vehicle: 'Motorcycle (Honda)',
        serviceArea: 'Downtown Zone',
        date: 'Oct 24, 2023',
        time: '09:41 AM',
        status: 'Pending Review',
        subtext: 'Awaiting docs',
        email: 'james.wilson@deliverease.com'
      },
      {
        id: 'RE6-7820',
        name: 'Sarah Martinez',
        phone: '+1 (555) 987-6543',
        avatar: '4.png',
        partnerType: 'Independent',
        vehicle: 'Electric Scooter',
        serviceArea: 'Northwest District',
        date: 'Oct 23, 2023',
        time: '14:22 PM',
        status: 'Docs Verified',
        subtext: 'Ready for approval',
        email: 'sarah.martinez@deliverease.com'
      },
      {
        id: 'DRV-1042',
        name: 'Robert Chen',
        phone: '+1 (555) 456-7890',
        avatar: '3.png',
        partnerType: 'Independent',
        vehicle: 'Car (Toyota)',
        serviceArea: 'Southeast Hub',
        date: 'Oct 22, 2023',
        time: '11:05 AM',
        status: 'Approved',
        subtext: '',
        email: 'robert.chen@deliverease.com'
      },
      {
        id: 'DRV-0988',
        name: 'Michael Chang',
        phone: '+1 (555) 112-2334',
        avatar: '7.png',
        partnerType: 'Independent',
        vehicle: 'Motorcycle',
        serviceArea: 'Downtown Zone',
        date: 'Oct 15, 2023',
        time: '08:30 AM',
        status: 'Suspended',
        subtext: '',
        email: 'michael.chang@deliverease.com'
      }
    ];

    // Extra pending/verified rows to sum up 12 pending review items
    const extraPending = [
      {
        id: 'DRV-1043',
        name: 'Emily Davis',
        phone: '+1 (555) 321-7654',
        avatar: '2.png',
        partnerType: 'Independent',
        vehicle: 'Electric Scooter',
        serviceArea: 'Northwest District',
        date: 'Oct 24, 2023',
        time: '10:15 AM',
        status: 'Pending Review',
        subtext: 'Awaiting docs',
        email: 'emily.davis@deliverease.com'
      },
      {
        id: 'DRV-1044',
        name: 'William Taylor',
        phone: '+1 (555) 876-5432',
        avatar: '1.png',
        partnerType: 'Independent',
        vehicle: 'Motorcycle (Suzuki)',
        serviceArea: 'Downtown Zone',
        date: 'Oct 23, 2023',
        time: '11:30 AM',
        status: 'Docs Verified',
        subtext: 'Ready for approval',
        email: 'william.taylor@deliverease.com'
      },
      {
        id: 'DRV-1045',
        name: 'Olivia Brown',
        phone: '+1 (555) 234-5679',
        avatar: '6.png',
        partnerType: 'Independent',
        vehicle: 'Electric Scooter',
        serviceArea: 'Southeast Hub',
        date: 'Oct 22, 2023',
        time: '09:15 AM',
        status: 'Pending Review',
        subtext: 'Awaiting docs',
        email: 'olivia.brown@deliverease.com'
      },
      {
        id: 'DRV-1046',
        name: 'Liam Jones',
        phone: '+1 (555) 345-6780',
        avatar: '7.png',
        partnerType: 'Independent',
        vehicle: 'Car (Honda)',
        serviceArea: 'Downtown Zone',
        date: 'Oct 21, 2023',
        time: '16:45 PM',
        status: 'Docs Verified',
        subtext: 'Ready for approval',
        email: 'liam.jones@deliverease.com'
      },
      {
        id: 'DRV-1047',
        name: 'Emma Miller',
        phone: '+1 (555) 456-7891',
        avatar: '2.png',
        partnerType: 'Independent',
        vehicle: 'Electric Scooter',
        serviceArea: 'Northwest District',
        date: 'Oct 20, 2023',
        time: '14:10 PM',
        status: 'Pending Review',
        subtext: 'Awaiting docs',
        email: 'emma.miller@deliverease.com'
      },
      {
        id: 'DRV-1048',
        name: 'Noah Davis',
        phone: '+1 (555) 567-8902',
        avatar: '5.png',
        partnerType: 'Independent',
        vehicle: 'Motorcycle',
        serviceArea: 'Southeast Hub',
        date: 'Oct 19, 2023',
        time: '10:30 AM',
        status: 'Docs Verified',
        subtext: 'Ready for approval',
        email: 'noah.davis@deliverease.com'
      },
      {
        id: 'DRV-1049',
        name: 'Sophia Garcia',
        phone: '+1 (555) 678-9013',
        avatar: '4.png',
        partnerType: 'Independent',
        vehicle: 'Electric Scooter',
        serviceArea: 'Downtown Zone',
        date: 'Oct 18, 2023',
        time: '12:00 PM',
        status: 'Pending Review',
        subtext: 'Awaiting docs',
        email: 'sophia.garcia@deliverease.com'
      },
      {
        id: 'DRV-1050',
        name: 'Oliver Rodriguez',
        phone: '+1 (555) 789-0124',
        avatar: '3.png',
        partnerType: 'Independent',
        vehicle: 'Car (Toyota)',
        serviceArea: 'Northwest District',
        date: 'Oct 17, 2023',
        time: '08:45 AM',
        status: 'Docs Verified',
        subtext: 'Ready for approval',
        email: 'oliver.rodriguez@deliverease.com'
      },
      {
        id: 'DRV-1051',
        name: 'Isabella Wilson',
        phone: '+1 (555) 890-1235',
        avatar: '6.png',
        partnerType: 'Independent',
        vehicle: 'Motorcycle (Honda)',
        serviceArea: 'Southeast Hub',
        date: 'Oct 16, 2023',
        time: '11:15 AM',
        status: 'Pending Review',
        subtext: 'Awaiting docs',
        email: 'isabella.wilson@deliverease.com'
      },
      {
        id: 'DRV-1052',
        name: 'Lucas Martinez',
        phone: '+1 (555) 901-2346',
        avatar: '1.png',
        partnerType: 'Independent',
        vehicle: 'Electric Scooter',
        serviceArea: 'Downtown Zone',
        date: 'Oct 15, 2023',
        time: '15:20 PM',
        status: 'Docs Verified',
        subtext: 'Ready for approval',
        email: 'lucas.martinez@deliverease.com'
      }
    ];

    // Other names for generating approved/suspended rows
    const mockNames = [
      'Olivia Brown', 'Liam Jones', 'Emma Miller', 'Noah Davis', 'Sophia Garcia',
      'Oliver Rodriguez', 'Isabella Wilson', 'Lucas Martinez', 'Mia Anderson', 'Mason Thomas',
      'Charlotte Taylor', 'Logan Moore', 'Amelia Jackson', 'Ethan Martin', 'Evelyn Lee',
      'Alexander Perez', 'Abigail Thompson', 'Jacob White', 'Harper Harris', 'Michael Sanchez',
      'Emily Clark', 'Daniel Ramirez', 'Elizabeth Lewis', 'Henry Robinson', 'Sofia Walker',
      'Jackson Young', 'Avery Allen', 'Sebastian King'
    ];
    const mockVehicles = [
      'Motorcycle (Yamaha)', 'Electric Scooter', 'Car (Honda)', 'Auto Rickshaw', 'Motorcycle (Suzuki)',
      'Car (Hyundai)', 'Electric Scooter (Ola)', 'Cargo Van', 'Motorcycle (TVS)', 'Car (Ford)'
    ];
    const mockAreas = [
      'Downtown Zone', 'Northwest District', 'Southeast Hub', 'Uptown Area', 'East Side'
    ];
    const mockPartnerTypes = ['Independent'];

    // Generate remaining 28 rows (42 total)
    const extraApprovedSuspended = [];
    for (let i = 0; i < 28; i++) {
      const name = mockNames[i % mockNames.length];
      const email = name.toLowerCase().replace(' ', '.') + '@deliverease.com';
      const partnerType = mockPartnerTypes[i % mockPartnerTypes.length];
      const vehicle = mockVehicles[i % mockVehicles.length];
      const serviceArea = mockAreas[i % mockAreas.length];
      const isSuspended = i % 5 === 0; // Some suspended, rest approved
      const status = isSuspended ? 'Suspended' : 'Approved';
      const idNum = 1053 + i;
      const id = `DRV-${idNum}`;
      
      const day = 14 - (i % 12);
      const hour = 9 + (i % 8);
      const min = 10 + (i % 45);
      const dateStr = `Oct ${day}, 2023`;
      const timeStr = `${hour.toString().padStart(2, '0')}:${min.toString().padStart(2, '0')} ${hour >= 12 ? 'PM' : 'AM'}`;
      
      extraApprovedSuspended.push({
        id,
        name,
        phone: `+1 (555) ${300 + i}-${4000 + i}`,
        avatar: `${(i % 7) + 1}.png`,
        partnerType,
        vehicle,
        serviceArea,
        date: dateStr,
        time: timeStr,
        status,
        subtext: '',
        email
      });
    }

    // Combine all to get 42 entries
    const initialDrivers = [...firstFour, ...extraPending, ...extraApprovedSuspended];
    if (localStorage.getItem('allDrivers')) {
        window.allDrivers = JSON.parse(localStorage.getItem('allDrivers'));
        // Sync fields while preserving local statuses
        initialDrivers.forEach(initD => {
            const found = window.allDrivers.find(d => d.id === initD.id);
            if (found) {
                found.name = initD.name;
                found.phone = initD.phone;
                found.avatar = initD.avatar;
                found.partnerType = initD.partnerType;
                found.vehicle = initD.vehicle;
                found.serviceArea = initD.serviceArea;
                found.date = initD.date;
                found.time = initD.time;
            } else {
                window.allDrivers.push(initD);
            }
        });
        localStorage.setItem('allDrivers', JSON.stringify(window.allDrivers));
    } else {
        window.allDrivers = initialDrivers;
        localStorage.setItem('allDrivers', JSON.stringify(initialDrivers));
    }

    // State parameters
    window.currentPartner = 'all';
    window.currentArea = 'all';
    window.currentStatus = 'Pending'; // By default, filter by Pending review/Docs verified as in screenshot!
    window.currentSearch = '';
    window.currentPage = 1;
    window.itemsPerPage = 10;

    // Toast objects
    const toastEl = document.getElementById('success-toast');
    const toastMsg = document.getElementById('toast-message');
    const toast = new bootstrap.Toast(toastEl);

    // Global helper to show toasts
    window.showToast = function(message, type = 'success') {
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

    // Helper count pending/verified onboarding items
    window.countPending = function() {
        return window.allDrivers.filter(d => d.status === 'Pending Review' || d.status === 'Docs Verified').length;
    }

    // Initialize sidebar badge and drop-down label counts
    window.updateSidebarBadge = function() {
        const count = countPending();
        
        // Update sidebar list badge if present
        const sidebarBadge = document.querySelector('.badge.bg-danger');
        if (sidebarBadge) {
            if (count === 0) {
                sidebarBadge.style.display = 'none';
            } else {
                sidebarBadge.style.display = 'block';
                sidebarBadge.innerText = count;
            }
        }

        // Update dropdown filter count text
        const statusLabel = document.getElementById('selected-status-label');
        if (window.currentStatus === 'Pending') {
            statusLabel.innerText = `Pending Review (${count})`;
        }
        
        // Sync custom menu item text for pending
        const pendingItem = document.querySelector('[data-value="Pending"]');
        if (pendingItem) {
            pendingItem.innerText = `Pending Review (${count})`;
        }
    }

    // Filter setter
    window.setFilter = function(type, value, label) {
        if (type === 'partner') {
            window.currentPartner = value;
            document.getElementById('selected-partner-label').innerText = label;
            updateDropdownActive('partnerFilterBtn', value);
        } else if (type === 'area') {
            window.currentArea = value;
            document.getElementById('selected-area-label').innerText = label;
            updateDropdownActive('areaFilterBtn', value);
        } else if (type === 'status') {
            window.currentStatus = value;
            if (value === 'Pending') {
                document.getElementById('selected-status-label').innerText = `Pending Review (${countPending()})`;
            } else {
                document.getElementById('selected-status-label').innerText = label;
            }
            updateDropdownActive('statusFilterBtn', value);
        }
        
        window.currentPage = 1;
        renderTable();
    }

    // Active state sync on dropdown choices
    function updateDropdownActive(btnId, value) {
        const btn = document.getElementById(btnId);
        const menu = btn.nextElementSibling;
        const items = menu.querySelectorAll('.dropdown-item');
        items.forEach(item => {
            if (item.getAttribute('data-value') === value) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Render registrations table
    window.renderTable = function() {
        const tableBody = document.getElementById('approvals-tbody');
        const blankState = document.getElementById('blank-state');
        const tableWrapper = document.querySelector('.table-responsive');
        const footerWrapper = document.querySelector('.card-footer');
        
        // Filter elements
        let filtered = window.allDrivers.filter(driver => {
            // Search filter
            const query = window.currentSearch.toLowerCase().trim();
            const matchesSearch = !query || 
                                  driver.name.toLowerCase().includes(query) ||
                                  driver.id.toLowerCase().includes(query) ||
                                  driver.phone.toLowerCase().includes(query);
                                  
            // Partner filter
            const matchesPartner = window.currentPartner === 'all' || driver.partnerType === window.currentPartner;
            
            // Area filter
            const matchesArea = window.currentArea === 'all' || driver.serviceArea === window.currentArea;
            
            // Status filter (Pending maps to both 'Pending Review' and 'Docs Verified' as they await action)
            let matchesStatus = true;
            if (window.currentStatus !== 'all') {
                if (window.currentStatus === 'Pending') {
                    matchesStatus = driver.status === 'Pending Review' || driver.status === 'Docs Verified';
                } else {
                    matchesStatus = driver.status === window.currentStatus;
                }
            }
            
            return matchesSearch && matchesPartner && matchesArea && matchesStatus;
        });

        if (filtered.length === 0) {
            tableBody.innerHTML = '';
            tableWrapper.classList.add('d-none');
            footerWrapper.classList.add('d-none');
            blankState.classList.remove('d-none');
            return;
        }

        tableWrapper.classList.remove('d-none');
        footerWrapper.classList.remove('d-none');
        blankState.classList.add('d-none');

        // Paginate logic
        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / window.itemsPerPage);
        if (window.currentPage > totalPages) window.currentPage = Math.max(1, totalPages);
        
        const startIndex = (window.currentPage - 1) * window.itemsPerPage;
        const endIndex = Math.min(startIndex + window.itemsPerPage, totalItems);
        const paginated = filtered.slice(startIndex, endIndex);

        // Build HTML
        let html = '';
        paginated.forEach(driver => {
            let statusBadgeHtml = '';
            if (driver.status === 'Pending Review') {
                statusBadgeHtml = `
                    <div>
                        <span class="status-badge pending">
                            <span class="dot"></span>
                            Pending Review
                        </span>
                        <div class="status-subtext">
                            <i class="bx bx-time-five"></i> Awaiting docs
                        </div>
                    </div>
                `;
            } else if (driver.status === 'Docs Verified') {
                statusBadgeHtml = `
                    <div>
                        <span class="status-badge verified">
                            <span class="icon"><i class="bx bxs-check-circle"></i></span>
                            Docs Verified
                        </span>
                        <div class="status-subtext">
                            Ready for approval
                        </div>
                    </div>
                `;
            } else if (driver.status === 'Approved') {
                statusBadgeHtml = `
                    <div>
                        <span class="status-badge approved">
                            <span class="dot"></span>
                            Approved
                        </span>
                    </div>
                `;
            } else if (driver.status === 'Suspended') {
                statusBadgeHtml = `
                    <div>
                        <span class="status-badge suspended">
                            <span class="icon"><i class="bx bx-minus-circle"></i></span>
                            Suspended
                        </span>
                    </div>
                `;
            }

            // Map profiles
            const isReviewable = driver.status === 'Pending Review' || driver.status === 'Docs Verified';
            const profileUrl = isReviewable 
                ? `/fleet/approvals/${driver.id}/review` 
                : `/fleet/drivers/${driver.id}/profile`;
            const actionText = isReviewable ? 'Review Application' : 'View Profile';
            const actionIcon = isReviewable ? 'bx-clipboard' : 'bx-user';

            html += `
                <tr id="row-${driver.id}">
                    <td style="padding: 16px 20px;">
                        <div class="form-check m-0">
                            <input class="form-check-input row-checkbox" type="checkbox" data-id="${driver.id}" id="chk-${driver.id}" onclick="updateBulkButtons()">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3" style="width: 40px; height: 40px;">
                                <img src="/assets/img/avatars/${driver.avatar}" alt="${driver.name}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            </div>
                            <div>
                                <a href="${profileUrl}" class="mb-0 fw-bold text-body text-decoration-none hover-primary" style="font-size: 0.9rem; display: block;">${driver.name}</a>
                                <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4;">${driver.phone}</div>
                                <div class="text-muted" style="font-size: 0.75rem; line-height: 1.2;">ID: ${driver.id}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-body" style="font-size: 0.88rem;">${driver.partnerType}</div>
                        <div class="text-muted" style="font-size: 0.8rem; margin-top: 2px;">${driver.vehicle}</div>
                    </td>
                    <td>
                        <span class="badge area-badge-pill">${driver.serviceArea}</span>
                    </td>
                    <td>
                        <div class="text-body" style="font-size: 0.88rem;">${driver.date}</div>
                        <div class="text-muted" style="font-size: 0.8rem; margin-top: 2px;">${driver.time}</div>
                    </td>
                    <td>
                        ${statusBadgeHtml}
                    </td>
                    <td class="text-end pe-4">
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow btn-action-trigger" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded" style="font-size: 1.25rem;"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0);" onclick="approveDriver('${driver.id}')"><i class="bx bx-check text-success"></i> Approve</a>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0);" onclick="rejectDriver('${driver.id}')"><i class="bx bx-x text-danger"></i> Reject</a>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="${profileUrl}"><i class="bx ${actionIcon}"></i> ${actionText}</a>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;

        // Update footer info text
        document.getElementById('showing-start').innerText = startIndex + 1;
        document.getElementById('showing-end').innerText = endIndex;
        document.getElementById('showing-total').innerText = totalItems;

        renderPagination(totalPages);
        document.getElementById('selectAllCheckbox').checked = false;
        updateBulkButtons();
    }

    // Render pagination buttons
    function renderPagination(totalPages) {
        const container = document.getElementById('pagination-controls');
        let html = '';

        // Previous button
        html += `
            <li class="page-item ${window.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" onclick="changePage(${window.currentPage - 1})">Previous</a>
            </li>
        `;

        const maxVisible = 5;
        if (totalPages <= maxVisible) {
            for (let i = 1; i <= totalPages; i++) {
                html += `
                    <li class="page-item ${window.currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
            }
        } else {
            // Smart pagination truncation matching screenshot (1 2 3 ... 5)
            if (window.currentPage <= 3) {
                for (let i = 1; i <= 3; i++) {
                    html += `
                        <li class="page-item ${window.currentPage === i ? 'active' : ''}">
                            <a class="page-link" href="javascript:void(0);" onclick="changePage(${i})">${i}</a>
                        </li>
                    `;
                }
                html += `
                    <li class="page-item disabled">
                        <span class="page-link" style="border: none; background: transparent;">...</span>
                    </li>
                `;
                html += `
                    <li class="page-item ${window.currentPage === totalPages ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(${totalPages})">${totalPages}</a>
                    </li>
                `;
            } else if (window.currentPage >= totalPages - 2) {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(1)">1</a>
                    </li>
                `;
                html += `
                    <li class="page-item disabled">
                        <span class="page-link" style="border: none; background: transparent;">...</span>
                    </li>
                `;
                for (let i = totalPages - 2; i <= totalPages; i++) {
                    html += `
                        <li class="page-item ${window.currentPage === i ? 'active' : ''}">
                            <a class="page-link" href="javascript:void(0);" onclick="changePage(${i})">${i}</a>
                        </li>
                    `;
                }
            } else {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(1)">1</a>
                    </li>
                `;
                html += `
                    <li class="page-item disabled">
                        <span class="page-link" style="border: none; background: transparent;">...</span>
                    </li>
                `;
                html += `
                    <li class="page-item active">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(${window.currentPage})">${window.currentPage}</a>
                    </li>
                `;
                html += `
                    <li class="page-item disabled">
                        <span class="page-link" style="border: none; background: transparent;">...</span>
                    </li>
                `;
                html += `
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(${totalPages})">${totalPages}</a>
                    </li>
                `;
            }
        }

        // Next button
        html += `
            <li class="page-item ${window.currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" onclick="changePage(${window.currentPage + 1})">Next</a>
            </li>
        `;

        container.innerHTML = html;
    }

    // Change page callback
    window.changePage = function(page) {
        window.currentPage = page;
        renderTable();
    }

    // Toggle Select All row checkboxes
    window.toggleSelectAll = function(master) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(chk => chk.checked = master.checked);
        updateBulkButtons();
    }

    // Update state of bulk action buttons
    window.updateBulkButtons = function() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const approveBtn = document.getElementById('bulk-approve-btn');
        const rejectBtn = document.getElementById('bulk-reject-btn');
        
        if (checkboxes.length > 0) {
            approveBtn.innerText = `Approve Selected (${checkboxes.length})`;
            rejectBtn.innerText = `Reject Selected (${checkboxes.length})`;
            approveBtn.removeAttribute('disabled');
            rejectBtn.removeAttribute('disabled');
            approveBtn.style.opacity = '1';
            rejectBtn.style.opacity = '1';
        } else {
            approveBtn.innerText = 'Approve Selected';
            rejectBtn.innerText = 'Reject Selected';
            approveBtn.setAttribute('disabled', 'true');
            rejectBtn.setAttribute('disabled', 'true');
            approveBtn.style.opacity = '0.65';
            rejectBtn.style.opacity = '0.65';
        }
    }

    // Action: Approve single driver
    window.approveDriver = function(driverId) {
        const driver = window.allDrivers.find(d => d.id === driverId);
        if (!driver) return;

        driver.status = 'Approved';
        driver.subtext = '';
        localStorage.setItem('allDrivers', JSON.stringify(window.allDrivers));

        const row = document.getElementById(`row-${driverId}`);
        if (row) {
            row.style.transition = 'all 0.35s ease';
            row.style.backgroundColor = 'rgba(16, 185, 129, 0.12)';
            setTimeout(() => {
                if (window.currentStatus === 'Pending') {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        renderTable();
                        updateSidebarBadge();
                    }, 350);
                } else {
                    renderTable();
                    updateSidebarBadge();
                }
                showToast(`${driver.name} has been approved successfully.`);
            }, 300);
        }
    }

    // Action: Reject single driver
    window.rejectDriver = function(driverId) {
        const driver = window.allDrivers.find(d => d.id === driverId);
        if (!driver) return;

        driver.status = 'Suspended';
        driver.subtext = '';
        localStorage.setItem('allDrivers', JSON.stringify(window.allDrivers));

        const row = document.getElementById(`row-${driverId}`);
        if (row) {
            row.style.transition = 'all 0.35s ease';
            row.style.backgroundColor = 'rgba(239, 68, 68, 0.12)';
            setTimeout(() => {
                if (window.currentStatus === 'Pending') {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        renderTable();
                        updateSidebarBadge();
                    }, 350);
                } else {
                    renderTable();
                    updateSidebarBadge();
                }
                showToast(`${driver.name} registration has been rejected.`, 'danger');
            }, 300);
        }
    }

    // Action: Bulk Approve/Reject selected
    window.bulkAction = function(action) {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length === 0) return;

        const ids = Array.from(checked).map(chk => chk.getAttribute('data-id'));
        let count = 0;

        ids.forEach(id => {
            const driver = window.allDrivers.find(d => d.id === id);
            if (driver) {
                if (action === 'approve') {
                    driver.status = 'Approved';
                    driver.subtext = '';
                } else {
                    driver.status = 'Suspended';
                    driver.subtext = '';
                }
                count++;
            }
        });

        localStorage.setItem('allDrivers', JSON.stringify(window.allDrivers));

        // Add visual transition highlights
        ids.forEach(id => {
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.style.transition = 'all 0.35s ease';
                row.style.backgroundColor = action === 'approve' ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)';
            }
        });

        setTimeout(() => {
            ids.forEach(id => {
                const row = document.getElementById(`row-${id}`);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                }
            });

            setTimeout(() => {
                renderTable();
                updateSidebarBadge();
                if (action === 'approve') {
                    showToast(`Approved ${count} applications successfully.`);
                } else {
                    showToast(`Rejected ${count} applications.`, 'danger');
                }
            }, 350);
        }, 350);
    }

    // Search bar filter handler
    document.getElementById('search-driver-input').addEventListener('input', function(e) {
        window.currentSearch = e.target.value;
        window.currentPage = 1;
        renderTable();
    });

    // Export button -> download CSV
    document.getElementById('export-btn').addEventListener('click', function() {
        let csv = 'ID,Name,Phone,Email,Partner Type,Vehicle,Service Area,Registration Date,Status\n';
        window.allDrivers.forEach(d => {
            csv += `"${d.id}","${d.name}","${d.phone}","${d.email}","${d.partnerType}","${d.vehicle}","${d.serviceArea}","${d.date} ${d.time}","${d.status}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", `deliverease_registrations_${new Date().toISOString().slice(0, 10)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast("Driver list exported to CSV successfully.");
    });

    // Initial render
    renderTable();
    updateSidebarBadge();
});
</script>
@endsection
