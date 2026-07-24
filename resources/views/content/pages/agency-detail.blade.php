@extends('layouts/contentNavbarLayout')

@php
  $canEdit = $canEdit ?? false;
  $isAdmin = auth()->user()->isAdmin();
@endphp

@section('title', 'Agency Details')
@section('page-title', 'Agency Details')

@section('content')

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
  <div>
    <a href="javascript:history.back()" class="text-muted small"><i class="bx bx-arrow-back"></i> Back</a>
    <h4 class="mb-1 mt-1" id="agency-title">Loading…</h4>
    <p class="text-muted mb-0" id="agency-subtitle"></p>
  </div>
  <div class="d-flex gap-2" id="agency-header-actions"></div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Company details</h5>
        @if($canEdit || $isAdmin)
          <button type="button" class="btn btn-sm btn-outline-primary" id="btn-edit-agency">Edit</button>
        @endif
      </div>
      <div class="card-body" id="agency-info">
        <p class="text-muted mb-0">Loading…</p>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Hubs & zone contracts</h5>
        @if($canEdit || $isAdmin)
          <button type="button" class="btn btn-sm btn-primary" id="btn-add-branch">Add hub</button>
        @endif
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Hub</th>
                <th>Included zones</th>
                <th>Per km</th>
                <th>Min charge</th>
                <th>Status</th>
                <th class="text-end" style="width: 56px;">Actions</th>
              </tr>
            </thead>
            <tbody id="branches-tbody">
              <tr><td colspan="6" class="text-muted text-center py-3">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Executives</h5>
        <button type="button" class="btn btn-sm btn-primary" id="btn-add-executive">Add executive</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Hubs</th>
                <th>Drivers</th>
                <th>Status</th>
                <th class="text-end" style="width: 56px;">Actions</th>
              </tr>
            </thead>
            <tbody id="executives-tbody">
              <tr><td colspan="6" class="text-muted text-center py-3">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Edit agency modal --}}
<div class="modal fade" id="editAgencyModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="editAgencyForm">
      <div class="modal-header">
        <h5 class="modal-title">Edit agency</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-md-6"><label class="form-label">Name *</label><input class="form-control" id="edit-name" required></div>
        <div class="col-md-6"><label class="form-label">GSTIN</label><input class="form-control" id="edit-gstin"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" id="edit-phone"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" id="edit-email" type="email"></div>
        <div class="col-12"><label class="form-label">Address line 1</label><input class="form-control" id="edit-address1"></div>
        <div class="col-12"><label class="form-label">Address line 2</label><input class="form-control" id="edit-address2"></div>
        <div class="col-md-4"><label class="form-label">City</label><input class="form-control" id="edit-city"></div>
        <div class="col-md-4"><label class="form-label">State</label><input class="form-control" id="edit-state"></div>
        <div class="col-md-4"><label class="form-label">Pincode</label><input class="form-control" id="edit-pincode"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

{{-- Branch modal (create multi) --}}
<div class="modal fade" id="branchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" id="branchForm">
      <div class="modal-header">
        <h5 class="modal-title">Add hub / contract</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-12">
          <label class="form-label">Hub name *</label>
          <input class="form-control" id="branch-name" required placeholder="e.g. Trivandrum Hub">
          <small class="text-muted">One hub can cover many zones under the same contract rates.</small>
        </div>
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label mb-0">Included zones * <small class="text-muted fw-normal">(multi-select)</small></label>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary" id="btn-zones-select-all">Select all</button>
              <button type="button" class="btn btn-outline-secondary" id="btn-zones-clear">Clear</button>
            </div>
          </div>
          <div id="branch-zones" class="border rounded p-3" style="max-height: 220px; overflow:auto;">
            @foreach($zones as $zone)
              <div class="form-check">
                <input class="form-check-input branch-zone-cb" type="checkbox" value="{{ $zone->id }}" id="bz-{{ $zone->id }}" data-zone-name="{{ $zone->name }}">
                <label class="form-check-label" for="bz-{{ $zone->id }}">{{ $zone->name }}</label>
              </div>
            @endforeach
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cost per km *</label>
          <input type="number" step="0.01" min="0" class="form-control" id="branch-cost" required value="0">
        </div>
        <div class="col-md-6">
          <label class="form-label">Minimum order charge *</label>
          <input type="number" step="0.01" min="0" class="form-control" id="branch-min" required value="0">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save hub</button>
      </div>
    </form>
  </div>
