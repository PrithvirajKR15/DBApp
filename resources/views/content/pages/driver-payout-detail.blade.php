@extends('layouts/contentNavbarLayout')

@section('title', $driver['name'] . ' Payouts')
@section('page-title', 'Driver Payout Details')

@php
    $history = $driver['history'];
    $historyOrderCount = collect($history)->sum(fn ($payout) => count($payout['orders']));
@endphp

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('operations-payouts') }}" class="btn btn-icon btn-outline-secondary">
            <i class="bx bx-arrow-back"></i>
        </a>
        <div class="avatar avatar-lg">
            <span class="avatar-initial rounded-circle bg-label-primary fw-bold">{{ $driver['avatar'] }}</span>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-bold">{{ $driver['name'] }}</h4>
                <span class="badge bg-label-{{ $driver['status'] === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($driver['status']) }}</span>
            </div>
            <p class="text-muted mb-0">{{ $driver['id'] }} · {{ ucfirst($driver['type']) }} driver · {{ $driver['zone'] }}</p>
        </div>
    </div>
    <div class="text-end">
        <small class="text-muted d-block">Contact</small>
        <span class="fw-semibold">{{ $driver['phone'] }}</span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <small class="text-muted fw-semibold">Lifetime Paid</small>
                <h3 class="fw-bold text-success mb-1 mt-2">${{ number_format($driver['lifetime_paid'], 2) }}</h3>
                <small class="text-muted">All completed payouts</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <small class="text-muted fw-semibold">Pending Amount</small>
                <h3 class="fw-bold text-warning mb-1 mt-2">${{ number_format($driver['pending_amount'], 2) }}</h3>
                <small class="text-muted">Not yet paid</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <small class="text-muted fw-semibold">Paid Orders</small>
                <h3 class="fw-bold mb-1 mt-2">{{ number_format($driver['paid_orders']) }}</h3>
                <small class="text-muted">{{ $historyOrderCount }} shown in recent history</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card shadow-none border h-100" style="border-radius: 12px;">
            <div class="card-body">
                <small class="text-muted fw-semibold">Last Payout</small>
                <h5 class="fw-bold mb-1 mt-2">{{ date('M d, Y', strtotime($driver['last_payout_at'])) }}</h5>
                <small class="text-muted">{{ date('h:i A', strtotime($driver['last_payout_at'])) }}</small>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h5 class="fw-bold mb-1">Payout History</h5>
        <p class="text-muted mb-0">Each payout includes its order-level earning breakdown.</p>
    </div>
    <div class="input-group input-group-merge border rounded" style="width: min(100%, 320px);">
        <span class="input-group-text border-0 bg-transparent"><i class="bx bx-search text-muted"></i></span>
        <input type="search" class="form-control border-0" id="driver-payout-search" placeholder="Payout, reference, or order...">
    </div>
</div>

<div class="d-flex flex-column gap-3" id="driver-payout-history">
    @foreach ($history as $index => $payout)
        @php
            $payoutTotal = collect($payout['orders'])->sum('net');
            $searchText = strtolower($payout['id'] . ' ' . $payout['reference'] . ' ' . collect($payout['orders'])->pluck('order_id')->implode(' '));
        @endphp
        <div class="card shadow-none border driver-payout-record" data-search="{{ $searchText }}" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-transparent p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold text-body">#{{ $payout['id'] }}</span>
                            <span class="badge bg-label-success">{{ ucfirst($payout['status']) }}</span>
                        </div>
                        <small class="text-muted">{{ date('M d, Y · h:i A', strtotime($payout['paid_at'])) }} · {{ $payout['period'] }}</small>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div class="text-end">
                            <small class="text-muted d-block">{{ count($payout['orders']) }} orders</small>
                            <span class="fw-bold text-success">${{ number_format($payoutTotal, 2) }}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#payout-orders-{{ $index }}">
                            Order details <i class="bx bx-chevron-down ms-1"></i>
                        </button>
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-4"><small class="text-muted">Method</small><div class="fw-semibold">{{ $payout['method'] }}</div></div>
                    <div class="col-md-4"><small class="text-muted">Reference</small><div class="fw-semibold">{{ $payout['reference'] }}</div></div>
                    <div class="col-md-4"><small class="text-muted">Paid at</small><div class="fw-semibold">{{ date('M d, Y h:i A', strtotime($payout['paid_at'])) }}</div></div>
                </div>
            </div>
            <div class="collapse {{ $index === 0 ? 'show' : '' }}" id="payout-orders-{{ $index }}">
                <div class="table-responsive border-top">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr class="table-light">
                                <th>Order</th>
                                <th>Delivered At</th>
                                <th class="text-end">Delivery Fee</th>
                                <th class="text-end">Bonus</th>
                                <th class="text-end">Deduction</th>
                                <th class="text-end">Paid Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payout['orders'] as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('operations-orders-detail', ['id' => $order['order_id']]) }}" class="fw-semibold">
                                            #{{ $order['order_id'] }}
                                        </a>
                                    </td>
                                    <td>{{ date('M d, Y · h:i A', strtotime($order['delivered_at'])) }}</td>
                                    <td class="text-end">${{ number_format($order['delivery_fee'], 2) }}</td>
                                    <td class="text-end text-success">{{ $order['bonus'] > 0 ? '+$' . number_format($order['bonus'], 2) : '—' }}</td>
                                    <td class="text-end text-danger">{{ $order['deduction'] > 0 ? '-$' . number_format($order['deduction'], 2) : '—' }}</td>
                                    <td class="text-end fw-bold">${{ number_format($order['net'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card shadow-none border d-none" id="driver-payout-empty" style="border-radius: 12px;">
    <div class="card-body text-center py-5">
        <i class="bx bx-search-alt fs-1 text-muted"></i>
        <p class="fw-semibold mb-0 mt-2">No payout records found</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('driver-payout-search');
    const records = Array.from(document.querySelectorAll('.driver-payout-record'));
    const empty = document.getElementById('driver-payout-empty');

    search.addEventListener('input', function () {
        const term = search.value.trim().toLowerCase();
        let visible = 0;
        records.forEach(function (record) {
            const matches = record.dataset.search.includes(term);
            record.classList.toggle('d-none', !matches);
            if (matches) visible++;
        });
        empty.classList.toggle('d-none', visible !== 0);
    });
});
</script>
@endsection
