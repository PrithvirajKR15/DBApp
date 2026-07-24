@extends('layouts/contentNavbarLayout')

@section('title', 'Payouts')
@section('page-title', 'Payouts')

@php
    $isNavbar = false;
    $data = $data ?? [];
    $metrics = $data['metrics'] ?? [];
    $drivers = $data['drivers'] ?? [];
    $payouts = $data['payouts'] ?? [];
    $driverLifetimeTotal = collect($drivers)->sum('lifetime_paid');
    $driverPendingTotal = collect($drivers)->sum('pending_amount');
    $driverPaidOrders = collect($drivers)->sum('paid_orders');
    $statusBadges = [
        'settled' => 'success',
        'processing' => 'info',
        'pending' => 'warning',
        'failed' => 'danger',
    ];
@endphp

@section('content')
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Payouts</h4>
        <p class="text-muted mb-0">Track driver earnings, payout history, and bank transfers.</p>
    </div>
    <button type="button" class="btn btn-primary d-flex align-items-center gap-2" id="request-payout-btn">
        <i class="bx bx-credit-card"></i>
        Request Payout
    </button>
</div>

<div class="d-flex align-items-center gap-2 mb-4">
    <button type="button" class="btn btn-primary payout-view-tab" data-view="drivers">
        <i class="bx bx-group me-1"></i>Driver Payouts
    </button>
    <button type="button" class="btn btn-outline-secondary payout-view-tab" data-view="bank">
        <i class="bx bx-transfer me-1"></i>Bank Transfers
    </button>
</div>

