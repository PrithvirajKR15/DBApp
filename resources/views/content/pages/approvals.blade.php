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

    .status-badge.rejected {
        background-color: #fef2f2 !important;
        color: #b91c1c !important;
    }
    .status-badge.rejected .icon {
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
                        @foreach(($zones ?? collect()) as $zoneOption)
                            <li><a class="dropdown-item" href="javascript:void(0);" data-value="{{ $zoneOption->name }}" onclick="setFilter('area', @json($zoneOption->name), @json($zoneOption->name))">{{ $zoneOption->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Status Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center border bg-white filter-dropdown-btn" type="button" id="statusFilterBtn" data-bs-toggle="dropdown" aria-expanded="false" style="border-color: #e0e2e7 !important; border-radius: 8px; padding: 8px 14px; font-size: 0.88rem; height: 38px; color: #566a7f; min-width: 190px; justify-content: space-between;">
                        <span id="selected-status-label">Pending Review</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="statusFilterBtn">
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="all" onclick="setFilter('status', 'all', 'All Statuses')">All Statuses</a></li>
                        <li><a class="dropdown-item active" href="javascript:void(0);" data-value="Pending" onclick="setFilter('status', 'Pending', 'Pending Review')">Pending Review</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-value="Rejected" onclick="setFilter('status', 'Rejected', 'Rejected')">Rejected</a></li>
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
                <tr class="table-light border-bottom">
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const listUrl = @json(route('fleet-approvals.list'));
    const statusUrlTemplate = @json(url('/fleet/approvals/__CODE__/status'));
    const bulkStatusUrl = @json(route('fleet-approvals.bulk-status'));

    window.allDrivers = [];
    window.pendingCount = 0;
    window.rejectedCount = 0;
    window.currentPartner = 'all';
    window.currentArea = 'all';
    window.currentStatus = 'Pending';
    window.currentSearch = '';
    window.currentPage = 1;
    window.itemsPerPage = 10;

    const toastEl = document.getElementById('success-toast');
    const toastMsg = document.getElementById('toast-message');
    const toast = new bootstrap.Toast(toastEl);

    window.showToast = function(message, type = 'success') {
        toastMsg.innerText = message;
        toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');
        if (type === 'success') toastEl.classList.add('bg-success');
        else if (type === 'danger') toastEl.classList.add('bg-danger');
        else toastEl.classList.add('bg-warning');
        toast.show();
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resolveAvatarUrl(avatar) {
        if (!avatar) return '/assets/img/avatars/1.png';
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

    window.countPending = function() {
        return window.pendingCount;
    };

    window.updateSidebarBadge = function() {
        const count = countPending();
        const menuLink = document.querySelector('a[href*="/fleet/approvals"]');
        let sidebarBadge = document.querySelector('[data-menu-badge="fleet-approvals"]');

        if (count === 0) {
            if (sidebarBadge) sidebarBadge.remove();
        } else if (sidebarBadge) {
            sidebarBadge.style.display = '';
            sidebarBadge.innerText = count;
        } else if (menuLink) {
            sidebarBadge = document.createElement('div');
            sidebarBadge.className = 'badge rounded-pill bg-danger text-uppercase ms-auto';
            sidebarBadge.dataset.menuBadge = 'fleet-approvals';
            sidebarBadge.innerText = count;
            menuLink.appendChild(sidebarBadge);
        }

        const statusLabel = document.getElementById('selected-status-label');
        if (window.currentStatus === 'Pending') {
            statusLabel.innerText = `Pending Review (${count})`;
        } else if (window.currentStatus === 'Rejected') {
            statusLabel.innerText = `Rejected (${window.rejectedCount})`;
        }

        const pendingItem = document.querySelector('#statusFilterBtn + .dropdown-menu [data-value="Pending"]');
        if (pendingItem) pendingItem.innerText = `Pending Review (${count})`;
        const rejectedItem = document.querySelector('#statusFilterBtn + .dropdown-menu [data-value="Rejected"]');
        if (rejectedItem) rejectedItem.innerText = `Rejected (${window.rejectedCount})`;
    };

    window.setFilter = function(type, value, label) {
        if (type === 'area') {
            window.currentArea = value;
            document.getElementById('selected-area-label').innerText = label;
            updateDropdownActive('areaFilterBtn', value);
        } else if (type === 'status') {
            window.currentStatus = value;
            if (value === 'Pending') {
                document.getElementById('selected-status-label').innerText = `Pending Review (${countPending()})`;
            } else if (value === 'Rejected') {
                document.getElementById('selected-status-label').innerText = `Rejected (${window.rejectedCount})`;
            } else {
                document.getElementById('selected-status-label').innerText = label;
            }
            updateDropdownActive('statusFilterBtn', value);
        }

        window.currentPage = 1;
        renderTable();
    };

    function updateDropdownActive(btnId, value) {
        const btn = document.getElementById(btnId);
        const menu = btn.nextElementSibling;
        menu.querySelectorAll('.dropdown-item').forEach((item) => {
            item.classList.toggle('active', item.getAttribute('data-value') === value);
        });
    }

    function showTableLoading() {
        const tableBody = document.getElementById('approvals-tbody');
        const blankState = document.getElementById('blank-state');
        const tableWrapper = document.querySelector('.table-responsive');
        const footerWrapper = document.querySelector('.card-footer');
        blankState.classList.add('d-none');
        tableWrapper.classList.remove('d-none');
        footerWrapper.classList.remove('d-none');
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>
                    Loading registrations...
                </td>
            </tr>
        `;
    }

    async function loadDrivers() {
        showTableLoading();
        try {
            const response = await fetch(listUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await parseJsonResponse(response);
            window.allDrivers = data.drivers || [];
            window.pendingCount = data.pending_count ?? window.allDrivers.filter((d) => d.status === 'Pending').length;
            window.rejectedCount = data.rejected_count ?? window.allDrivers.filter((d) => d.status === 'Rejected').length;
            updateSidebarBadge();
            renderTable();
        } catch (error) {
            window.allDrivers = [];
            window.pendingCount = 0;
            window.rejectedCount = 0;
            updateSidebarBadge();
            document.getElementById('approvals-tbody').innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-danger">
                        <i class="bx bx-error-circle fs-3 mb-2 d-block"></i>
                        ${escapeHtml(error.message || 'Unable to load registrations.')}
                    </td>
                </tr>
            `;
            showToast(error.message || 'Unable to load registrations.', 'danger');
        }
    }

    window.renderTable = function() {
        const tableBody = document.getElementById('approvals-tbody');
        const blankState = document.getElementById('blank-state');
        const tableWrapper = document.querySelector('.table-responsive');
        const footerWrapper = document.querySelector('.card-footer');

        let filtered = window.allDrivers.filter((driver) => {
            const query = window.currentSearch.toLowerCase().trim();
            const matchesSearch = !query
                || (driver.name || '').toLowerCase().includes(query)
                || (driver.id || '').toLowerCase().includes(query)
                || (driver.phone || '').toLowerCase().includes(query);

            const matchesArea = window.currentArea === 'all' || driver.serviceArea === window.currentArea;
            const matchesStatus = window.currentStatus === 'all' || driver.status === window.currentStatus;

            return matchesSearch && matchesArea && matchesStatus;
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

        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / window.itemsPerPage);
        if (window.currentPage > totalPages) window.currentPage = Math.max(1, totalPages);

        const startIndex = (window.currentPage - 1) * window.itemsPerPage;
        const endIndex = Math.min(startIndex + window.itemsPerPage, totalItems);
        const paginated = filtered.slice(startIndex, endIndex);

        let html = '';
        paginated.forEach((driver) => {
            let statusBadgeHtml = '';
            if (driver.status === 'Pending') {
                statusBadgeHtml = `
                    <div>
                        <span class="status-badge pending">
                            <span class="dot"></span>
                            Pending Review
                        </span>
                        ${driver.subtext ? `<div class="status-subtext"><i class="bx bx-time-five"></i> ${escapeHtml(driver.subtext)}</div>` : ''}
                    </div>
                `;
            } else if (driver.status === 'Rejected') {
                statusBadgeHtml = `
                    <div>
                        <span class="status-badge rejected">
                            <span class="icon"><i class="bx bx-x-circle"></i></span>
                            Rejected
                        </span>
                    </div>
                `;
            }

            const isReviewable = driver.status === 'Pending' || driver.status === 'Rejected';
            const profileUrl = isReviewable
                ? `/fleet/approvals/${encodeURIComponent(driver.id)}/review`
                : `/fleet/drivers/${encodeURIComponent(driver.id)}/profile`;
            const actionText = driver.status === 'Pending' ? 'Review Application' : 'View Application';
            const actionIcon = isReviewable ? 'bx-clipboard' : 'bx-user';
            const avatarSrc = resolveAvatarUrl(driver.avatar);

            html += `
                <tr id="row-${escapeHtml(driver.id)}">
                    <td style="padding: 16px 20px;">
                        <div class="form-check m-0">
                            <input class="form-check-input row-checkbox" type="checkbox" data-id="${escapeHtml(driver.id)}" id="chk-${escapeHtml(driver.id)}" onclick="updateBulkButtons()">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3" style="width: 40px; height: 40px;">
                                <img src="${avatarSrc}" alt="${escapeHtml(driver.name)}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            </div>
                            <div>
                                <a href="${profileUrl}" class="mb-0 fw-bold text-body text-decoration-none hover-primary" style="font-size: 0.9rem; display: block;">${escapeHtml(driver.name)}</a>
                                <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4;">${escapeHtml(driver.phone)}</div>
                                <div class="text-muted" style="font-size: 0.75rem; line-height: 1.2;">ID: ${escapeHtml(driver.id)}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-body" style="font-size: 0.88rem;">${escapeHtml(driver.partnerType)}</div>
                        <div class="text-muted" style="font-size: 0.8rem; margin-top: 2px;">${escapeHtml(driver.vehicle)}</div>
                    </td>
                    <td>
                        <span class="badge area-badge-pill">${escapeHtml(driver.serviceArea)}</span>
                    </td>
                    <td>
                        <div class="text-body" style="font-size: 0.88rem;">${escapeHtml(driver.date)}</div>
                        <div class="text-muted" style="font-size: 0.8rem; margin-top: 2px;">${escapeHtml(driver.time)}</div>
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
                                <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0);" onclick="approveDriver('${escapeHtml(driver.id)}')"><i class="bx bx-check text-success"></i> Approve</a>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0);" onclick="rejectDriver('${escapeHtml(driver.id)}')"><i class="bx bx-x text-danger"></i> Reject</a>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="${profileUrl}"><i class="bx ${actionIcon}"></i> ${actionText}</a>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
        document.getElementById('showing-start').innerText = startIndex + 1;
        document.getElementById('showing-end').innerText = endIndex;
        document.getElementById('showing-total').innerText = totalItems;
        renderPagination(totalPages);
        document.getElementById('selectAllCheckbox').checked = false;
        updateBulkButtons();
    };

    function renderPagination(totalPages) {
        const container = document.getElementById('pagination-controls');
        let html = `
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
        } else if (window.currentPage <= 3) {
            for (let i = 1; i <= 3; i++) {
                html += `
                    <li class="page-item ${window.currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
            }
            html += `<li class="page-item disabled"><span class="page-link" style="border: none; background: transparent;">...</span></li>`;
            html += `
                <li class="page-item ${window.currentPage === totalPages ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0);" onclick="changePage(${totalPages})">${totalPages}</a>
                </li>
            `;
        } else if (window.currentPage >= totalPages - 2) {
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="changePage(1)">1</a></li>`;
            html += `<li class="page-item disabled"><span class="page-link" style="border: none; background: transparent;">...</span></li>`;
            for (let i = totalPages - 2; i <= totalPages; i++) {
                html += `
                    <li class="page-item ${window.currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0);" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
            }
        } else {
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="changePage(1)">1</a></li>`;
            html += `<li class="page-item disabled"><span class="page-link" style="border: none; background: transparent;">...</span></li>`;
            html += `
                <li class="page-item active">
                    <a class="page-link" href="javascript:void(0);" onclick="changePage(${window.currentPage})">${window.currentPage}</a>
                </li>
            `;
            html += `<li class="page-item disabled"><span class="page-link" style="border: none; background: transparent;">...</span></li>`;
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="changePage(${totalPages})">${totalPages}</a></li>`;
        }

        html += `
            <li class="page-item ${window.currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" onclick="changePage(${window.currentPage + 1})">Next</a>
            </li>
        `;
        container.innerHTML = html;
    }

    window.changePage = function(page) {
        window.currentPage = page;
        renderTable();
    };

    window.toggleSelectAll = function(master) {
        document.querySelectorAll('.row-checkbox').forEach((chk) => { chk.checked = master.checked; });
        updateBulkButtons();
    };

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
    };

    async function updateDriverStatus(driverId, status) {
        const response = await fetch(routeForCode(statusUrlTemplate, driverId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status }),
        });
        return parseJsonResponse(response);
    }

    function applyLocalStatusChange(driverId, status) {
        const index = window.allDrivers.findIndex((d) => d.id === driverId);
        if (index === -1) return null;
        const driver = window.allDrivers[index];

        if (status === 'Active') {
            window.allDrivers.splice(index, 1);
        } else {
            driver.status = status;
            driver.subtext = '';
        }

        window.pendingCount = window.allDrivers.filter((d) => d.status === 'Pending').length;
        window.rejectedCount = window.allDrivers.filter((d) => d.status === 'Rejected').length;
        return driver;
    }

    window.approveDriver = async function(driverId) {
        const driver = window.allDrivers.find((d) => d.id === driverId);
        if (!driver) return;

        try {
            await updateDriverStatus(driverId, 'Active');
            const row = document.getElementById(`row-${driverId}`);
            if (row) {
                row.style.transition = 'all 0.35s ease';
                row.style.backgroundColor = 'rgba(16, 185, 129, 0.12)';
            }
            setTimeout(() => {
                applyLocalStatusChange(driverId, 'Active');
                renderTable();
                updateSidebarBadge();
                showToast(`${driver.name} has been approved successfully.`);
            }, 250);
        } catch (error) {
            showToast(error.message || 'Unable to approve driver.', 'danger');
        }
    };

    window.rejectDriver = async function(driverId) {
        const driver = window.allDrivers.find((d) => d.id === driverId);
        if (!driver) return;

        try {
            await updateDriverStatus(driverId, 'Rejected');
            const row = document.getElementById(`row-${driverId}`);
            if (row) {
                row.style.transition = 'all 0.35s ease';
                row.style.backgroundColor = 'rgba(239, 68, 68, 0.12)';
            }
            setTimeout(() => {
                applyLocalStatusChange(driverId, 'Rejected');
                renderTable();
                updateSidebarBadge();
                showToast(`${driver.name} registration has been rejected.`, 'danger');
            }, 250);
        } catch (error) {
            showToast(error.message || 'Unable to reject driver.', 'danger');
        }
    };

    window.bulkAction = async function(action) {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length === 0) return;

        const ids = Array.from(checked).map((chk) => chk.getAttribute('data-id'));
        const status = action === 'approve' ? 'Active' : 'Rejected';

        try {
            const response = await fetch(bulkStatusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ codes: ids, status }),
            });
            const data = await parseJsonResponse(response);
            window.allDrivers = data.drivers || [];
            window.pendingCount = data.pending_count ?? 0;
            window.rejectedCount = data.rejected_count ?? 0;
            renderTable();
            updateSidebarBadge();
            showToast(
                data.message || (action === 'approve'
                    ? `Approved ${ids.length} applications successfully.`
                    : `Rejected ${ids.length} applications.`),
                action === 'approve' ? 'success' : 'danger'
            );
        } catch (error) {
            showToast(error.message || 'Bulk action failed.', 'danger');
        }
    };

    document.getElementById('search-driver-input').addEventListener('input', function(e) {
        window.currentSearch = e.target.value;
        window.currentPage = 1;
        renderTable();
    });

    document.getElementById('export-btn').addEventListener('click', function() {
        let csv = 'ID,Name,Phone,Email,Partner Type,Vehicle,Service Area,Registration Date,Status\n';
        window.allDrivers.forEach((d) => {
            csv += `"${d.id}","${d.name}","${d.phone}","${d.email}","${d.partnerType}","${d.vehicle}","${d.serviceArea}","${d.date} ${d.time}","${d.status}"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `deliverease_registrations_${new Date().toISOString().slice(0, 10)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('Driver list exported to CSV successfully.');
    });

    loadDrivers();
});
</script>
@endsection
