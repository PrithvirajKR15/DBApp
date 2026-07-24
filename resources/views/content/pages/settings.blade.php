@extends('layouts/contentNavbarLayout')

@section('title', 'Settings')
@section('page-title', 'System & Fleet Settings')

@php
    $activeSection = $activeSection ?? 'general-settings';
@endphp

@section('content')
<style>
    .settings-nav {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .settings-nav > li + li {
        border-top: 1px solid #eef0f2;
    }
    .settings-nav a {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.95rem 1.1rem;
        color: #566a7f;
        text-decoration: none;
        transition: background-color .15s ease, color .15s ease;
    }
    .settings-nav a:hover {
        background: #f8f9fa;
        color: #384551;
    }
    .settings-nav a.active {
        background: rgba(255, 122, 0, 0.08);
        color: #ff7a00;
        border-left: 3px solid #ff7a00;
        padding-left: calc(1.1rem - 3px);
    }
    .settings-nav a i {
        font-size: 1.25rem;
        line-height: 1.2;
        margin-top: 1px;
    }
    .settings-nav .nav-copy {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        min-width: 0;
    }
    .settings-nav .nav-copy strong {
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.25;
    }
    .settings-nav .nav-copy small {
        font-size: 0.75rem;
        color: #a1acb8;
        line-height: 1.3;
    }
    .settings-nav a.active .nav-copy small {
        color: rgba(255, 122, 0, 0.75);
    }
    .settings-nav .nav-group-label {
        padding: 0.7rem 1.1rem 0.35rem;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #a1acb8;
    }
    .pin-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        background: #f1f3f5;
        color: #566a7f;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 0 0.25rem 0.25rem 0;
    }
    .store-zone-option:hover {
        background: #f8f9fa;
    }
    .store-derived-pins {
        min-height: 42px;
    }
</style>