</div>

{{-- View hub modal --}}
<div class="modal fade" id="viewBranchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Hub details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="view-branch-body">
        <p class="text-muted mb-0">Loading…</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        @if($canEdit || $isAdmin)
          <button type="button" class="btn btn-primary" id="btn-view-branch-edit" hidden>Edit hub</button>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Edit single branch modal --}}
<div class="modal fade" id="editBranchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="editBranchForm">
      <div class="modal-header">
        <h5 class="modal-title">Edit hub / contract</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <input type="hidden" id="edit-branch-id" value="">
        <div class="col-12">
          <label class="form-label">Hub name *</label>
          <input class="form-control" id="edit-branch-name" required>
        </div>
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label mb-0">Included zones *</label>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary" id="btn-edit-zones-select-all">Select all</button>
              <button type="button" class="btn btn-outline-secondary" id="btn-edit-zones-clear">Clear</button>
            </div>
          </div>
          <div id="edit-branch-zones" class="border rounded p-3" style="max-height: 200px; overflow:auto;">
            @foreach($zones as $zone)
              <div class="form-check">
                <input class="form-check-input edit-branch-zone-cb" type="checkbox" value="{{ $zone->id }}" id="ebz-{{ $zone->id }}" data-zone-name="{{ $zone->name }}">
                <label class="form-check-label" for="ebz-{{ $zone->id }}">{{ $zone->name }}</label>
              </div>
            @endforeach
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cost per km *</label>
          <input type="number" step="0.01" min="0" class="form-control" id="edit-branch-cost" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Minimum order charge *</label>
          <input type="number" step="0.01" min="0" class="form-control" id="edit-branch-min" required>
        </div>
        <div class="col-12">
          <label class="form-label">Status</label>
          <select class="form-select" id="edit-branch-status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

