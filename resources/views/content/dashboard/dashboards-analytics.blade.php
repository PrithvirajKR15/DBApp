@extends('layouts/contentNavbarLayout')

@section('title', 'Delivery Management Dashboard')

@section('content')
<div class="row">
    <!-- Filters Row -->
    <div class="col-12 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="btn-group" role="group" aria-label="Time Filter">
                <button type="button" class="btn btn-white border shadow-sm px-3 active-filter-btn" id="filter-today">Today</button>
                <button type="button" class="btn btn-white border shadow-sm px-3 text-muted" id="filter-yesterday">Yesterday</button>
                <button type="button" class="btn btn-white border shadow-sm px-3 text-muted" id="filter-week">This Week</button>
            </div>
            <div>
                <button type="button" class="btn btn-white border shadow-sm d-flex align-items-center gap-2" id="filter-custom">
                    <i class="icon-base bx bx-calendar text-muted"></i>
                    <span class="text-body" style="font-size: 0.85rem;">Custom Date</span>
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <!-- Card 1: Active Orders -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold" style="font-size: 0.9rem;">Active Orders</span>
                    <span class="avatar rounded p-2" style="background: rgba(105, 108, 255, 0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="icon-base bx bx-package text-primary fs-4"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline mb-2">
                    <h3 class="mb-0 fw-bold me-2" style="font-size: 1.8rem; color: #32475c;">1,248</h3>
                    <span class="text-success fw-semibold d-flex align-items-center" style="font-size: 0.85rem;">
                        <i class="icon-base bx bx-trending-up me-1"></i> 12%
                    </span>
                </div>
                <div class="text-muted mb-3" style="font-size: 0.75rem;">vs yesterday</div>
                <div class="d-flex justify-content-between pt-3 border-top border-light">
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">In Transit</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #32475c;">425</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">Delivered</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #32475c;">823</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Active Drivers -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold" style="font-size: 0.9rem;">Active Drivers</span>
                    <span class="avatar rounded p-2" style="background: rgba(113, 221, 55, 0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="icon-base bx bx-cycling text-success fs-4"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline mb-2">
                    <h3 class="mb-0 fw-bold me-2" style="font-size: 1.8rem; color: #32475c;">342</h3>
                    <span class="text-success fw-semibold d-flex align-items-center" style="font-size: 0.85rem;">
                        <i class="icon-base bx bx-trending-up me-1"></i> 5%
                    </span>
                </div>
                <div class="text-muted mb-3" style="font-size: 0.75rem;">vs yesterday</div>
                <div class="d-flex justify-content-between pt-3 border-top border-light">
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">Online</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #32475c;">342</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">Pending Appr.</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #32475c;">12</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Total Earnings -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold" style="font-size: 0.9rem;">Total Earnings</span>
                    <span class="avatar rounded p-2" style="background: rgba(224, 76, 238, 0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="icon-base bx bx-wallet text-secondary fs-4" style="color: #e04cee !important;"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline mb-2">
                    <h3 class="mb-0 fw-bold me-2" style="font-size: 1.8rem; color: #32475c;">$14,590</h3>
                    <span class="text-success fw-semibold d-flex align-items-center" style="font-size: 0.85rem;">
                        <i class="icon-base bx bx-trending-up me-1"></i> 8.4%
                    </span>
                </div>
                <div class="text-muted mb-3" style="font-size: 0.75rem;">vs yesterday</div>
                <div class="d-flex justify-content-between pt-3 border-top border-light">
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">Settled</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #32475c;">$12K</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">Pending COD</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #ffab00;">$2.5K</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Failed Deliveries -->
    <div class="col-sm-6 col-lg-3 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold" style="font-size: 0.9rem;">Failed Deliveries</span>
                    <span class="avatar rounded p-2" style="background: rgba(255, 62, 29, 0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="icon-base bx bx-error-alt text-danger fs-4"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline mb-2">
                    <h3 class="mb-0 fw-bold me-2" style="font-size: 1.8rem; color: #32475c;">18</h3>
                    <span class="text-danger fw-semibold d-flex align-items-center" style="font-size: 0.85rem;">
                        <i class="icon-base bx bx-trending-down me-1"></i> 2%
                    </span>
                </div>
                <div class="text-muted mb-3" style="font-size: 0.75rem;">vs yesterday</div>
                <div class="d-flex justify-content-between pt-3 border-top border-light">
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">Customer Unavail.</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #32475c;">12</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;">Address Issue</div>
                        <div class="fw-bold" style="font-size: 0.9rem; color: #32475c;">6</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Row: Hourly Delivery Trends & Deliveries by Area -->
