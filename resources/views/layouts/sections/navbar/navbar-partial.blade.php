@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp

<!-- ! Not required for layout-without-menu -->
@if(!isset($navbarHideToggle))
<div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-4">
    <a class="nav-item nav-link px-0" href="javascript:void(0)" title="Toggle side menu" aria-label="Toggle side menu">
        <i class="icon-base bx bx-menu icon-md"></i>
    </a>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center justify-content-between w-100" id="navbar-collapse">
    <!-- Left: Page Title -->
    <div class="navbar-nav align-items-center">
        <h4 class="mb-0 fw-bold text-body" style="font-family: 'Public Sans', sans-serif;">
            @yield('page-title', 'Delivery Management Dashboard')
        </h4>
    </div>
    
    <!-- Right: Search, Notifications, Export and User -->
    <div class="d-flex align-items-center gap-3">
        <!-- Search bar -->
        <div class="nav-item d-flex align-items-center bg-light px-2 rounded-2" style="border: 1px solid #e1e4e6;">
            <i class="icon-base bx bx-search text-muted fs-5"></i>
            <input type="text" class="form-control border-0 bg-transparent shadow-none py-1 ps-2" placeholder="Search orders, drivers..." style="width: 240px; font-size: 0.85rem;">
        </div>

        <!-- Notification Bell -->
        <div class="position-relative cursor-pointer d-flex align-items-center">
            <a href="javascript:void(0);" class="text-body btn btn-icon btn-ghost-secondary border-0 p-0 position-relative">
                <i class="icon-base bx bx-bell fs-4"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="margin-top: 4px; margin-left: -4px;"></span>
            </a>
        </div>

        <!-- Export button -->
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2 py-1 px-3" style="font-size: 0.85rem; border-color: #d9dee3; color: #566a7f;" onclick="window.print()">
            <i class="icon-base bx bx-cloud-upload fs-5"></i>
            <span>Export</span>
        </button>
    </div>
</div>