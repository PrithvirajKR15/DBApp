@extends('layouts/contentNavbarLayout')

@section('title', 'Earnings')
@section('page-title', 'Earnings')

@php
    $isNavbar = false;
    $data = $data ?? [];
    $metrics = $data['metrics'] ?? [];
    $orderEarnings = $data['order_earnings'] ?? [];
    $transactions = $data['transactions'] ?? [];

    $typeLabels = [
        'order_sale' => 'Order Sale',
        'delivery_fee' => 'Delivery Fee',
        'refund' => 'Refund',
        'adjustment' => 'Adjustment',
    ];
    $typeBadges = [
        'order_sale' => 'primary',
        'delivery_fee' => 'info',
        'refund' => 'warning',
        'adjustment' => 'secondary',
    ];
    $statusBadges = [
        'succeeded' => 'success',
        'pending' => 'warning',
        'failed' => 'danger',
    ];
@endphp

@section('content')
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Earnings</h4>
        <p class="text-muted mb-0">Review revenue, fees, refunds, and adjustments from completed orders.</p>
    </div>
    <a href="{{ route('operations-payouts') }}" class="btn btn-outline-primary d-flex align-items-center gap-2">
        <i class="bx bx-transfer-alt"></i>
        View Payouts
    </a>
</div>
<div class="d-flex align-items-center gap-2 mb-3" id="earnings-view-tabs">
    <button type="button" class="btn btn-primary earnings-view-tab" data-view="transactions">
        <i class="bx bx-list-ul me-1"></i>Transactions
    </button>
    <button type="button" class="btn btn-outline-secondary earnings-view-tab" data-view="orders">
        <i class="bx bx-package me-1"></i>Order Earnings
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Gross Revenue</span>
                    <i class="bx bx-line-chart text-primary fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1">${{ number_format($metrics['gross_revenue'], 2) }}</h3>
                <small class="text-success fw-semibold"><i class="bx bx-chevron-up"></i> {{ $metrics['change'] }}% this month</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Delivery Fees</span>
                    <i class="bx bx-cycling text-info fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1">${{ number_format($metrics['delivery_fees'], 2) }}</h3>
                <small class="text-muted">Driver delivery earnings</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Refunds</span>
                    <i class="bx bx-undo text-danger fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1 text-danger">${{ number_format($metrics['refunds'], 2) }}</h3>
                <small class="text-muted">Deducted this month</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Net Earnings</span>
                    <i class="bx bx-wallet text-success fs-3"></i>
                </div>
                <h3 class="fw-bold mb-1 text-success">${{ number_format($metrics['net_earnings'], 2) }}</h3>
                <small class="text-muted">After refunds and adjustments</small>
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
                    <input type="search" class="form-control border-0" id="earnings-search" placeholder="Transaction, order, or driver...">
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="earnings-date">
                    <option value="">All dates</option>
                    <option value="today">Today</option>
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2" id="earnings-type-wrap">
                <select class="form-select" id="earnings-type">
                    <option value="">All types</option>
                    @foreach ($typeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <select class="form-select" id="earnings-status">
                    <option value="">All statuses</option>
                    <option value="succeeded">Succeeded</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <button type="button" class="btn btn-outline-secondary w-100" id="earnings-reset">
                    <i class="bx bx-reset me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted"><span class="fw-semibold text-body" id="earnings-count">{{ count($transactions) }}</span> <span id="earnings-count-label">transactions</span></span>
</div>

<div class="card shadow-none border" id="transactions-view" style="border-radius: 12px; overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr class="table-light">
                    <th>Transaction</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Order</th>
                    <th>Driver</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="earnings-rows">
                @foreach ($transactions as $transaction)
                    @php
                        $search = strtolower(implode(' ', array_filter([
                            $transaction['id'],
                            $transaction['order_id'],
                            $transaction['driver'],
                            $typeLabels[$transaction['type']],
                            $transaction['status'],
                        ])));
                    @endphp
                    <tr class="earnings-row"
                        data-search="{{ $search }}"
                        data-date="{{ date('Y-m-d', strtotime($transaction['date'])) }}"
                        data-type="{{ $transaction['type'] }}"
                        data-status="{{ $transaction['status'] }}">
                        <td><span class="fw-semibold">#{{ $transaction['id'] }}</span></td>
                        <td>
                            <span class="d-block">{{ date('M d, Y', strtotime($transaction['date'])) }}</span>
                            <small class="text-muted">{{ date('h:i A', strtotime($transaction['date'])) }}</small>
                        </td>
                        <td><span class="badge bg-label-{{ $typeBadges[$transaction['type']] }}">{{ $typeLabels[$transaction['type']] }}</span></td>
                        <td>{{ $transaction['order_id'] ? '#' . $transaction['order_id'] : '—' }}</td>
                        <td>{{ $transaction['driver'] }}</td>
                        <td class="text-end fw-bold {{ $transaction['amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $transaction['amount'] >= 0 ? '+' : '-' }}${{ number_format(abs($transaction['amount']), 2) }}
                        </td>
                        <td><span class="badge bg-label-{{ $statusBadges[$transaction['status']] }}">{{ ucfirst($transaction['status']) }}</span></td>
                    </tr>
                @endforeach
                <tr id="earnings-empty" class="d-none">
                    <td colspan="7" class="text-center py-5">
                        <i class="bx bx-search-alt fs-1 text-muted"></i>
                        <p class="mb-0 mt-2 fw-semibold">No transactions found</p>
                        <small class="text-muted">Try changing or resetting the filters.</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-none border d-none" id="orders-view" style="border-radius: 12px; overflow: hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr class="table-light">
                    <th>Order</th>
                    <th>Date</th>
                    <th>Store / Customer</th>
                    <th>Driver</th>
                    <th class="text-end">Order Amount</th>
                    <th class="text-end">Delivery Fee</th>
                    <th class="text-end">Refund</th>
                    <th class="text-end">Net Earning</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orderEarnings as $order)
                    @php
                        $orderSearch = strtolower(implode(' ', [
                            $order['order_id'],
                            $order['store'],
                            $order['customer'],
                            $order['driver'],
                            $order['status'],
                        ]));
                    @endphp
                    <tr class="order-earning-row"
                        data-search="{{ $orderSearch }}"
                        data-date="{{ date('Y-m-d', strtotime($order['date'])) }}"
                        data-status="{{ $order['status'] }}">
                        <td><span class="fw-semibold text-primary">#{{ $order['order_id'] }}</span></td>
                        <td>
                            <span class="d-block">{{ date('M d, Y', strtotime($order['date'])) }}</span>
                            <small class="text-muted">{{ date('h:i A', strtotime($order['date'])) }}</small>
                        </td>
                        <td>
                            <span class="d-block fw-semibold">{{ $order['store'] }}</span>
                            <small class="text-muted">{{ $order['customer'] }}</small>
                        </td>
                        <td>{{ $order['driver'] }}</td>
                        <td class="text-end">${{ number_format($order['order_amount'], 2) }}</td>
                        <td class="text-end text-info">+${{ number_format($order['delivery_fee'], 2) }}</td>
                        <td class="text-end {{ $order['refund'] > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $order['refund'] > 0 ? '-$' . number_format($order['refund'], 2) : '—' }}
                        </td>
                        <td class="text-end fw-bold text-success">${{ number_format($order['net_earning'], 2) }}</td>
                        <td><span class="badge bg-label-{{ $statusBadges[$order['status']] }}">{{ ucfirst($order['status']) }}</span></td>
                    </tr>
                @endforeach
                <tr id="order-earnings-empty" class="d-none">
                    <td colspan="9" class="text-center py-5">
                        <i class="bx bx-search-alt fs-1 text-muted"></i>
                        <p class="mb-0 mt-2 fw-semibold">No order earnings found</p>
                        <small class="text-muted">Try changing or resetting the filters.</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('earnings-search');
    const dateFilter = document.getElementById('earnings-date');
    const typeFilter = document.getElementById('earnings-type');
    const statusFilter = document.getElementById('earnings-status');
    const transactionRows = Array.from(document.querySelectorAll('.earnings-row'));
    const orderRows = Array.from(document.querySelectorAll('.order-earning-row'));
    const count = document.getElementById('earnings-count');
    const countLabel = document.getElementById('earnings-count-label');
    const empty = document.getElementById('earnings-empty');
    const orderEmpty = document.getElementById('order-earnings-empty');
    const typeWrap = document.getElementById('earnings-type-wrap');
    let activeView = 'transactions';

    function isInDateRange(value, range) {
        if (!range) return true;
        const rowDate = new Date(value + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (range === 'today') return rowDate.getTime() === today.getTime();
        const start = new Date(today);
        start.setDate(start.getDate() - (Number(range) - 1));
        return rowDate >= start && rowDate <= today;
    }

    function filterEarnings() {
        const term = search.value.trim().toLowerCase();
        const rows = activeView === 'transactions' ? transactionRows : orderRows;
        let visible = 0;
        rows.forEach(function (row) {
            const matches = row.dataset.search.includes(term)
                && (activeView === 'orders' || !typeFilter.value || row.dataset.type === typeFilter.value)
                && (!statusFilter.value || row.dataset.status === statusFilter.value)
                && isInDateRange(row.dataset.date, dateFilter.value);
            row.classList.toggle('d-none', !matches);
            if (matches) visible++;
        });
        count.textContent = visible;
        countLabel.textContent = activeView === 'transactions' ? 'transactions' : 'orders';
        empty.classList.toggle('d-none', activeView !== 'transactions' || visible !== 0);
        orderEmpty.classList.toggle('d-none', activeView !== 'orders' || visible !== 0);
    }

    [search, dateFilter, typeFilter, statusFilter].forEach(function (control) {
        control.addEventListener(control === search ? 'input' : 'change', filterEarnings);
    });

    document.getElementById('earnings-reset').addEventListener('click', function () {
        search.value = '';
        dateFilter.value = '';
        typeFilter.value = '';
        statusFilter.value = '';
        filterEarnings();
    });

    document.querySelectorAll('.earnings-view-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            activeView = tab.dataset.view;
            document.getElementById('transactions-view').classList.toggle('d-none', activeView !== 'transactions');
            document.getElementById('orders-view').classList.toggle('d-none', activeView !== 'orders');
            typeWrap.classList.toggle('d-none', activeView === 'orders');
            search.placeholder = activeView === 'transactions'
                ? 'Transaction, order, or driver...'
                : 'Order, store, customer, or driver...';
            document.querySelectorAll('.earnings-view-tab').forEach(function (button) {
                const isActive = button === tab;
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-outline-secondary', !isActive);
            });
            filterEarnings();
        });
    });
});
</script>
@endsection
