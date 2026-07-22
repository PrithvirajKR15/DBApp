@extends('layouts/contentNavbarLayout')

@section('title', 'Settings')
@section('page-title', 'System & Fleet Settings')

@section('content')
<div class="row">
    <!-- Left Navigation Column -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="settings-menu-list">
                    <a href="javascript:void(0);" class="list-group-item list-group-item-action active p-3 border-bottom d-flex align-items-center gap-2" data-target="general-settings">
                        <i class="icon-base bx bx-cog fs-4"></i>
                        <span class="fw-bold">General Settings</span>
                    </a>
                    <a href="javascript:void(0);" class="list-group-item list-group-item-action p-3 border-bottom d-flex align-items-center gap-2" data-target="delivery-settings">
                        <i class="icon-base bx bx-package fs-4"></i>
                        <span class="fw-bold">Delivery Config</span>
                    </a>
                    <a href="javascript:void(0);" class="list-group-item list-group-item-action p-3 d-flex align-items-center gap-2" data-target="notifications-settings">
                        <i class="icon-base bx bx-bell fs-4"></i>
                        <span class="fw-bold">Notifications & Alerts</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Settings Form Column -->
    <div class="col-md-8 mb-4">
        <form id="systemSettingsForm">
            <!-- General Settings Section -->
            <div class="card shadow-none border settings-section" id="general-settings" style="border-radius: 12px;">
                <div class="card-header pb-3 bg-transparent border-bottom">
                    <h5 class="mb-0 fw-bold text-body">General Application Settings</h5>
                    <small class="text-muted">Basic configuration for Deliverease.</small>
                </div>
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="app-name" class="form-label text-body fw-semibold">Application Name</label>
                            <input type="text" class="form-control" id="app-name" value="Deliverease" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin-email" class="form-label text-body fw-semibold">Support Contact Email</label>
                            <input type="email" class="form-control" id="admin-email" value="admin@deliverease.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency-select" class="form-label text-body fw-semibold">Base Currency</label>
                            <select class="form-select" id="currency-select">
                                <option value="USD" selected>US Dollar ($)</option>
                                <option value="EUR">Euro (€)</option>
                                <option value="GBP">British Pound (£)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="timezone-select" class="form-label text-body fw-semibold">System Timezone</label>
                            <select class="form-select" id="timezone-select">
                                <option value="Asia/Kolkata" selected>Indian Standard Time (Asia/Kolkata)</option>
                                <option value="UTC">Coordinated Universal Time (UTC)</option>
                                <option value="America/New_York">Eastern Time (America/New_York)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Config Section (Initially Hidden) -->
            <div class="card shadow-none border settings-section d-none" id="delivery-settings" style="border-radius: 12px;">
                <div class="card-header pb-3 bg-transparent border-bottom">
                    <h5 class="mb-0 fw-bold text-body">Delivery Charge Parameters</h5>
                    <small class="text-muted">Configure default pricing for orders.</small>
                </div>
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="base-fare" class="form-label text-body fw-semibold">Base Fare ($)</label>
                            <input type="number" step="0.01" class="form-control" id="base-fare" value="5.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="per-km" class="form-label text-body fw-semibold">Per-mile Surcharge ($)</label>
                            <input type="number" step="0.01" class="form-control" id="per-km" value="1.50" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="auto-approve-switch">
                                <label class="form-check-label text-body fw-semibold" for="auto-approve-switch">Auto-Approve Registered Drivers</label>
                            </div>
                            <small class="text-muted d-block mb-3">Skip onboarding approvals for riders with clean national databases checks.</small>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="bg-checks-switch" checked>
                                <label class="form-check-label text-body fw-semibold" for="bg-checks-switch">Mandate Driver Background Checks</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Section (Initially Hidden) -->
            <div class="card shadow-none border settings-section d-none" id="notifications-settings" style="border-radius: 12px;">
                <div class="card-header pb-3 bg-transparent border-bottom">
                    <h5 class="mb-0 fw-bold text-body">Notifications & Alerts</h5>
                    <small class="text-muted">Configure SMS/email delivery receipts.</small>
                </div>
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="sms-notify" checked>
                                <label class="form-check-label text-body fw-semibold" for="sms-notify">Customer SMS Notifications</label>
                            </div>
                            <small class="text-muted d-block mt-1">Send automatic tracking SMS alerts on order state change.</small>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email-receipts" checked>
                                <label class="form-check-label text-body fw-semibold" for="email-receipts">Email Receipts & Payout Alerts</label>
                            </div>
                            <small class="text-muted d-block mt-1">Send transaction invoices to customers and transfer alerts to drivers.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Settings Success Toast -->
<div class="bs-toast toast toast-placement-ex m-2 fade bg-success top-0 end-0" id="settings-toast" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; z-index: 1090;">
    <div class="toast-header">
        <i class="icon-base bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Settings Saved</div>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        Application configuration settings saved successfully!
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const listItems = document.querySelectorAll('#settings-menu-list a');
    const sections = document.querySelectorAll('.settings-section');
    
    // Switch active settings panels
    listItems.forEach(item => {
        item.addEventListener('click', function () {
            listItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            const targetId = this.getAttribute('data-target');
            sections.forEach(section => {
                if (section.id === targetId) {
                    section.classList.remove('d-none');
                } else {
                    section.classList.add('d-none');
                }
            });
        });
    });

    // Form submission showing toast
    const settingsForm = document.getElementById('systemSettingsForm');
    const toastEl = document.getElementById('settings-toast');
    const toast = new bootstrap.Toast(toastEl);

    settingsForm.addEventListener('submit', function (e) {
        e.preventDefault();
        toast.show();
    });
});
</script>
@endsection
