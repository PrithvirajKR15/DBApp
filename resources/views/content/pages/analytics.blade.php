@extends('layouts/contentNavbarLayout')

@section('title', 'Analytics')
@section('page-title', 'Fleet Analytics & Reports')

@section('content')
<!-- KPI Cards -->
<div class="row">
    <!-- Success Rate -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Success Rate</span>
                    <span class="badge bg-label-success rounded-pill">+0.5%</span>
                </div>
                <h3 class="fw-bold mb-1" style="color: #32475c;">98.4%</h3>
                <small class="text-muted">Target: 99.0%</small>
                <!-- SVG Mini Line -->
                <div class="mt-3" style="height: 40px;">
                    <svg viewBox="0 0 200 40" class="w-100 h-100">
                        <path d="M0,30 L30,25 L60,28 L90,15 L120,20 L150,8 L180,10 L200,5" fill="none" stroke="#28c76f" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <!-- Avg Delivery Time -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Avg. Delivery Time</span>
                    <span class="badge bg-label-primary rounded-pill">-1.2m</span>
                </div>
                <h3 class="fw-bold mb-1" style="color: #32475c;">24.2 mins</h3>
                <small class="text-muted">Target: &lt; 30 mins</small>
                <!-- SVG Mini Line -->
                <div class="mt-3" style="height: 40px;">
                    <svg viewBox="0 0 200 40" class="w-100 h-100">
                        <path d="M0,20 L30,22 L60,18 L90,25 L120,15 L150,18 L180,12 L200,8" fill="none" stroke="#007bff" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <!-- Fleet Utilization -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Fleet Utilization</span>
                    <span class="badge bg-label-warning rounded-pill">+3.1%</span>
                </div>
                <h3 class="fw-bold mb-1" style="color: #32475c;">82.1%</h3>
                <small class="text-muted">Active drivers: 342 / 416</small>
                <!-- SVG Mini Line -->
                <div class="mt-3" style="height: 40px;">
                    <svg viewBox="0 0 200 40" class="w-100 h-100">
                        <path d="M0,35 L30,30 L60,25 L90,22 L120,20 L150,15 L180,18 L200,10" fill="none" stroke="#ffab00" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <!-- Total Distance Covered -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Distance Covered</span>
                    <span class="badge bg-label-info rounded-pill">+14.2%</span>
                </div>
                <h3 class="fw-bold mb-1" style="color: #32475c;">1,849 mi</h3>
                <small class="text-muted">Today's total mileage</small>
                <!-- SVG Mini Line -->
                <div class="mt-3" style="height: 40px;">
                    <svg viewBox="0 0 200 40" class="w-100 h-100">
                        <path d="M0,28 L30,22 L60,25 L90,20 L120,18 L150,12 L180,8 L200,3" fill="none" stroke="#03c3ec" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Analytics Charts -->
<div class="row">
    <!-- Monthly Delivery Volume Bar Chart -->
    <div class="col-md-8 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-header pb-0 bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body">Monthly Delivery Volume</h5>
                <select class="form-select form-select-sm" style="width: 120px;">
                    <option value="2026">Year 2026</option>
                    <option value="2025">Year 2025</option>
                </select>
            </div>
            <div class="card-body py-4">
                <div style="height: 260px;">
                    <svg viewBox="0 0 600 240" class="w-100 h-100">
                        <!-- Horizontal Grid Lines -->
                        <line x1="40" y1="20" x2="580" y2="20" stroke="#f1f3f5" stroke-width="1"/>
                        <line x1="40" y1="70" x2="580" y2="70" stroke="#f1f3f5" stroke-width="1"/>
                        <line x1="40" y1="120" x2="580" y2="120" stroke="#f1f3f5" stroke-width="1"/>
                        <line x1="40" y1="170" x2="580" y2="170" stroke="#f1f3f5" stroke-width="1"/>
                        <line x1="40" y1="210" x2="580" y2="210" stroke="#ced4da" stroke-width="1"/>

                        <!-- Monthly Bar Graphs (Jan - Jun) -->
                        <!-- Jan -->
                        <rect x="75" y="100" width="30" height="110" rx="4" fill="#696cff"/>
                        <text x="90" y="228" fill="#adb5bd" font-size="10" text-anchor="middle">Jan</text>
                        
                        <!-- Feb -->
                        <rect x="155" y="80" width="30" height="130" rx="4" fill="#696cff"/>
                        <text x="170" y="228" fill="#adb5bd" font-size="10" text-anchor="middle">Feb</text>

                        <!-- Mar -->
                        <rect x="235" y="50" width="30" height="160" rx="4" fill="#696cff"/>
                        <text x="250" y="228" fill="#adb5bd" font-size="10" text-anchor="middle">Mar</text>

                        <!-- Apr -->
                        <rect x="315" y="70" width="30" height="140" rx="4" fill="#696cff"/>
                        <text x="330" y="228" fill="#adb5bd" font-size="10" text-anchor="middle">Apr</text>

                        <!-- May -->
                        <rect x="395" y="30" width="30" height="180" rx="4" fill="#696cff"/>
                        <text x="410" y="228" fill="#adb5bd" font-size="10" text-anchor="middle">May</text>

                        <!-- Jun -->
                        <rect x="475" y="40" width="30" height="170" rx="4" fill="#696cff"/>
                        <text x="490" y="228" fill="#adb5bd" font-size="10" text-anchor="middle">Jun</text>

                        <!-- Y Axis labels -->
                        <text x="25" y="25" fill="#adb5bd" font-size="9" text-anchor="middle">15k</text>
                        <text x="25" y="125" fill="#adb5bd" font-size="9" text-anchor="middle">7.5k</text>
                        <text x="25" y="215" fill="#adb5bd" font-size="9" text-anchor="middle">0</text>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicles Shares Donut Pie Chart -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-header pb-0 bg-transparent border-0">
                <h5 class="mb-0 fw-bold text-body">Vehicle Share</h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-between py-4">
                <div class="d-flex align-items-center justify-content-center" style="height: 180px;">
                    <svg viewBox="0 0 160 160" width="160" height="160">
                        <!-- Scooter: 50% -->
                        <circle cx="80" cy="80" r="60" fill="none" stroke="#28c76f" stroke-width="20" stroke-dasharray="188.4 376.8" stroke-dashoffset="0" />
                        <!-- Car: 30% -->
                        <circle cx="80" cy="80" r="60" fill="none" stroke="#007bff" stroke-width="20" stroke-dasharray="113.04 376.8" stroke-dashoffset="-188.4" />
                        <!-- Auto Rickshaw: 20% -->
                        <circle cx="80" cy="80" r="60" fill="none" stroke="#ffab00" stroke-width="20" stroke-dasharray="75.36 376.8" stroke-dashoffset="-301.44" />
                    </svg>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted" style="font-size: 0.85rem;"><i class="icon-base bx bx-circle me-2 text-success"></i> Scooter</span>
                        <span class="fw-bold">50%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted" style="font-size: 0.85rem;"><i class="icon-base bx bx-circle me-2 text-primary"></i> Car</span>
                        <span class="fw-bold">30%</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted" style="font-size: 0.85rem;"><i class="icon-base bx bx-circle me-2 text-warning"></i> Auto Rickshaw</span>
                        <span class="fw-bold">20%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