<div class="row">
    <!-- Hourly Delivery Trends Card -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-header d-flex align-items-center justify-content-between pb-0 bg-transparent border-0">
                <div class="card-title mb-0">
                    <h5 class="m-0 fw-bold text-body" style="font-size: 1.1rem;">Hourly Delivery Trends</h5>
                </div>
                <div class="dropdown">
                    <button class="btn p-0" type="button" id="deliveryTrendsOpt" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base bx bx-dots-horizontal-rounded"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="deliveryTrendsOpt">
                        <a class="dropdown-item" href="javascript:void(0);">Last 24 Hours</a>
                        <a class="dropdown-item" href="javascript:void(0);">Last 7 Days</a>
                        <a class="dropdown-item" href="javascript:void(0);">Export Chart</a>
                    </div>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <!-- SVG Interactive Chart representing Completed and In Transit trends -->
                <div class="position-relative mt-4" style="height: 280px;">
                    <svg viewBox="0 0 800 240" class="w-100 h-100">
                        <!-- Grid Lines -->
                        <line x1="50" y1="20" x2="780" y2="20" stroke="#f1f3f5" stroke-width="1" />
                        <line x1="50" y1="70" x2="780" y2="70" stroke="#f1f3f5" stroke-width="1" />
                        <line x1="50" y1="120" x2="780" y2="120" stroke="#f1f3f5" stroke-width="1" />
                        <line x1="50" y1="170" x2="780" y2="170" stroke="#f1f3f5" stroke-width="1" />
                        <line x1="50" y1="220" x2="780" y2="220" stroke="#ced4da" stroke-width="1" />
                        
                        <!-- Y-Axis Labels -->
                        <text x="25" y="25" fill="#adb5bd" font-size="11" text-anchor="middle">100</text>
                        <text x="25" y="125" fill="#adb5bd" font-size="11" text-anchor="middle">50</text>
                        <text x="25" y="225" fill="#adb5bd" font-size="11" text-anchor="middle">0</text>

                        <!-- Completed (Green line/area) -->
                        <path d="M 50 180 Q 140 120 230 70 T 410 40 T 590 30 T 780 80 L 780 220 L 50 220 Z" fill="url(#greenGrad)" opacity="0.15" />
                        <path d="M 50 180 Q 140 120 230 70 T 410 40 T 590 30 T 780 80" fill="none" stroke="#28c76f" stroke-width="3" stroke-linecap="round" />

                        <!-- In Transit (Blue line/area) -->
                        <path d="M 50 200 Q 140 180 230 150 T 410 160 T 590 190 T 780 170 L 780 220 L 50 220 Z" fill="url(#blueGrad)" opacity="0.1" />
                        <path d="M 50 200 Q 140 180 230 150 T 410 160 T 590 190 T 780 170" fill="none" stroke="#007bff" stroke-width="3" stroke-linecap="round" />

                        <!-- X-Axis Labels -->
                        <text x="50" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">8AM</text>
                        <text x="140" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">9AM</text>
                        <text x="230" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">10AM</text>
                        <text x="320" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">11AM</text>
                        <text x="410" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">12PM</text>
                        <text x="500" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">1PM</text>
                        <text x="590" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">2PM</text>
                        <text x="680" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">3PM</text>
                        <text x="780" y="238" fill="#adb5bd" font-size="10" text-anchor="middle">4PM</text>

                        <!-- Gradients definition -->
                        <defs>
                            <linearGradient id="greenGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#28c76f" />
                                <stop offset="100%" stop-color="#28c76f" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="blueGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#007bff" />
                                <stop offset="100%" stop-color="#007bff" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <!-- Legend -->
                <div class="d-flex align-items-center gap-3 mt-3">
                    <div class="d-flex align-items-center gap-1">
                        <span style="display:inline-block; width:12px; height:12px; background-color:#28c76f; border-radius:3px;"></span>
                        <span class="text-muted" style="font-size:0.8rem;">Completed</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span style="display:inline-block; width:12px; height:12px; background-color:#007bff; border-radius:3px;"></span>
                        <span class="text-muted" style="font-size:0.8rem;">In Transit</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deliveries by Area Card -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-header d-flex align-items-center justify-content-between pb-3 bg-transparent border-0">
                <h5 class="m-0 fw-bold text-body" style="font-size: 1.1rem;">Deliveries by Area</h5>
                <a href="{{ route('live-map') }}" class="text-primary fw-semibold" style="font-size: 0.85rem;">View Map</a>
            </div>
            <div class="card-body">
                <ul class="p-0 m-0 list-unstyled">
                    <!-- Area 1: Downtown Zone -->
                    <li class="d-flex mb-4 align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-circle bg-label-primary d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">DT</span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0 fw-semibold">Downtown Zone</h6>
                                <small class="text-muted">12 active drivers</small>
                            </div>
                            <div class="user-progress text-end">
                                <h6 class="mb-0 fw-bold">420</h6>
                                <span class="badge bg-label-success rounded-pill" style="font-size: 0.7rem;">High Demand</span>
                            </div>
                        </div>
                    </li>
                    <!-- Area 2: Northwest District -->
                    <li class="d-flex mb-4 align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-circle bg-label-secondary d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; background-color: rgba(224, 76, 238, 0.1) !important; color: #e04cee !important;">NW</span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0 fw-semibold">Northwest District</h6>
                                <small class="text-muted">8 active drivers</small>
                            </div>
                            <div class="user-progress text-end">
                                <h6 class="mb-0 fw-bold">285</h6>
                                <span class="badge bg-label-secondary rounded-pill text-muted" style="font-size: 0.7rem; background-color: #f1f3f5 !important;">Normal</span>
                            </div>
                        </div>
                    </li>
                    <!-- Area 3: Southeast Hub -->
                    <li class="d-flex mb-4 align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-circle bg-label-success d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">SE</span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0 fw-semibold">Southeast Hub</h6>
                                <small class="text-muted">5 active drivers</small>
                            </div>
                            <div class="user-progress text-end">
                                <h6 class="mb-0 fw-bold">156</h6>
                                <span class="badge bg-label-secondary rounded-pill text-muted" style="font-size: 0.7rem; background-color: #f1f3f5 !important;">Normal</span>
                            </div>
                        </div>
                    </li>
                    <!-- Area 4: East Side -->
                    <li class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-circle bg-label-warning d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">ES</span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                                <h6 class="mb-0 fw-semibold">East Side</h6>
                                <small class="text-muted">3 active drivers</small>
                            </div>
                            <div class="user-progress text-end">
                                <h6 class="mb-0 fw-bold">89</h6>
                                <span class="badge bg-label-warning rounded-pill" style="font-size: 0.7rem;">Low Coverage</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Active Order Assignments & Live Activity Feed -->
