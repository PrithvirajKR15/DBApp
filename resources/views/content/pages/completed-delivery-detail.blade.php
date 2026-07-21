@php
$isNavbar = true;
$ordersData = $ordersData ?? ['orders' => []];
$batchesData = $batchesData ?? ['batches' => [], 'drivers' => []];
$decodedId = urldecode($orderId);

$order = $order ?? collect($ordersData['orders'] ?? [])->first(function ($o) use ($orderId, $decodedId) {
    return strcasecmp($o['id'], $orderId) === 0 || strcasecmp($o['id'], $decodedId) === 0;
});

$batchId = $order['batch_id'] ?? null;
$deliveredAt = null;

if ($order) {
    foreach ($batchesData['batches'] ?? [] as $batch) {
        foreach ($batch['orders'] ?? [] as $batchOrder) {
            if (strcasecmp($batchOrder['id'], $order['id']) === 0) {
                $batchId = $batch['id'];
                $deliveredAt = $batchOrder['delivered_at'] ?? null;
                break 2;
            }
        }
    }
}

if (!$order) {
    $order = collect($ordersData['orders'] ?? [])->first(fn ($o) => ($o['delivery'] ?? '') === 'delivered')
        ?? ($ordersData['orders'][0] ?? null);
}
abort_unless($order, 404);

$fromBatch = request()->query('from') === 'batch';

// Driver record (full detail from fleet data when available)
$driver = $order['driver'] ?? ['name' => 'Michael Chen', 'id' => 'DRV-8492', 'avatar' => '5.png'];
$fleetDriver = collect($batchesData['drivers'] ?? [])->first(fn ($d) => $d['id'] === ($driver['id'] ?? ''));

$vehicleCode = $fleetDriver['vehicle'] ?? 'VAN-492A';
$vehicleNames = [
    'VAN' => 'Ford Transit Van (White)',
    'BIKE' => 'Honda CB Motorbike (Black)',
    'SCOOT' => 'Vespa Cargo Scooter (Grey)',
    'CAR' => 'Toyota Corolla (Silver)',
];
$vehiclePrefix = strtoupper(explode('-', $vehicleCode)[0]);
$vehicleName = $vehicleNames[$vehiclePrefix] ?? 'Delivery Vehicle';

$paymentLabels = [
    'online' => 'Visa ending in 4242',
    'cod' => 'Cash on Delivery',
    'apple' => 'Apple Pay',
];
$paymentIcons = [
    'online' => 'bx-credit-card',
    'cod' => 'bx-money',
    'apple' => 'bxl-apple',
];
$isCod = ($order['payment'] ?? 'online') === 'cod';

$detail = [
    'delivered_date' => 'Jul 17, 2026',
    'delivered_time' => $deliveredAt ?? '2:45 PM',
    'completion_time' => ($deliveredAt ?? '2:45 PM') . ' · Jul 17, 2026',
    'batch_number' => $batchId ?? ('BCH-' . substr(preg_replace('/\D/', '', $order['id']), 0, 3) . '-A'),
    'otp' => '1245',
    'rating' => 5,
    'feedback' => 'Great service!',
    'remarks' => 'Please leave at the front door and ring the doorbell once.',
    'duration' => '32 mins',
    'distance' => '4.2 mi',
];

$products = [
    ['name' => 'Organic Whole Milk', 'category' => 'Dairy Category', 'qty' => '2 Gallons', 'unit_price' => '$5.99', 'total' => 11.98, 'icon' => 'bx-coffee'],
    ['name' => 'Honeycrisp Apples', 'category' => 'Produce', 'qty' => '3 lbs', 'unit_price' => '$2.49 / lb', 'total' => 7.47, 'icon' => 'bx-leaf'],
    ['name' => 'Artisan Sourdough Loaf', 'category' => 'Bakery', 'qty' => '1 Loaf', 'unit_price' => '$6.50', 'total' => 6.50, 'icon' => 'bx-basket'],
    ['name' => 'Free-Range Eggs', 'category' => 'Dairy Category', 'qty' => '1 Dozen', 'unit_price' => '$4.25', 'total' => 4.25, 'icon' => 'bx-circle'],
];
$productsSubtotal = array_sum(array_column($products, 'total'));

$timeline = [
    ['label' => 'Delivered', 'time' => 'Jul 17, ' . ($deliveredAt ?? '2:45 PM'), 'state' => 'delivered'],
    ['label' => 'Out for Delivery', 'time' => 'Jul 17, 2:13 PM', 'state' => 'done'],
    ['label' => 'Order Picked Up', 'time' => 'Jul 17, 2:05 PM', 'state' => 'done'],
    ['label' => 'Order Prepared', 'time' => 'Jul 17, 1:30 PM', 'state' => 'done'],
];

