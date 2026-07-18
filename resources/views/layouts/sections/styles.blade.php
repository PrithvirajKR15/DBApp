<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

<!-- Fonts Icons -->
@vite(['resources/assets/vendor/fonts/iconify/iconify.css'])

<!-- Core CSS -->
@vite(['resources/assets/vendor/scss/core.scss', 'resources/assets/css/demo.css'])

<!-- Vendor Styles -->
@vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss')
@yield('vendor-style')

<!-- Page Styles -->
@yield('page-style')

<!-- app CSS -->
@vite(['resources/css/app.css'])
<!-- END: app CSS-->

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- SweetAlert2 Global Customizations -->
<style>
    .swal2-container {
        z-index: 99999 !important;
    }
    
    /* Global Premium Close Button Customization */
    .btn-close {
        background-color: #ff7a00 !important;
        border-radius: 50% !important;
        padding: 1.00rem !important;
        opacity: 0.75 !important;
        background-position: center !important;
        transition: all 0.2s ease-in-out !important;
        box-shadow: 0 2px 5px rgba(255, 122, 0, 0.2) !important;
    }
    .btn-close:hover {
        opacity: 1 !important;
        box-shadow: 0 4px 8px rgba(255, 122, 0, 0.35) !important;
    }

    /* Selectable Partner Cards (Used in driver add/edit modal & profile page) */
    .partner-card {
        border-width: 1.5px !important;
        box-shadow: none !important;
    }
    .partner-card.active {
        border-color: #ff7a00 !important;
        background-color: rgba(255, 122, 0, 0.02) !important;
    }
    .partner-card.active .partner-icon-box {
        background-color: rgba(255, 122, 0, 0.1) !important;
        color: #ff7a00 !important;
    }
    .partner-card .form-check-input:checked {
        background-color: #ff7a00 !important;
        border-color: #ff7a00 !important;
    }
</style>