<div id="driver-payouts-view">
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card shadow-none border h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Lifetime Driver Payouts</span>
                        <i class="bx bx-dollar-circle text-success fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-1">${{ number_format($driverLifetimeTotal, 2) }}</h3>
                    <small class="text-muted">Paid across all drivers</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card shadow-none border h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Pending Driver Amount</span>
                        <i class="bx bx-time-five text-warning fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-warning mb-1">${{ number_format($driverPendingTotal, 2) }}</h3>
                    <small class="text-muted">Awaiting payout</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card shadow-none border h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Paid Orders</span>
                        <i class="bx bx-package text-primary fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ number_format($driverPaidOrders) }}</h3>
                    <small class="text-muted">Orders included in payouts</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card shadow-none border h-100" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Drivers</span>
                        <i class="bx bx-group text-info fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ count($drivers) }}</h3>
                    <small class="text-muted">{{ collect($drivers)->where('status', 'active')->count() }} currently active</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-none border mb-3" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-lg-5">
                    <div class="input-group input-group-merge border rounded overflow-hidden">
                        <span class="input-group-text border-0 bg-transparent"><i class="bx bx-search text-muted"></i></span>
                        <input type="search" class="form-control border-0" id="driver-search" placeholder="Driver name, ID, phone, or zone...">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select" id="driver-type">
                        <option value="">All driver types</option>
                        <option value="store">Store drivers</option>
                        <option value="zone">Zone drivers</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select" id="driver-status">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select" id="driver-sort">
                        <option value="lifetime-desc">Lifetime: High to low</option>
                        <option value="lifetime-asc">Lifetime: Low to high</option>
                        <option value="recent">Most recent payout</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-1">
                    <button type="button" class="btn btn-outline-secondary w-100" id="driver-reset" title="Reset filters">
                        <i class="bx bx-reset"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted"><span class="fw-semibold text-body" id="driver-count">{{ count($drivers) }}</span> drivers</span>
        <small class="text-muted">Select a driver to view payout and order details</small>
    </div>

    <div class="card shadow-none border" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr class="table-light">
                        <th>Driver</th>
                        <th>Type / Zone</th>
                        <th class="text-end">Lifetime Paid</th>
                        <th class="text-end">Pending</th>
                        <th class="text-end">Paid Orders</th>
                        <th>Last Payout</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="driver-payout-rows">
                    @foreach ($drivers as $driver)
                        <tr class="driver-payout-row"
                            data-search="{{ strtolower($driver['id'] . ' ' . $driver['name'] . ' ' . $driver['phone'] . ' ' . $driver['zone']) }}"
                            data-type="{{ $driver['type'] }}"
                            data-status="{{ $driver['status'] }}"
                            data-lifetime="{{ $driver['lifetime_paid'] }}"
                            data-last-payout="{{ strtotime($driver['last_payout_at']) }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">{{ $driver['avatar'] }}</span></div>
                                    <div>
                                        <a href="{{ route('operations-payouts-driver-detail', ['id' => $driver['id']]) }}" class="d-block fw-semibold">{{ $driver['name'] }}</a>
                                        <small class="text-muted">{{ $driver['id'] }} · {{ $driver['phone'] }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="d-block">{{ ucfirst($driver['type']) }} driver</span><small class="text-muted">{{ $driver['zone'] }}</small></td>
                            <td class="text-end fw-bold text-success">${{ number_format($driver['lifetime_paid'], 2) }}</td>
                            <td class="text-end fw-semibold text-warning">${{ number_format($driver['pending_amount'], 2) }}</td>
                            <td class="text-end">{{ number_format($driver['paid_orders']) }}</td>
                            <td><span class="d-block">{{ date('M d, Y', strtotime($driver['last_payout_at'])) }}</span><small class="text-muted">{{ date('h:i A', strtotime($driver['last_payout_at'])) }}</small></td>
                            <td><span class="badge bg-label-{{ $driver['status'] === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($driver['status']) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('operations-payouts-driver-detail', ['id' => $driver['id']]) }}" class="btn btn-sm btn-outline-primary">
                                    Details <i class="bx bx-right-arrow-alt ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="driver-empty" class="d-none">
                        <td colspan="8" class="text-center py-5">
                            <i class="bx bx-search-alt fs-1 text-muted"></i>
                            <p class="mb-0 mt-2 fw-semibold">No drivers found</p>
                            <small class="text-muted">Try changing or resetting the filters.</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="bank-payouts-view" class="d-none">
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Available Balance</span>
                    <i class="bx bx-wallet text-primary fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1">${{ number_format($metrics['available_balance'], 2) }}</h3>
                <small class="text-muted">Ready to transfer</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Pending</span>
                    <i class="bx bx-time-five text-warning fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1 text-warning" id="pending-payout-total">${{ number_format($metrics['pending_payouts'], 2) }}</h3>
                <small class="text-muted">Awaiting settlement</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Settled Payouts</span>
                    <i class="bx bx-check-circle text-success fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1">${{ number_format($metrics['settled_payouts'], 2) }}</h3>
                <small class="text-muted">Transferred to date</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Next Settlement</span>
                    <i class="bx bx-calendar text-info fs-3"></i>
                </div>
                <h4 class="fw-bold mb-1">{{ $metrics['next_settlement'] }}</h4>
                <small class="text-muted">Estimated bank processing</small>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-none border mb-3" style="border-radius: 12px;">
    <div class="card-body p-3">
        <div class="row g-2">
            <div class="col-12 col-lg-4">
                <div class="input-group input-group-merge border rounded overflow-hidden">
                    <span class="input-group-text border-0 bg-transparent"><i class="bx bx-search text-muted"></i></span>
                    <input type="search" class="form-control border-0" id="payout-search" placeholder="Reference, bank, or account...">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="payout-date">
                    <option value="">All dates</option>
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="payout-status">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="settled">Settled</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="payout-bank">
                    <option value="">All banks</option>
                    <option value="Chase Bank N.A.">Chase Bank N.A.</option>
                    <option value="Bank of America">Bank of America</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <button type="button" class="btn btn-outline-secondary w-100" id="payout-reset">
                    <i class="bx bx-reset me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted"><span class="fw-semibold text-body" id="payout-count">{{ count($payouts) }}</span> payouts</span>
    <a href="{{ route('operations-earnings') }}" class="small fw-semibold">View earnings <i class="bx bx-right-arrow-alt"></i></a>
</div>

<div class="card shadow-none border" style="border-radius: 12px; overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr class="table-light">
                    <th>Reference</th>
                    <th>Requested</th>
                    <th>Destination</th>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Settled</th>
                </tr>
            </thead>
            <tbody id="payout-rows">
                @foreach ($payouts as $payout)
                    <tr class="payout-row"
                        data-search="{{ strtolower($payout['id'] . ' ' . $payout['bank'] . ' ' . $payout['account_ending'] . ' ' . $payout['status']) }}"
                        data-date="{{ $payout['requested_date'] }}"
                        data-status="{{ $payout['status'] }}"
                        data-bank="{{ $payout['bank'] }}">
                        <td><span class="fw-semibold">#{{ $payout['id'] }}</span></td>
                        <td>{{ date('M d, Y', strtotime($payout['requested_date'])) }}</td>
                        <td>{{ $payout['bank'] }}</td>
                        <td>•••• {{ $payout['account_ending'] }}</td>
                        <td class="text-end fw-bold">${{ number_format($payout['amount'], 2) }}</td>
                        <td><span class="badge bg-label-{{ $statusBadges[$payout['status']] }}">{{ ucfirst($payout['status']) }}</span></td>
                        <td>{{ $payout['settled_date'] ? date('M d, Y', strtotime($payout['settled_date'])) : '—' }}</td>
                    </tr>
                @endforeach
                <tr id="payout-empty" class="d-none">
                    <td colspan="7" class="text-center py-5">
                        <i class="bx bx-search-alt fs-1 text-muted"></i>
                        <p class="mb-0 mt-2 fw-semibold">No payouts found</p>
                        <small class="text-muted">Try changing or resetting the filters.</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal fade" id="requestPayoutModal" tabindex="-1" aria-labelledby="requestPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="requestPayoutModalLabel">Request Bank Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="requestPayoutForm">
                <div class="modal-body">
                    <div class="alert alert-primary d-flex align-items-center gap-2">
                        <i class="bx bx-wallet fs-4"></i>
                        <div><small class="d-block">Available balance</small><strong>${{ number_format($metrics['available_balance'], 2) }}</strong></div>
                    </div>
                    <div class="mb-3">
                        <label for="request-payout-amount" class="form-label fw-semibold">Amount to transfer</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" id="request-payout-amount" min="10" max="{{ $metrics['available_balance'] }}" placeholder="0.00" required>
                        </div>
                        <small class="text-muted">Minimum payout is $10.00.</small>
                    </div>
                    <div>
                        <label for="request-payout-bank" class="form-label fw-semibold">Destination account</label>
                        <select class="form-select" id="request-payout-bank" required>
                            <option value="Chase Bank N.A.|8234">Chase Bank N.A. (•••• 8234)</option>
                            <option value="Bank of America|1129">Bank of America (•••• 1129)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const driverSearch = document.getElementById('driver-search');
    const driverType = document.getElementById('driver-type');
    const driverStatus = document.getElementById('driver-status');
    const driverSort = document.getElementById('driver-sort');
    const driverRowsContainer = document.getElementById('driver-payout-rows');
    const driverRows = Array.from(document.querySelectorAll('.driver-payout-row'));
    const driverCount = document.getElementById('driver-count');
    const driverEmpty = document.getElementById('driver-empty');

    function filterAndSortDrivers() {
        const term = driverSearch.value.trim().toLowerCase();
        let visible = 0;
        driverRows.forEach(function (row) {
            const matches = row.dataset.search.includes(term)
                && (!driverType.value || row.dataset.type === driverType.value)
                && (!driverStatus.value || row.dataset.status === driverStatus.value);
            row.classList.toggle('d-none', !matches);
            if (matches) visible++;
        });

        const sortedRows = [...driverRows].sort(function (a, b) {
            if (driverSort.value === 'lifetime-asc') return Number(a.dataset.lifetime) - Number(b.dataset.lifetime);
            if (driverSort.value === 'recent') return Number(b.dataset.lastPayout) - Number(a.dataset.lastPayout);
            return Number(b.dataset.lifetime) - Number(a.dataset.lifetime);
        });
        sortedRows.forEach(function (row) {
            driverRowsContainer.insertBefore(row, driverEmpty);
        });

        driverCount.textContent = visible;
        driverEmpty.classList.toggle('d-none', visible !== 0);
    }

    [driverSearch, driverType, driverStatus, driverSort].forEach(function (control) {
        control.addEventListener(control === driverSearch ? 'input' : 'change', filterAndSortDrivers);
    });

    document.getElementById('driver-reset').addEventListener('click', function () {
        driverSearch.value = '';
        driverType.value = '';
        driverStatus.value = '';
        driverSort.value = 'lifetime-desc';
        filterAndSortDrivers();
    });

    document.querySelectorAll('.payout-view-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            const showDrivers = tab.dataset.view === 'drivers';
            document.getElementById('driver-payouts-view').classList.toggle('d-none', !showDrivers);
            document.getElementById('bank-payouts-view').classList.toggle('d-none', showDrivers);
            document.querySelectorAll('.payout-view-tab').forEach(function (button) {
                const isActive = button === tab;
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-outline-secondary', !isActive);
            });
        });
    });

    const search = document.getElementById('payout-search');
    const dateFilter = document.getElementById('payout-date');
    const statusFilter = document.getElementById('payout-status');
    const bankFilter = document.getElementById('payout-bank');
    const rowsContainer = document.getElementById('payout-rows');
    const count = document.getElementById('payout-count');
    const empty = document.getElementById('payout-empty');

    function payoutRows() {
        return Array.from(document.querySelectorAll('.payout-row'));
    }

    function isInDateRange(value, range) {
        if (!range) return true;
        const rowDate = new Date(value + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const start = new Date(today);
        start.setDate(start.getDate() - (Number(range) - 1));
        return rowDate >= start && rowDate <= today;
    }

    function filterPayouts() {
        const term = search.value.trim().toLowerCase();
        let visible = 0;
        payoutRows().forEach(function (row) {
            const matches = row.dataset.search.includes(term)
                && (!statusFilter.value || row.dataset.status === statusFilter.value)
                && (!bankFilter.value || row.dataset.bank === bankFilter.value)
                && isInDateRange(row.dataset.date, dateFilter.value);
            row.classList.toggle('d-none', !matches);
            if (matches) visible++;
        });
        count.textContent = visible;
        empty.classList.toggle('d-none', visible !== 0);
    }

    [search, dateFilter, statusFilter, bankFilter].forEach(function (control) {
        control.addEventListener(control === search ? 'input' : 'change', filterPayouts);
    });

    document.getElementById('payout-reset').addEventListener('click', function () {
        search.value = '';
        dateFilter.value = '';
        statusFilter.value = '';
        bankFilter.value = '';
        filterPayouts();
    });

    const modal = new bootstrap.Modal(document.getElementById('requestPayoutModal'));
    const form = document.getElementById('requestPayoutForm');
    document.getElementById('request-payout-btn').addEventListener('click', function () {
        form.reset();
        modal.show();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const amount = Number(document.getElementById('request-payout-amount').value);
        const bankParts = document.getElementById('request-payout-bank').value.split('|');
        const reference = 'PAY-' + Math.floor(1000 + Math.random() * 9000);
        const today = new Date();
        const isoDate = today.toISOString().slice(0, 10);
        const displayDate = today.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        const row = document.createElement('tr');
        row.className = 'payout-row';
        row.dataset.search = (reference + ' ' + bankParts[0] + ' ' + bankParts[1] + ' pending').toLowerCase();
        row.dataset.date = isoDate;
        row.dataset.status = 'pending';
        row.dataset.bank = bankParts[0];
        row.innerHTML = '<td><span class="fw-semibold">#' + reference + '</span></td>'
            + '<td>' + displayDate + '</td>'
            + '<td>' + bankParts[0] + '</td>'
            + '<td>•••• ' + bankParts[1] + '</td>'
            + '<td class="text-end fw-bold">$' + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>'
            + '<td><span class="badge bg-label-warning">Pending</span></td><td>—</td>';
        rowsContainer.insertBefore(row, rowsContainer.firstChild);
        modal.hide();
        search.value = '';
        dateFilter.value = '';
        statusFilter.value = '';
        bankFilter.value = '';
        filterPayouts();
    });
});
</script>
@endsection