<div class="row">
    <!-- Active Order Assignments Card -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-header d-flex align-items-center justify-content-between pb-3 bg-transparent border-0">
                <h5 class="m-0 fw-bold text-body" style="font-size: 1.1rem;">Active Order Assignments</h5>
                <a href="{{ route('operations-orders') }}" class="text-primary fw-semibold" style="font-size: 0.85rem;">View All</a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr class="table-light">
                            <th class="fw-bold">Order ID</th>
                            <th class="fw-bold">Driver</th>
                            <th class="fw-bold">Status</th>
                            <th class="fw-bold">Est. Time</th>
                            <th class="fw-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0" id="assignments-tbody">
                        <!-- Order 1: In Transit -->
                        <tr id="order-row-8924">
                            <td><span class="fw-semibold">#ORD-8924</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2" style="width:26px; height:26px;">
                                        <img src="{{ asset('assets/img/avatars/5.png') }}" alt="Avatar" class="rounded-circle">
                                    </div>
                                    <span>Mike Johnson</span>
                                </div>
                            </td>
                            <td><span class="badge bg-label-primary rounded-pill">In Transit</span></td>
                            <td>12 mins</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i class="icon-base bx bx-edit-alt me-1"></i> Track Live</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i class="icon-base bx bx-trash me-1"></i> Cancel Order</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- Order 2: Ready for Pickup -->
                        <tr id="order-row-8925">
                            <td><span class="fw-semibold">#ORD-8925</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2" style="width:26px; height:26px;">
                                        <img src="{{ asset('assets/img/avatars/6.png') }}" alt="Avatar" class="rounded-circle">
                                    </div>
                                    <span>Sarah Connor</span>
                                </div>
                            </td>
                            <td><span class="badge bg-label-secondary rounded-pill" style="background-color: rgba(224, 76, 238, 0.1) !important; color: #e04cee !important;">Ready for Pickup</span></td>
                            <td>-</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i class="icon-base bx bx-edit-alt me-1"></i> Track Live</a>
                                        <a class="dropdown-item" href="javascript:void(0);"><i class="icon-base bx bx-trash me-1"></i> Cancel Order</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- Order 3: Unassigned -->
                        <tr id="order-row-8926">
                            <td><span class="fw-semibold">#ORD-8926</span></td>
                            <td>
                                <span class="text-muted italic">Unassigned</span>
                            </td>
                            <td><span class="badge bg-label-warning rounded-pill">Available</span></td>
                            <td>-</td>
                            <td>
                                <a href="javascript:void(0);" class="text-primary fw-semibold assign-driver-btn" data-order-id="#ORD-8926">Assign</a>
                            </td>
                        </tr>
                        <!-- Order 4: Delivered -->
                        <tr id="order-row-8923">
                            <td><span class="fw-semibold">#ORD-8923</span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2" style="width:26px; height:26px;">
                                        <img src="{{ asset('assets/img/avatars/7.png') }}" alt="Avatar" class="rounded-circle">
                                    </div>
                                    <span>David Smith</span>
                                </div>
                            </td>
                            <td><span class="badge bg-label-success rounded-pill">Delivered</span></td>
                            <td>-</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);"><i class="icon-base bx bx-file me-1"></i> View Receipt</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Live Activity Feed Card -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100 shadow-none border" style="border-radius: 12px;">
            <div class="card-header d-flex align-items-center justify-content-between pb-3 bg-transparent border-0">
                <h5 class="m-0 fw-bold text-body" style="font-size: 1.1rem;">Live Activity Feed</h5>
            </div>
            <div class="card-body">
                <div class="activity-feed">
                    <!-- Feed Item 1 -->
                    <div class="d-flex mb-4 gap-3">
                        <div class="feed-badge-container d-flex flex-column align-items-center">
                            <span class="badge-dot p-1 rounded-circle bg-success"></span>
                            <span class="feed-line flex-grow-1" style="width: 2px; background: #e9ecef; margin-top: 4px;"></span>
                        </div>
                        <div>
                            <p class="mb-1 text-body" style="font-size: 0.85rem;">
                                <strong>#ORD-8923</strong> was successfully delivered by <strong>David Smith</strong>.
                            </p>
                            <small class="text-muted" style="font-size: 0.75rem;">2 mins ago</small>
                        </div>
                    </div>
                    <!-- Feed Item 2 -->
                    <div class="d-flex mb-4 gap-3">
                        <div class="feed-badge-container d-flex flex-column align-items-center">
                            <span class="badge-dot p-1 rounded-circle bg-primary"></span>
                            <span class="feed-line flex-grow-1" style="width: 2px; background: #e9ecef; margin-top: 4px;"></span>
                        </div>
                        <div>
                            <p class="mb-1 text-body" style="font-size: 0.85rem;">
                                <strong>Mike Johnson</strong> accepted order <strong>#ORD-8924</strong>.
                            </p>
                            <small class="text-muted" style="font-size: 0.75rem;">5 mins ago</small>
                        </div>
                    </div>
                    <!-- Feed Item 3 -->
                    <div class="d-flex mb-4 gap-3">
                        <div class="feed-badge-container d-flex flex-column align-items-center">
                            <span class="badge-dot p-1 rounded-circle bg-warning"></span>
                            <span class="feed-line flex-grow-1" style="width: 2px; background: #e9ecef; margin-top: 4px;"></span>
                        </div>
                        <div>
                            <p class="mb-1 text-body" style="font-size: 0.85rem;">
                                New driver registration pending approval: <strong>Alex Chen (Rapido)</strong>.
                            </p>
                            <small class="text-muted" style="font-size: 0.75rem;">12 mins ago</small>
                        </div>
                    </div>
                    <!-- Feed Item 4 -->
                    <div class="d-flex mb-4 gap-3">
                        <div class="feed-badge-container d-flex flex-column align-items-center">
                            <span class="badge-dot p-1 rounded-circle bg-danger"></span>
                            <span class="feed-line flex-grow-1" style="width: 2px; background: #e9ecef; margin-top: 4px;"></span>
                        </div>
                        <div>
                            <p class="mb-1 text-body" style="font-size: 0.85rem;">
                                Delivery failed for <strong>#ORD-8910</strong>. Customer unavailable.
                            </p>
                            <small class="text-muted" style="font-size: 0.75rem;">18 mins ago</small>
                        </div>
                    </div>
                    <!-- Feed Item 5 -->
                    <div class="d-flex gap-3">
                        <div class="feed-badge-container d-flex flex-column align-items-center">
                            <span class="badge-dot p-1 rounded-circle bg-secondary" style="background-color: #e04cee !important;"></span>
                        </div>
                        <div>
                            <p class="mb-1 text-body" style="font-size: 0.85rem;">
                                Order <strong>#ORD-8925</strong> is ready for pickup at Store A.
                            </p>
                            <small class="text-muted" style="font-size: 0.75rem;">22 mins ago</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Driver Modal (Interactive with Javascript) -->