<div class="row">
    {{-- Left navigation --}}
    <div class="col-lg-3 col-md-4 mb-4">
        <div class="card shadow-none border" style="border-radius: 12px;">
            <div class="card-body p-0">
                <ul class="settings-nav" id="settings-menu-list" role="tablist">
                    <li class="nav-group-label" aria-hidden="true">General</li>
                    <li>
                        <a href="#general-settings"
                           class="{{ $activeSection === 'general-settings' ? 'active' : '' }}"
                           data-target="general-settings"
                           role="tab"
                           aria-controls="general-settings">
                            <i class="icon-base bx bx-cog"></i>
                            <span class="nav-copy">
                                <strong>General Settings</strong>
                                <small>App name, currency, timezone</small>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="#delivery-settings"
                           class="{{ $activeSection === 'delivery-settings' ? 'active' : '' }}"
                           data-target="delivery-settings"
                           role="tab"
                           aria-controls="delivery-settings">
                            <i class="icon-base bx bx-package"></i>
                            <span class="nav-copy">
                                <strong>Delivery Config</strong>
                                <small>Fares & driver onboarding</small>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="#notifications-settings"
                           class="{{ $activeSection === 'notifications-settings' ? 'active' : '' }}"
                           data-target="notifications-settings"
                           role="tab"
                           aria-controls="notifications-settings">
                            <i class="icon-base bx bx-bell"></i>
                            <span class="nav-copy">
                                <strong>Notifications</strong>
                                <small>SMS & email alerts</small>
                            </span>
                        </a>
                    </li>

                    <li class="nav-group-label" aria-hidden="true">Coverage</li>
                    <li>
                        <a href="#zone-pincode-settings"
                           class="{{ $activeSection === 'zone-pincode-settings' ? 'active' : '' }}"
                           data-target="zone-pincode-settings"
                           role="tab"
                           aria-controls="zone-pincode-settings">
                            <i class="icon-base bx bx-map-alt"></i>
                            <span class="nav-copy">
                                <strong>Zone ↔ Pincode</strong>
                                <small>Map postal codes to areas</small>
                            </span>
                        </a>
                    </li>
                    <li>
                        <a href="#stores-settings"
                           class="{{ $activeSection === 'stores-settings' ? 'active' : '' }}"
                           data-target="stores-settings"
                           role="tab"
                           aria-controls="stores-settings">
                            <i class="icon-base bx bx-store"></i>
                            <span class="nav-copy">
                                <strong>Stores</strong>
                                <small>Assign zones → get pincodes</small>
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Right content panels --}}
    <div class="col-lg-9 col-md-8 mb-4">
        {{-- General --}}
        <form id="systemSettingsForm" class="settings-section {{ $activeSection === 'general-settings' ? '' : 'd-none' }}" data-section="general-settings">
            <div class="card shadow-none border" style="border-radius: 12px;" id="general-settings">
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
                                <option value="INR" selected>Indian Rupee (₹)</option>
                                <option value="USD">US Dollar ($)</option>
                                <option value="EUR">Euro (€)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="timezone-select" class="form-label text-body fw-semibold">System Timezone</label>
                            <select class="form-select" id="timezone-select">
                                <option value="Asia/Kolkata" selected>Indian Standard Time (Asia/Kolkata)</option>
                                <option value="UTC">Coordinated Universal Time (UTC)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </form>

        {{-- Delivery --}}
        <form class="settings-section {{ $activeSection === 'delivery-settings' ? '' : 'd-none' }}" data-section="delivery-settings" id="delivery-settings-form">
            <div class="card shadow-none border" style="border-radius: 12px;" id="delivery-settings">
                <div class="card-header pb-3 bg-transparent border-bottom">
                    <h5 class="mb-0 fw-bold text-body">Delivery Charge Parameters</h5>
                    <small class="text-muted">Configure default pricing for orders.</small>
                </div>
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="base-fare" class="form-label text-body fw-semibold">Base Fare (₹)</label>
                            <input type="number" step="0.01" class="form-control" id="base-fare" value="40.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="per-km" class="form-label text-body fw-semibold">Per-km Surcharge (₹)</label>
                            <input type="number" step="0.01" class="form-control" id="per-km" value="12.00" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="auto-approve-switch">
                                <label class="form-check-label text-body fw-semibold" for="auto-approve-switch">Auto-Approve Registered Drivers</label>
                            </div>
                            <small class="text-muted d-block">Skip onboarding approvals for riders with clean checks.</small>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="bg-checks-switch" checked>
                                <label class="form-check-label text-body fw-semibold" for="bg-checks-switch">Mandate Driver Background Checks</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </form>

        {{-- Notifications --}}
        <form class="settings-section {{ $activeSection === 'notifications-settings' ? '' : 'd-none' }}" data-section="notifications-settings" id="notifications-settings-form">
            <div class="card shadow-none border" style="border-radius: 12px;" id="notifications-settings">
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
                            <small class="text-muted d-block mt-1">Send invoices to customers and transfer alerts to drivers.</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </form>

        {{-- Zone ↔ Pincode --}}
        <div class="settings-section {{ $activeSection === 'zone-pincode-settings' ? '' : 'd-none' }}" data-section="zone-pincode-settings" id="zone-pincode-settings">
            <div class="card shadow-none border" style="border-radius: 12px;">
                <div class="card-header pb-3 bg-transparent border-bottom">
                    <h5 class="mb-0 fw-bold text-body">Zone ↔ Pincode Mapping</h5>
                    <small class="text-muted">
                        Drivers pick <strong>areas (zones)</strong>. Map postal codes here so
                        <code>order.pincode</code> can find the right third-party drivers.
                        Set lat/lng as the zone map center — pins inherit it for order-detail map fallbacks.
                    </small>
                </div>
                <div class="card-body py-3">
                    @forelse ($zones as $zone)
                        <form class="zone-pincode-form border rounded-3 p-3 mb-3"
                              data-zone-id="{{ $zone->id }}"
                              action="{{ route('system-settings.zones.pincodes', $zone) }}"
                              method="POST">
                            @csrf
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold text-body">{{ $zone->name }}</div>
                                    <small class="text-muted">
                                        code: <code>{{ $zone->code }}</code>
                                        @if ($zone->region)
                                            · {{ ucfirst($zone->region) }}
                                        @endif
                                    </small>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 justify-content-end">
                                    <div class="text-end">
                                        @forelse ($zone->pincodeList() as $pin)
                                            <span class="pin-chip">{{ $pin }}</span>
                                        @empty
                                            <span class="text-muted small">No pincodes yet</span>
                                        @endforelse
                                    </div>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger zone-delete-btn"
                                            data-delete-url="{{ route('system-settings.zones.destroy', $zone) }}"
                                            data-zone-id="{{ $zone->id }}"
                                            data-zone-name="{{ $zone->name }}">
                                        <i class="bx bx-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small text-muted mb-1">Pincodes (comma or space separated)</label>
                                    <input type="text"
                                           name="pincodes"
                                           class="form-control"
                                           value="{{ implode(', ', $zone->pincodeList()) }}"
                                           placeholder="e.g. 695001, 695002"
                                           required>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small text-muted mb-1">Latitude</label>
                                    <input type="number"
                                           step="any"
                                           name="latitude"
                                           class="form-control"
                                           value="{{ $zone->lat }}"
                                           placeholder="8.5241">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small text-muted mb-1">Longitude</label>
                                    <input type="number"
                                           step="any"
                                           name="longitude"
                                           class="form-control"
                                           value="{{ $zone->lng }}"
                                           placeholder="76.9366">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">Save</button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Lat/lng is the zone map center. Saved pincodes inherit these coordinates for order-map fallbacks.
                            </small>
                            <div class="zone-save-status small mt-2 text-success d-none"></div>
                        </form>
                    @empty
                        <div class="text-muted py-4 text-center">
                            No zones found. Run <code>php artisan db:seed --class=ZoneSeeder</code> first.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Stores --}}
        <div class="settings-section {{ $activeSection === 'stores-settings' ? '' : 'd-none' }}" data-section="stores-settings" id="stores-settings">
            <div class="card shadow-none border" style="border-radius: 12px;">
                <div class="card-header pb-3 bg-transparent border-bottom">
                    <h5 class="mb-0 fw-bold text-body">Store Settings</h5>
                    <small class="text-muted">
                        Assign one or more <strong>zones (areas)</strong> to each store.
                        Serviceable pincodes are taken automatically from those zones’ pin maps
                        (configure them under <em>Zone ↔ Pincode</em>).
                    </small>
                </div>
                <div class="card-body py-3">
                    @forelse ($stores as $store)
                        @php
                            $selectedZoneIds = $store->zones->pluck('id')->all();
                            $derivedPins = $store->zones
                                ->flatMap(fn ($z) => $z->pincodeList())
                                ->unique()
                                ->sort()
                                ->values()
                                ->all();
                            if ($derivedPins === [] && ! empty($store->serviceable_pincodes)) {
                                $derivedPins = $store->serviceable_pincodes;
                            }
                        @endphp
                        <form class="store-settings-form border rounded-3 p-3 mb-3"
                              data-store-id="{{ $store->id }}"
                              action="{{ route('system-settings.stores.save', $store) }}"
                              method="POST">
                            @csrf
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <div class="fw-semibold text-body">{{ $store->name }}</div>
                                    <small class="text-muted">code: <code>{{ $store->code }}</code></small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ strtolower((string) $store->status) === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                                        {{ $store->status ?? '—' }}
                                    </span>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger store-delete-btn"
                                            data-delete-url="{{ route('system-settings.stores.destroy', $store) }}"
                                            data-store-id="{{ $store->id }}"
                                            data-store-name="{{ $store->name }}">
                                        <i class="bx bx-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-muted mb-1">Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ $store->name }}" required>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small text-muted mb-1">External Store ID</label>
                                    <input type="text" name="external_store_id" class="form-control"
                                           value="{{ $store->external_store_id ?? $store->code }}"
                                           placeholder="STORE_99">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label small text-muted mb-1">Latitude</label>
                                    <input type="number" step="any" name="latitude" class="form-control" value="{{ $store->lat }}">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label small text-muted mb-1">Longitude</label>
                                    <input type="number" step="any" name="longitude" class="form-control" value="{{ $store->lng }}">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label small text-muted mb-1">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" @selected(strtolower((string) $store->status) === 'active')>Active</option>
                                        <option value="inactive" @selected(strtolower((string) $store->status) === 'inactive')>Inactive</option>
                                    </select>
                                </div>

                                <div class="col-12 mb-2">
                                    <label class="form-label small text-muted mb-1">Service zones <span class="text-danger">*</span></label>
                                    <div class="border rounded-3 p-2 store-zone-grid" style="max-height: 220px; overflow-y: auto;">
                                        <div class="row g-1">
                                            @foreach ($zones as $zone)
                                                @php $zonePins = $zone->pincodeList(); @endphp
                                                <div class="col-md-6 col-lg-4">
                                                    <label class="d-flex align-items-start gap-2 p-2 rounded-2 store-zone-option"
                                                           style="cursor: pointer;">
                                                        <input type="checkbox"
                                                               class="form-check-input mt-1 store-zone-check"
                                                               name="zone_ids[]"
                                                               value="{{ $zone->id }}"
                                                               data-pincodes="{{ e(json_encode($zonePins)) }}"
                                                               @checked(in_array($zone->id, $selectedZoneIds, true))>
                                                        <span>
                                                            <span class="d-block fw-semibold text-body" style="font-size: 0.85rem;">{{ $zone->name }}</span>
                                                            <small class="text-muted">
                                                                @if ($zonePins !== [])
                                                                    {{ implode(', ', $zonePins) }}
                                                                @else
                                                                    no pins mapped
                                                                @endif
                                                            </small>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <small class="text-muted">Choose every area this store delivers to. At least one zone is required.</small>
                                </div>

                                <div class="col-12 mb-2">
                                    <label class="form-label small text-muted mb-1">Serviceable pincodes <span class="fw-normal">(from selected zones)</span></label>
                                    <div class="store-derived-pins border rounded-3 p-2 bg-light min-h-40">
                                        @forelse ($derivedPins as $pin)
                                            <span class="pin-chip">{{ $pin }}</span>
                                        @empty
                                            <span class="text-muted small store-pins-empty">Select zones to see pincodes</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="store-save-status small text-success d-none"></div>
                                <button type="submit" class="btn btn-primary">Save Store</button>
                            </div>
                        </form>
                    @empty
                        <div class="text-muted py-4 text-center">No stores found yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="bs-toast toast toast-placement-ex m-2 fade bg-success top-0 end-0" id="settings-toast" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; z-index: 1090;">
    <div class="toast-header">
        <i class="icon-base bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold" id="settings-toast-title">Settings Saved</div>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="settings-toast-body">
        Application configuration settings saved successfully!
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('#settings-menu-list a[data-target]');
    const sections = document.querySelectorAll('.settings-section');
    const toastEl = document.getElementById('settings-toast');
    const toast = new bootstrap.Toast(toastEl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value;

    function showSection(targetId) {
        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-target') === targetId);
        });
        sections.forEach(section => {
            const match = section.id === targetId
                || section.getAttribute('data-section') === targetId
                || section.querySelector('#' + targetId);
            section.classList.toggle('d-none', !match);
        });
        if (history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('section', targetId);
            history.replaceState({}, '', url);
        }
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            showSection(this.getAttribute('data-target'));
        });
    });

    // Generic toast for static forms
    document.querySelectorAll('#systemSettingsForm, #delivery-settings-form, #notifications-settings-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            document.getElementById('settings-toast-title').textContent = 'Settings Saved';
            document.getElementById('settings-toast-body').textContent = 'Application configuration settings saved successfully!';
            toast.show();
        });
    });

    async function postForm(form) {
        const body = new FormData(form);
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || 'Save failed.');
        }
        return data;
    }

    document.querySelectorAll('.zone-pincode-form').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const status = form.querySelector('.zone-save-status');
            try {
                const data = await postForm(form);
                status.textContent = data.message || 'Saved.';
                status.classList.remove('d-none', 'text-danger');
                status.classList.add('text-success');
                document.getElementById('settings-toast-title').textContent = 'Zone Updated';
                document.getElementById('settings-toast-body').textContent = data.message || 'Zone pincodes saved.';
                toast.show();
            } catch (err) {
                status.textContent = err.message;
                status.classList.remove('d-none', 'text-success');
                status.classList.add('text-danger');
            }
        });
    });

    document.querySelectorAll('.store-settings-form').forEach(form => {
        const pinBox = form.querySelector('.store-derived-pins');

        function refreshDerivedPins() {
            const pins = new Set();
            form.querySelectorAll('.store-zone-check:checked').forEach(input => {
                try {
                    const list = JSON.parse(input.getAttribute('data-pincodes') || '[]');
                    (list || []).forEach(pin => pins.add(String(pin)));
                } catch (e) {}
            });
            const sorted = Array.from(pins).sort();
            if (!pinBox) return;
            if (sorted.length === 0) {
                pinBox.innerHTML = '<span class="text-muted small store-pins-empty">Select zones to see pincodes</span>';
                return;
            }
            pinBox.innerHTML = sorted.map(pin => `<span class="pin-chip">${pin}</span>`).join('');
        }

        form.querySelectorAll('.store-zone-check').forEach(input => {
            input.addEventListener('change', refreshDerivedPins);
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const status = form.querySelector('.store-save-status');
            const checked = form.querySelectorAll('.store-zone-check:checked');
            if (checked.length === 0) {
                status.textContent = 'Select at least one zone.';
                status.classList.remove('d-none', 'text-success');
                status.classList.add('text-danger');
                return;
            }
            try {
                const data = await postForm(form);
                status.textContent = data.message || 'Saved.';
                status.classList.remove('d-none', 'text-danger');
                status.classList.add('text-success');
                if (data.store?.serviceable_pincodes && pinBox) {
                    const pins = data.store.serviceable_pincodes;
                    pinBox.innerHTML = pins.length
                        ? pins.map(pin => `<span class="pin-chip">${pin}</span>`).join('')
                        : '<span class="text-muted small">No pincodes on selected zones</span>';
                }
                document.getElementById('settings-toast-title').textContent = 'Store Updated';
                document.getElementById('settings-toast-body').textContent = data.message || 'Store settings saved.';
                toast.show();
            } catch (err) {
                status.textContent = err.message;
                status.classList.remove('d-none', 'text-success');
                status.classList.add('text-danger');
            }
        });
    });

    async function deleteResource(url) {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || 'Delete failed.');
        }
        return data;
    }

    document.querySelectorAll('.zone-delete-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.dataset.deleteUrl;
            const zoneId = this.dataset.zoneId;
            const zoneName = this.dataset.zoneName || 'this zone';
            const form = this.closest('.zone-pincode-form');

            Swal.fire({
                title: 'Delete zone?',
                text: `Delete "${zoneName}"? Pincode mappings and store/agency links for this zone will also be removed.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3e1d',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const data = await deleteResource(url);
                    form?.remove();
                    document.querySelectorAll(`.store-zone-check[value="${zoneId}"]`).forEach(input => {
                        input.closest('.col-md-6, .col-lg-4')?.remove();
                    });
                    document.querySelectorAll('.store-settings-form').forEach(storeForm => {
                        storeForm.querySelectorAll('.store-zone-check').forEach(input => {
                            input.dispatchEvent(new Event('change'));
                        });
                    });
                    document.getElementById('settings-toast-title').textContent = 'Zone Deleted';
                    document.getElementById('settings-toast-body').textContent = data.message || 'Zone deleted.';
                    toast.show();
                } catch (err) {
                    Swal.fire({ title: 'Delete failed', text: err.message, icon: 'error' });
                }
            });
        });
    });

    document.querySelectorAll('.store-delete-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.dataset.deleteUrl;
            const storeName = this.dataset.storeName || 'this store';
            const form = this.closest('.store-settings-form');

            Swal.fire({
                title: 'Delete store?',
                text: `Delete "${storeName}"? Related store pincodes and zone links will also be removed.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3e1d',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const data = await deleteResource(url);
                    form?.remove();
                    document.getElementById('settings-toast-title').textContent = 'Store Deleted';
                    document.getElementById('settings-toast-body').textContent = data.message || 'Store deleted.';
                    toast.show();
                } catch (err) {
                    Swal.fire({ title: 'Delete failed', text: err.message, icon: 'error' });
                }
            });
        });
    });
});
</script>
@endsection