$proofPhotos = ['1.jpg', '12.jpg', '13.jpg'];
$backUrl = $fromBatch ? url('/operations/delivery-batches') : url('/operations/orders');
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', 'Completed Delivery ' . $order['id'])
@section('page-title', 'Completed Delivery Details')

@section('content')
<style>
    .btn-primary-orange {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
        color: #ffffff !important;
        font-weight: 600;
    }
    .btn-primary-orange:hover {
        background-color: #e06b00 !important;
        border-color: #e06b00 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(255, 122, 0, 0.2) !important;
    }
    .back-link {
        color: #566a7f;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .back-link:hover { color: #ff7a00; }
    .detail-card {
        border: 1px solid #e0e2e7;
        border-radius: 12px;
        background: #fff;
    }
    .detail-card .card-head {
        padding: 14px 20px;
        border-bottom: 1px solid #eceef1;
    }
    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 50rem;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .chip-delivered-green { background: rgba(40,199,111,0.14); color: #28c76f; }
    .chip-delivered-green .dot { background: #28c76f; }
    .field-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #8592a3;
        display: block;
        margin-bottom: 4px;
    }
    .field-value { font-weight: 600; color: #566a7f; font-size: 0.95rem; }
    .paid-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(40,199,111,0.12);
        color: #28c76f;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 4px 10px;
        border-radius: 6px;
    }
    .remarks-box {
        background: #f8fafc;
        border: 1px solid #eceef1;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.88rem;
        color: #566a7f;
        font-style: italic;
    }
    .product-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #fff0e6;
        color: #ff7a00;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.1rem;
    }
    #completed-products-table th {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #8592a3;
        font-weight: 700;
    }
    .driver-role-badge {
        background: rgba(255,122,0,0.12);
        color: #ff7a00;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 6px;
    }
    .plate-chip {
        display: inline-block;
        background: #f1f3f5;
        border: 1px solid #e0e2e7;
        border-radius: 6px;
        padding: 3px 10px;
        font-family: monospace;
        font-weight: 700;
        font-size: 0.82rem;
        letter-spacing: 1.5px;
        color: #566a7f;
    }
    .stat-tile {
        background: #f8fafc;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        flex: 1;
    }
    .stat-tile .stat-label { font-size: 0.7rem; color: #8592a3; font-weight: 600; }
    .stat-tile .stat-value { font-size: 0.95rem; color: #566a7f; font-weight: 700; }
    .timeline {
        list-style: none;
        padding: 0;
        margin: 0;
        position: relative;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 8px;
        bottom: 8px;
        width: 2px;
        background: #e0e2e7;
    }
    .timeline-item {
        position: relative;
        padding-left: 36px;
        padding-bottom: 18px;
    }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-dot {
        position: absolute;
        left: 4px;
        top: 2px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #e0e2e7;
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px #e0e2e7;
    }
    .timeline-item.done .timeline-dot {
        background: #28c76f;
        box-shadow: 0 0 0 1px #28c76f;
    }
    .timeline-item.delivered .timeline-dot {
        background: #28c76f;
        box-shadow: 0 0 0 4px rgba(40,199,111,0.2);
    }
    .otp-verified {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #28c76f;
        font-weight: 600;
        font-size: 0.92rem;
    }
    .star-row { color: #ffab00; font-size: 1.05rem; letter-spacing: 1px; }
    .proof-photo {
        width: 88px;
        height: 88px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #e0e2e7;
        cursor: pointer;
        transition: transform 0.15s ease;
    }
    .proof-photo:hover { transform: scale(1.04); }
    .signature-box {
        background: #f8fafc;
        border: 1px solid #eceef1;
        border-radius: 10px;
        padding: 14px 16px;
        height: 100%;
    }
    .signature-canvas {
        background: #fff;
        border: 1px dashed #e0e2e7;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 120px;
    }
    @media print {
        .no-print { display: none !important; }
    }
</style>

<div class="completed-delivery-page">
<!-- Breadcrumb / Back -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2 no-print">
    <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.85rem;">
        <a href="{{ $backUrl }}" class="back-link"><i class="bx bx-arrow-back"></i> {{ $fromBatch ? 'Back to Batches' : 'Back to Orders' }}</a>
        <span class="mx-1">·</span>
        <span>Deliveries</span>
        <i class="bx bx-chevron-right"></i>
        <span class="text-body fw-semibold">Order #{{ $order['id'] }}</span>
    </div>
</div>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <h4 class="mb-0 fw-bold text-body">Completed Delivery Details</h4>
        <span class="status-chip chip-delivered-green"><span class="dot"></span>Delivered</span>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap no-print">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-print-report" style="border-radius: 8px; border-color: #e0e2e7; color: #566a7f;">
            <i class="bx bx-printer me-1"></i>Print Report
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-download-proof" style="border-radius: 8px; border-color: #e0e2e7; color: #566a7f;">
            <i class="bx bx-download me-1"></i>Download Proof
        </button>
        <a href="{{ $backUrl }}" class="btn btn-primary-orange btn-sm" style="border-radius: 8px;">
            {{ $fromBatch ? 'Back to Batches' : 'Back to Orders' }}
        </a>
    </div>
</div>

<!-- Summary strip -->
<div class="detail-card mb-4">
    <div class="p-3 p-md-4">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <span class="field-label">Order Number</span>
                <div class="fw-bold text-body" style="font-size: 1.05rem;">#{{ $order['id'] }}</div>
            </div>
            <div class="col-6 col-lg-3">
                <span class="field-label">Delivery Date &amp; Time</span>
                <div class="fw-bold text-body" style="font-size: 1.05rem;">{{ $detail['delivered_date'] }} · {{ $detail['delivered_time'] }}</div>
            </div>
            <div class="col-6 col-lg-3">
                <span class="field-label">Driver</span>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar" style="width: 28px; height: 28px;">
                        <img src="{{ asset('assets/img/avatars/'.($driver['avatar'] ?? '5.png')) }}" class="rounded-circle" alt="">
                    </div>
                    <span class="fw-bold text-body">{{ $driver['name'] }}</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <span class="field-label">Batch Number</span>
                <div class="fw-bold text-body" style="font-size: 1.05rem;">{{ $detail['batch_number'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Left column -->
    <div class="col-12 col-xl-8">
        <!-- Customer & Order Information -->
        <div class="detail-card mb-3">
            <div class="card-head">
                <h6 class="mb-0 fw-bold text-body">Customer &amp; Order Information</h6>
            </div>
            <div class="p-3 p-md-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <span class="field-label">Customer Name</span>
                        <div class="field-value">{{ $order['customer'] }}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Mobile Number</span>
                        <div class="field-value">{{ $order['phone'] ?? '+1 555-0100' }}</div>
                    </div>
                    <div class="col-12">
                        <span class="field-label">Delivery Address</span>
                        <div class="field-value"><i class="bx bx-map-pin" style="color: #ff7a00;"></i> {{ $order['address'] }}, {{ $order['area'] ?? '' }}</div>
                    </div>
                </div>
                <hr class="my-4" style="border-color: #eceef1;">
                <div class="row g-4">
                    <div class="col-md-6">
                        <span class="field-label">Store Name</span>
                        <div class="field-value">{{ $order['store'] }}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Order Value</span>
                        <div class="fw-bold" style="font-size: 1.15rem; color: #28c76f;">${{ number_format($order['value'], 2) }}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Payment Method</span>
                        <div class="field-value"><i class="bx {{ $paymentIcons[$order['payment']] ?? 'bx-credit-card' }}" style="color: #696cff;"></i> {{ $paymentLabels[$order['payment']] ?? 'Online Payment' }}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Payment Status</span>
                        <span class="paid-chip"><i class="bx bx-check"></i> {{ $isCod ? 'Collected on delivery' : 'Paid in full' }}</span>
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Amount Collected (COD)</span>
                        <div class="field-value {{ $isCod ? '' : 'text-muted' }}">${{ $isCod ? number_format($order['value'], 2) : '0.00' }}</div>
                    </div>
                    <div class="col-md-6">
                        <span class="field-label">Delivery Remarks</span>
                        <div class="remarks-box">&ldquo;{{ $detail['remarks'] }}&rdquo;</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ordered Products -->
        <div class="detail-card mb-3">
            <div class="card-head d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold text-body">Ordered Products</h6>
                <small class="text-muted">{{ count($products) }} items</small>
            </div>
            <div class="table-responsive">
                <table class="table mb-0" id="completed-products-table">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $p)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="product-thumb"><i class="bx {{ $p['icon'] }}"></i></div>
                                    <div>
                                        <div class="fw-semibold text-body" style="font-size: 0.9rem;">{{ $p['name'] }}</div>
                                        <small class="text-muted">{{ $p['category'] }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-body">{{ $p['qty'] }}</td>
                            <td class="text-muted">{{ $p['unit_price'] }}</td>
                            <td class="fw-semibold text-end">${{ number_format($p['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top d-flex align-items-center justify-content-between">
                <span class="fw-bold text-body">Subtotal ({{ count($products) }} items)</span>
                <span class="fw-bold text-body" style="font-size: 1.1rem;">${{ number_format($productsSubtotal, 2) }}</span>
            </div>
        </div>

        <!-- Delivery Verification -->
        <div class="detail-card mb-3">
            <div class="card-head">
                <h6 class="mb-0 fw-bold text-body">Delivery Verification</h6>
            </div>
            <div class="p-3 p-md-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <span class="field-label">OTP Verification</span>
                            <span class="otp-verified"><i class="bx bxs-check-shield"></i> Verified Successfully ({{ $detail['otp'] }})</span>
                        </div>
                        <div class="mb-4">
                            <span class="field-label">Completion Time</span>
                            <div class="field-value">{{ $detail['completion_time'] }}</div>
                        </div>
                        <div class="mb-4">
                            <span class="field-label">Customer Feedback</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="star-row">
                                    @for ($i = 1; $i <= 5; $i++)
                                    <i class="bx {{ $i <= $detail['rating'] ? 'bxs-star' : 'bx-star' }}"></i>
                                    @endfor
                                </span>
                                <span class="fw-semibold text-body" style="font-size: 0.88rem;">{{ number_format($detail['rating'], 1) }} ({{ $detail['feedback'] }})</span>
                            </div>
                        </div>
                        <div>
                            <span class="field-label">Delivery Proof Photos</span>
                            <div class="d-flex gap-2 flex-wrap mt-1">
                                @foreach ($proofPhotos as $photo)
                                <img src="{{ asset('assets/img/elements/'.$photo) }}" class="proof-photo" alt="Delivery proof photo">
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="signature-box">
                            <span class="field-label">Customer Signature</span>
                            <div class="signature-canvas mt-2">
                                <svg width="200" height="80" viewBox="0 0 200 80" fill="none">
                                    <path d="M15 55 C 30 20, 45 20, 55 45 C 62 62, 70 60, 78 40 C 85 24, 95 22, 102 42 C 108 58, 118 56, 128 38 C 136 24, 150 26, 158 44 C 163 55, 175 52, 185 40"
                                          stroke="#566a7f" stroke-width="2.2" stroke-linecap="round" fill="none"/>
                                    <path d="M20 66 L 180 66" stroke="#e0e2e7" stroke-width="1.5" stroke-dasharray="4 4"/>
                                </svg>
                            </div>
                            <small class="text-muted d-block mt-2 text-center">Signed by {{ $order['customer'] }} at {{ $detail['delivered_time'] }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-12 col-xl-4">
        <!-- Assigned Driver -->
        <div class="detail-card mb-3">
            <div class="card-head">
                <h6 class="mb-0 fw-bold text-body">Assigned Driver</h6>
            </div>
            <div class="p-3 p-md-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar" style="width: 52px; height: 52px;">
                        <img src="{{ asset('assets/img/avatars/'.($driver['avatar'] ?? '5.png')) }}" class="rounded-circle" alt="">
                    </div>
                    <div>
                        <div class="fw-bold text-body">{{ $driver['name'] }}</div>
                        <span class="driver-role-badge">{{ ($fleetDriver['type'] ?? 'store') === 'store' ? 'Full-Time Staff' : 'Zone Driver' }}</span>
                    </div>
                </div>
                <hr style="border-color: #eceef1;">
                <span class="field-label">Vehicle Details</span>
                <div class="field-value mb-2">{{ $vehicleName }}</div>
                <span class="plate-chip mb-3 d-inline-block">{{ $vehicleCode }}</span>
                <div class="d-flex gap-2 mt-2 mb-3">
                    <div class="stat-tile">
                        <div class="stat-label">Duration</div>
                        <div class="stat-value">{{ $detail['duration'] }}</div>
                    </div>
                    <div class="stat-tile">
                        <div class="stat-label">Distance</div>
                        <div class="stat-value">{{ $detail['distance'] }}</div>
                    </div>
                </div>
                <a href="{{ url('/fleet/drivers/'.($driver['id'] ?? 'DRV-8492').'/profile') }}" class="btn btn-outline-secondary w-100 no-print" style="border-radius: 8px; border-color: #e0e2e7; color: #566a7f;">
                    View Driver Profile
                </a>
            </div>
        </div>

        <!-- Delivery Timeline -->
        <div class="detail-card mb-3">
            <div class="card-head">
                <h6 class="mb-0 fw-bold text-body">Delivery Timeline</h6>
            </div>
            <div class="p-3 p-md-4">
                <ul class="timeline">
                    @foreach ($timeline as $step)
                    <li class="timeline-item {{ $step['state'] }}">
                        <div class="timeline-dot"></div>
                        <div class="fw-semibold text-body" style="font-size: 0.9rem;">{{ $step['label'] }}</div>
                        <small class="text-muted">{{ $step['time'] }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
</div><!-- /.completed-delivery-page -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('btn-print-report')?.addEventListener('click', function () {
        window.print();
    });

    document.getElementById('btn-download-proof')?.addEventListener('click', function () {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Proof of Delivery',
                text: 'Proof of delivery for #{{ $order['id'] }} is being prepared for download.',
                confirmButtonColor: '#ff7a00'
            });
        } else {
            alert('Proof of delivery for #{{ $order['id'] }} is being prepared for download.');
        }
    });
});
</script>
@endsection