<div class="modal fade" id="assignDriverModal" tabindex="-1" aria-labelledby="assignDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-body" id="assignDriverModalLabel">Assign Driver to Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal-btn"></button>
            </div>
            <form id="assignDriverForm">
                <div class="modal-body py-4">
                    <input type="hidden" id="assign-order-id" name="order_id">
                    <div class="mb-3">
                        <label for="order-display" class="form-label text-muted">Order ID</label>
                        <input type="text" class="form-control bg-light border-0 fw-semibold text-body" id="order-display" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="driver-select" class="form-label text-body fw-semibold">Select Available Driver</label>
                        <select class="form-select" id="driver-select" required>
                            <option value="" disabled selected>Choose a driver...</option>
                            <option value="Alex Chen" data-avatar="1.png">Alex Chen (Rapido - 2 mins away)</option>
                            <option value="John Doe" data-avatar="2.png">John Doe (Bike - 5 mins away)</option>
                            <option value="Robert Lee" data-avatar="3.png">Robert Lee (Car - 8 mins away)</option>
                            <option value="William Tan" data-avatar="4.png">William Tan (Scooter - 10 mins away)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="est-pickup" class="form-label text-body fw-semibold">Estimated Delivery Time (mins)</label>
                        <input type="number" class="form-control" id="est-pickup" min="5" max="60" value="15" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="cancel-modal-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Driver</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tab/Filter highlight functionality
    const filterButtons = document.querySelectorAll('.btn-group button');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => {
                b.classList.remove('active-filter-btn');
                b.classList.add('text-muted');
            });
            this.classList.add('active-filter-btn');
            this.classList.remove('text-muted');
        });
    });

    // Modal triggers and elements
    const assignButtons = document.querySelectorAll('.assign-driver-btn');
    const assignModalEl = document.getElementById('assignDriverModal');
    const assignModal = new bootstrap.Modal(assignModalEl);
    const assignForm = document.getElementById('assignDriverForm');
    const orderIdInput = document.getElementById('assign-order-id');
    const orderDisplayInput = document.getElementById('order-display');
    const driverSelect = document.getElementById('driver-select');
    const estPickup = document.getElementById('est-pickup');

    let currentAssignRow = null;

    assignButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const orderId = this.getAttribute('data-order-id');
            currentAssignRow = this.closest('tr');
            
            // Set values in the modal
            orderIdInput.value = orderId;
            orderDisplayInput.value = orderId;
            driverSelect.value = '';
            
            // Show modal
            assignModal.show();
        });
    });

    // Form submission handles closing the modal and updating the static table
    assignForm.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const selectedDriver = driverSelect.value;
        const estTime = estPickup.value;
        const selectedOption = driverSelect.options[driverSelect.selectedIndex];
        const avatarNum = selectedOption.getAttribute('data-avatar') || '1.png';

        if (currentAssignRow && selectedDriver) {
            // Update table cells dynamically
            const driverCell = currentAssignRow.cells[1];
            const statusCell = currentAssignRow.cells[2];
            const timeCell = currentAssignRow.cells[3];
            const actionCell = currentAssignRow.cells[4];

            driverCell.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-xs me-2" style="width:26px; height:26px;">
                        <img src="{{ asset('assets/img/avatars/') }}/${avatarNum}" alt="Avatar" class="rounded-circle">
                    </div>
                    <span>${selectedDriver}</span>
                </div>
            `;

            statusCell.innerHTML = `<span class="badge bg-label-primary rounded-pill">In Transit</span>`;
            timeCell.innerText = `${estTime} mins`;
            actionCell.innerHTML = `
                <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:void(0);"><i class="icon-base bx bx-edit-alt me-1"></i> Track Live</a>
                        <a class="dropdown-item" href="javascript:void(0);"><i class="icon-base bx bx-trash me-1"></i> Cancel Order</a>
                    </div>
                </div>
            `;

            // Bootstrap dropdowns re-initialization might be needed, but since it's static we can rely on bootstrap event delegation.
        }

        // Close the modal on submit click
        assignModal.hide();
    });

    // Custom date modal alert just to feel interactive
    document.getElementById('filter-custom').addEventListener('click', function() {
        alert("Date picker dialog opened! (Static implementation)");
    });
});
</script>

<style>
.active-filter-btn {
    background-color: #697a8d !important;
    color: #fff !important;
    border-color: #697a8d !important;
}
.badge-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
}
.feed-badge-container {
    width: 8px;
}
.italic {
    font-style: italic;
}
</style>
@endsection