{{-- Executive modal --}}
<div class="modal fade" id="executiveModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" id="executiveForm">
      <div class="modal-header">
        <h5 class="modal-title">Add executive</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-md-6"><label class="form-label">Name *</label><input class="form-control" id="exec-name" required></div>
        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" class="form-control" id="exec-email" required></div>
        <div class="col-md-6"><label class="form-label">Mobile</label><input class="form-control" id="exec-mobile"></div>
        <div class="col-md-6"><label class="form-label">Password</label><input type="text" class="form-control" id="exec-password" placeholder="Default: password@123"></div>
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label mb-0">Hubs * <small class="text-muted fw-normal">(multi-select)</small></label>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary" id="btn-exec-branches-select-all">Select all</button>
              <button type="button" class="btn btn-outline-secondary" id="btn-exec-branches-clear">Clear</button>
            </div>
          </div>
          <div id="exec-branches" class="border rounded p-3" style="max-height: 220px; overflow:auto;"></div>
          <small class="text-muted">Select one hub, several, or all hubs this executive will manage. Drivers can use any zone included in those hubs.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create executive</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const agencyId = @json($agencyId);
  const canEdit = @json($canEdit);
  const isAdmin = @json($isAdmin);
  let agency = null;

  const editModal = new bootstrap.Modal(document.getElementById('editAgencyModal'));
  const branchModal = new bootstrap.Modal(document.getElementById('branchModal'));
  const editBranchModal = new bootstrap.Modal(document.getElementById('editBranchModal'));
  const viewBranchModal = new bootstrap.Modal(document.getElementById('viewBranchModal'));
  const executiveModal = new bootstrap.Modal(document.getElementById('executiveModal'));
  const canManageBranches = canEdit || isAdmin;
  let viewingBranchId = null;

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  async function load() {
    try {
      const res = await fetch(`/agencies/${agencyId}/detail`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (!res.ok || !data.status) {
        alert(data.message || 'Failed to load agency');
        return;
      }
      agency = data.agency;
      render();
    } catch (err) {
      console.error(err);
      alert(err.message || 'Failed to load agency');
    }
  }

  function render() {
    document.getElementById('agency-title').textContent = agency.name;
    document.getElementById('agency-subtitle').textContent = `Status: ${agency.status} · Created by ${agency.creator_name || '—'}`;

    document.getElementById('agency-info').innerHTML = `
      <dl class="row mb-0">
        <dt class="col-sm-4 text-muted">Phone</dt><dd class="col-sm-8">${esc(agency.phone || '—')}</dd>
        <dt class="col-sm-4 text-muted">Email</dt><dd class="col-sm-8">${esc(agency.email || '—')}</dd>
        <dt class="col-sm-4 text-muted">GSTIN</dt><dd class="col-sm-8">${esc(agency.gstin || '—')}</dd>
        <dt class="col-sm-4 text-muted">Address</dt>
        <dd class="col-sm-8">${esc([agency.address_line1, agency.address_line2, agency.city, agency.state, agency.pincode].filter(Boolean).join(', ') || '—')}</dd>
      </dl>`;

    const branches = agency.branches || [];
    const btbody = document.getElementById('branches-tbody');
    if (!branches.length) {
      btbody.innerHTML = `<tr><td colspan="6" class="text-muted text-center py-3">No hubs yet. Add a hub and include the zones it covers.</td></tr>`;
    } else {
      btbody.innerHTML = branches.map((b) => {
        const zoneBadges = (b.zones || []).map((z) =>
          `<span class="badge bg-label-primary me-1 mb-1">${esc(z.name)}</span>`
        ).join('') || '—';
        const items = [
          `<li><button type="button" class="dropdown-item" data-view-branch="${b.id}"><i class="bx bx-show me-2"></i>View</button></li>`,
        ];
        if (canManageBranches) {
          items.push(`<li><button type="button" class="dropdown-item" data-edit-branch="${b.id}"><i class="bx bx-edit-alt me-2"></i>Edit</button></li>`);
          items.push(`<li><hr class="dropdown-divider"></li>`);
          items.push(`<li><button type="button" class="dropdown-item text-danger" data-del-branch="${b.id}"><i class="bx bx-trash me-2"></i>Delete</button></li>`);
        }
        return `<tr>
          <td class="fw-semibold">${esc(b.name)}</td>
          <td style="max-width: 280px;">${zoneBadges}</td>
          <td>₹${Number(b.cost_per_km).toFixed(2)}</td>
          <td>₹${Number(b.minimum_order_charge).toFixed(2)}</td>
          <td><span class="badge bg-label-${b.status === 'active' ? 'success' : 'secondary'}">${esc(b.status)}</span></td>
          <td class="text-end" style="width: 56px;">
            <div class="dropdown">
              <button type="button"
                class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                data-bs-toggle="dropdown"
                data-bs-boundary="viewport"
                aria-expanded="false"
                title="Actions">
                <i class="bx bx-dots-vertical-rounded fs-5"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">${items.join('')}</ul>
            </div>
          </td>
        </tr>`;
      }).join('');
    }

    const executives = agency.executives || [];
    const etbody = document.getElementById('executives-tbody');
    if (!executives.length) {
      etbody.innerHTML = `<tr><td colspan="6" class="text-muted text-center py-3">No executives yet.</td></tr>`;
    } else {
      etbody.innerHTML = executives.map((e) => `<tr class="executive-row" style="cursor:pointer;" data-exec-href="/agencies/executives/${e.id}">
        <td class="fw-semibold">${esc(e.name)}</td>
        <td>${esc(e.email)}</td>
        <td>${(e.branches || []).map((b) => esc(b.name) + (b.zone_names ? ` <small class="text-muted">(${esc(b.zone_names)})</small>` : '')).join('<br>') || '—'}</td>
        <td><span class="badge bg-label-info">${Number(e.drivers_count || 0)}</span></td>
        <td><span class="badge bg-label-success">${esc(e.status)}</span></td>
        <td class="text-end" style="width: 56px;">
          <div class="dropdown">
            <button type="button"
              class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
              data-bs-toggle="dropdown"
              data-bs-boundary="viewport"
              aria-expanded="false"
              title="Actions">
              <i class="bx bx-dots-vertical-rounded fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="/agencies/executives/${e.id}"><i class="bx bx-show me-2"></i>View drivers</a></li>
            </ul>
          </div>
        </td>
      </tr>`).join('');
    }

    const branchChecks = document.getElementById('exec-branches');
    branchChecks.innerHTML = branches.length
      ? branches.map((b) => `<div class="form-check">
          <input class="form-check-input exec-branch-cb" type="checkbox" value="${b.id}" id="eb-${b.id}">
          <label class="form-check-label" for="eb-${b.id}">${esc(b.name)} <small class="text-muted">(${esc(b.zone_names || 'no zones')})</small></label>
        </div>`).join('')
      : '<p class="text-muted mb-0">Add a hub before creating an executive.</p>';

    // Disable zones already covered by another hub (for create modal)
    const usedZoneIds = new Set();
    branches.forEach((b) => (b.zone_ids || []).forEach((id) => usedZoneIds.add(String(id))));
    document.querySelectorAll('.branch-zone-cb').forEach((cb) => {
      const used = usedZoneIds.has(String(cb.value));
      cb.disabled = used;
      cb.checked = false;
      const label = cb.closest('.form-check')?.querySelector('label');
      if (label) {
        label.classList.toggle('text-muted', used);
        label.textContent = used
          ? `${cb.dataset.zoneName} (in another hub)`
          : cb.dataset.zoneName;
      }
    });
  }

  document.getElementById('executives-tbody')?.addEventListener('click', (e) => {
    if (e.target.closest('a, button')) return;
    const href = e.target.closest('[data-exec-href]')?.dataset.execHref;
    if (href) window.location.href = href;
  });

  document.getElementById('btn-zones-select-all')?.addEventListener('click', () => {
    document.querySelectorAll('.branch-zone-cb:not(:disabled)').forEach((cb) => { cb.checked = true; });
  });
  document.getElementById('btn-zones-clear')?.addEventListener('click', () => {
    document.querySelectorAll('.branch-zone-cb:not(:disabled)').forEach((cb) => { cb.checked = false; });
  });
  document.getElementById('btn-exec-branches-select-all')?.addEventListener('click', () => {
    document.querySelectorAll('.exec-branch-cb').forEach((cb) => { cb.checked = true; });
  });
  document.getElementById('btn-exec-branches-clear')?.addEventListener('click', () => {
    document.querySelectorAll('.exec-branch-cb').forEach((cb) => { cb.checked = false; });
  });

  document.getElementById('btn-edit-agency')?.addEventListener('click', () => {
    document.getElementById('edit-name').value = agency.name || '';
    document.getElementById('edit-gstin').value = agency.gstin || '';
    document.getElementById('edit-phone').value = agency.phone || '';
    document.getElementById('edit-email').value = agency.email || '';
    document.getElementById('edit-address1').value = agency.address_line1 || '';
    document.getElementById('edit-address2').value = agency.address_line2 || '';
    document.getElementById('edit-city').value = agency.city || '';
    document.getElementById('edit-state').value = agency.state || '';
    document.getElementById('edit-pincode').value = agency.pincode || '';
    editModal.show();
  });

  document.getElementById('editAgencyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      name: document.getElementById('edit-name').value.trim(),
      gstin: document.getElementById('edit-gstin').value.trim(),
      phone: document.getElementById('edit-phone').value.trim(),
      email: document.getElementById('edit-email').value.trim(),
      address_line1: document.getElementById('edit-address1').value.trim(),
      address_line2: document.getElementById('edit-address2').value.trim(),
      city: document.getElementById('edit-city').value.trim(),
      state: document.getElementById('edit-state').value.trim(),
      pincode: document.getElementById('edit-pincode').value.trim(),
    };
    const res = await fetch(`/agencies/${agencyId}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.status) { alert(data.message || 'Update failed'); return; }
    editModal.hide();
    await load();
  });

  document.getElementById('btn-add-branch')?.addEventListener('click', () => {
    document.getElementById('branchForm').reset();
    document.querySelectorAll('.branch-zone-cb:not(:disabled)').forEach((cb) => { cb.checked = false; });
    const nameInput = document.getElementById('branch-name');
    if (nameInput && agency?.name) nameInput.value = agency.name;
    branchModal.show();
  });

  document.getElementById('branchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const zoneIds = [...document.querySelectorAll('.branch-zone-cb:checked:not(:disabled)')]
      .map((el) => Number(el.value));
    if (!zoneIds.length) {
      alert('Select at least one zone for this hub.');
      return;
    }
    const name = document.getElementById('branch-name').value.trim();
    if (!name) {
      alert('Enter a hub name.');
      return;
    }
    const payload = {
      zone_ids: zoneIds,
      name,
      cost_per_km: Number(document.getElementById('branch-cost').value),
      minimum_order_charge: Number(document.getElementById('branch-min').value),
    };
    const res = await fetch(`/agencies/${agencyId}/branches`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.status) {
      alert(data.message || Object.values(data.errors || {}).flat().join('\n') || 'Failed');
      return;
    }
    branchModal.hide();
    await load();
  });

  function openEditBranch(editId) {
    const branch = (agency.branches || []).find((b) => String(b.id) === String(editId));
    if (!branch) return;
    document.getElementById('edit-branch-id').value = branch.id;
    document.getElementById('edit-branch-name').value = branch.name || '';
    document.getElementById('edit-branch-cost').value = branch.cost_per_km ?? 0;
    document.getElementById('edit-branch-min').value = branch.minimum_order_charge ?? 0;
    document.getElementById('edit-branch-status').value = branch.status || 'active';

    const selected = new Set((branch.zone_ids || []).map(String));
    const usedElsewhere = new Set();
    (agency.branches || []).forEach((b) => {
      if (String(b.id) === String(branch.id)) return;
      (b.zone_ids || []).forEach((id) => usedElsewhere.add(String(id)));
    });

    document.querySelectorAll('.edit-branch-zone-cb').forEach((cb) => {
      const taken = usedElsewhere.has(String(cb.value));
      cb.disabled = taken;
      cb.checked = selected.has(String(cb.value));
      const label = cb.closest('.form-check')?.querySelector('label');
      if (label) {
        label.classList.toggle('text-muted', taken);
        label.textContent = taken
          ? `${cb.dataset.zoneName} (in another hub)`
          : cb.dataset.zoneName;
      }
    });
    editBranchModal.show();
  }

  function openViewBranch(viewId) {
    const branch = (agency.branches || []).find((b) => String(b.id) === String(viewId));
    if (!branch) return;
    viewingBranchId = branch.id;
    const zoneBadges = (branch.zones || []).map((z) =>
      `<span class="badge bg-label-primary me-1 mb-1">${esc(z.name)}</span>`
    ).join('') || '—';
    document.getElementById('view-branch-body').innerHTML = `
      <dl class="row mb-0">
        <dt class="col-sm-4 text-muted">Hub</dt>
        <dd class="col-sm-8 fw-semibold">${esc(branch.name)}</dd>
        <dt class="col-sm-4 text-muted">Zones</dt>
        <dd class="col-sm-8">${zoneBadges}</dd>
        <dt class="col-sm-4 text-muted">Cost per km</dt>
        <dd class="col-sm-8">₹${Number(branch.cost_per_km).toFixed(2)}</dd>
        <dt class="col-sm-4 text-muted">Min charge</dt>
        <dd class="col-sm-8">₹${Number(branch.minimum_order_charge).toFixed(2)}</dd>
        <dt class="col-sm-4 text-muted">Status</dt>
        <dd class="col-sm-8"><span class="badge bg-label-${branch.status === 'active' ? 'success' : 'secondary'}">${esc(branch.status)}</span></dd>
      </dl>`;
    const editBtn = document.getElementById('btn-view-branch-edit');
    if (editBtn) {
      editBtn.hidden = !canManageBranches;
    }
    viewBranchModal.show();
  }

  document.getElementById('btn-view-branch-edit')?.addEventListener('click', () => {
    if (!viewingBranchId) return;
    viewBranchModal.hide();
    openEditBranch(viewingBranchId);
  });

  document.getElementById('branches-tbody').addEventListener('click', async (e) => {
    const viewId = e.target.closest('[data-view-branch]')?.dataset.viewBranch;
    if (viewId) {
      openViewBranch(viewId);
      return;
    }

    const editId = e.target.closest('[data-edit-branch]')?.dataset.editBranch;
    if (editId) {
      openEditBranch(editId);
      return;
    }

    const id = e.target.closest('[data-del-branch]')?.dataset.delBranch;
    if (!id || !confirm('Delete this branch?')) return;
    const res = await fetch(`/agencies/branches/${id}`, {
      method: 'DELETE',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    const data = await res.json().catch(() => ({}));
    if (!data.status) { alert(data.message || 'Delete failed'); return; }
    await load();
  });

  document.getElementById('btn-edit-zones-select-all')?.addEventListener('click', () => {
    document.querySelectorAll('.edit-branch-zone-cb:not(:disabled)').forEach((cb) => { cb.checked = true; });
  });
  document.getElementById('btn-edit-zones-clear')?.addEventListener('click', () => {
    document.querySelectorAll('.edit-branch-zone-cb:not(:disabled)').forEach((cb) => { cb.checked = false; });
  });

  document.getElementById('editBranchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const branchId = document.getElementById('edit-branch-id').value;
    const zoneIds = [...document.querySelectorAll('.edit-branch-zone-cb:checked:not(:disabled)')]
      .map((el) => Number(el.value));
    if (!zoneIds.length) {
      alert('Select at least one zone.');
      return;
    }
    const payload = {
      name: document.getElementById('edit-branch-name').value.trim(),
      zone_ids: zoneIds,
      cost_per_km: Number(document.getElementById('edit-branch-cost').value),
      minimum_order_charge: Number(document.getElementById('edit-branch-min').value),
      status: document.getElementById('edit-branch-status').value,
    };
    const res = await fetch(`/agencies/branches/${branchId}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.status) {
      alert(data.message || Object.values(data.errors || {}).flat().join('\n') || 'Update failed');
      return;
    }
    editBranchModal.hide();
    await load();
  });

  document.getElementById('btn-add-executive').addEventListener('click', () => {
    document.getElementById('executiveForm').reset();
    executiveModal.show();
  });

  document.getElementById('executiveForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const branchIds = [...document.querySelectorAll('.exec-branch-cb:checked')].map((el) => Number(el.value));
    if (!branchIds.length) {
      alert('Select at least one zone branch.');
      return;
    }
    const payload = {
      name: document.getElementById('exec-name').value.trim(),
      email: document.getElementById('exec-email').value.trim(),
      mobile: document.getElementById('exec-mobile').value.trim(),
      password: document.getElementById('exec-password').value.trim() || undefined,
      branch_ids: branchIds,
    };
    const res = await fetch(`/agencies/${agencyId}/executives`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.status) {
      alert(data.message || Object.values(data.errors || {}).flat().join('\n') || 'Failed');
      return;
    }
    executiveModal.hide();
    await load();
  });

  load();
});
</script>
@endsection
