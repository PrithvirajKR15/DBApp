@extends('layouts/contentNavbarLayout')

@php
  $isNavbar = false;
  $mode = $mode ?? 'all';
  $isAdmin = auth()->user()->isAdmin();
  $listStatus = $mode === 'pending' ? 'pending' : ($mode === 'approved' ? 'active' : '');
@endphp

@section('title', $pageTitle ?? 'Agencies')
@section('page-title', $pageTitle ?? 'Third Party Agencies')

@section('content')

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
  <div>
    <h4 class="mb-1">{{ $pageTitle ?? 'Third Party Agencies' }}</h4>
    <p class="text-muted mb-0">
      @if($mode === 'pending')
        Review Store Admin agency registration requests.
      @elseif($mode === 'approved')
        Active third-party agencies and their zone contracts.
      @else
        Register agencies, manage zone branches, and create executives.
      @endif
    </p>
  </div>
  <button type="button" class="btn btn-primary" id="btn-open-agency-modal">
    <i class="bx bx-plus me-1"></i> Register Agency
  </button>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" id="agencies-table">
        <thead>
          <tr>
            <th>Agency</th>
            <th>Contact</th>
            <th>City</th>
            <th>Branches</th>
            <th>Status</th>
            <th>Created by</th>
            <th class="text-end" style="width: 56px;"></th>
          </tr>
        </thead>
        <tbody id="agencies-tbody">
          <tr><td colspan="7" class="text-center text-muted py-4">Loading…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
      <div class="text-muted small" id="pagination-info"></div>
      <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
    </div>
  </div>
</div>

{{-- Create / Edit Agency Modal --}}
<div class="modal fade" id="agencyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="agencyForm">
        <div class="modal-header">
          <h5 class="modal-title" id="agencyModalTitle">Register Agency</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="agency-edit-id" value="">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Agency Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="agency-name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">GSTIN</label>
              <input type="text" class="form-control" id="agency-gstin">
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" id="agency-phone">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="agency-email">
            </div>
            <div class="col-12">
              <label class="form-label">Address line 1</label>
              <input type="text" class="form-control" id="agency-address1">
            </div>
            <div class="col-12">
              <label class="form-label">Address line 2</label>
              <input type="text" class="form-control" id="agency-address2">
            </div>
            <div class="col-md-4">
              <label class="form-label">City</label>
              <input type="text" class="form-control" id="agency-city">
            </div>
            <div class="col-md-4">
              <label class="form-label">State</label>
              <input type="text" class="form-control" id="agency-state" value="Kerala">
            </div>
            <div class="col-md-4">
              <label class="form-label">Pincode</label>
              <input type="text" class="form-control" id="agency-pincode">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="agency-save-btn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const listUrl = @json(route('agencies.list'));
  const storeUrl = @json(route('agencies.store'));
  const listStatus = @json($listStatus);
  const isAdmin = @json($isAdmin);
  const mode = @json($mode);
  const modalEl = document.getElementById('agencyModal');
  const agencyModal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;
  let agencies = [];
  let currentPage = 1;
  const perPage = 10;

  function statusBadge(status) {
    const map = { pending: 'warning', active: 'success', rejected: 'danger' };
    return `<span class="badge bg-label-${map[status] || 'secondary'}">${status}</span>`;
  }

  function detailUrl(id) {
    return mode === 'all' && !isAdmin
      ? `/store/agencies/${id}`
      : `/agencies/${id}`;
  }

  function renderPagination(meta) {
    const info = document.getElementById('pagination-info');
    const ul = document.getElementById('pagination');
    if (!meta || !meta.total) {
      if (info) info.textContent = '';
      if (ul) ul.innerHTML = '';
      return;
    }
    if (info) info.textContent = `Showing ${meta.from ?? 0}–${meta.to ?? 0} of ${meta.total}`;

    const current = meta.current_page;
    const last = meta.last_page;
    let html = `
      <li class="page-item ${current <= 1 ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0)" data-page="${current - 1}">Prev</a>
      </li>`;
    for (let i = 1; i <= last; i++) {
      if (last > 7 && Math.abs(i - current) > 1 && i !== 1 && i !== last) {
        if (i === 2 || i === last - 1) {
          html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        }
        continue;
      }
      html += `
        <li class="page-item ${i === current ? 'active' : ''}">
          <a class="page-link" href="javascript:void(0)" data-page="${i}">${i}</a>
        </li>`;
    }
    html += `
      <li class="page-item ${current >= last ? 'disabled' : ''}">
        <a class="page-link" href="javascript:void(0)" data-page="${current + 1}">Next</a>
      </li>`;
    if (ul) ul.innerHTML = html;
  }

  async function loadAgencies(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('agencies-tbody');
    try {
      const params = new URLSearchParams({ page: String(page), per_page: String(perPage) });
      if (listStatus) params.set('status', listStatus);
      const res = await fetch(`${listUrl}?${params}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      if (!res.ok) {
        throw new Error(`Failed to load agencies (${res.status})`);
      }
      const data = await res.json();
      agencies = data.agencies || [];
      renderTable();
      renderPagination(data.meta);
    } catch (err) {
      console.error(err);
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(err.message || 'Failed to load')}</td></tr>`;
      }
    }
  }

  function renderTable() {
    const tbody = document.getElementById('agencies-tbody');
    if (!tbody) return;
    if (!agencies.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No agencies found.</td></tr>`;
      return;
    }

    tbody.innerHTML = agencies.map((a) => {
      const items = [];
      items.push(`<li><a class="dropdown-item" href="${detailUrl(a.id)}"><i class="bx bx-show me-2"></i>View</a></li>`);

      if (a.can_edit) {
        items.push(`<li><button type="button" class="dropdown-item" data-edit="${a.id}"><i class="bx bx-edit-alt me-2"></i>Edit</button></li>`);
      }

      if (isAdmin && a.status === 'pending') {
        items.push(`<li><hr class="dropdown-divider"></li>`);
        items.push(`<li><button type="button" class="dropdown-item text-success" data-approve="${a.id}"><i class="bx bx-check-circle me-2"></i>Approve</button></li>`);
        items.push(`<li><button type="button" class="dropdown-item text-danger" data-reject="${a.id}"><i class="bx bx-x-circle me-2"></i>Reject</button></li>`);
      }

      return `<tr>
        <td class="fw-semibold">${escapeHtml(a.name)}</td>
        <td><div>${escapeHtml(a.phone || '—')}</div><small class="text-muted">${escapeHtml(a.email || '')}</small></td>
        <td>${escapeHtml(a.city || '—')}</td>
        <td>${a.branches_count ?? 0}</td>
        <td>${statusBadge(a.status)}</td>
        <td>${escapeHtml(a.creator_name || '—')}</td>
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

  function fillAgencyForm(a) {
    document.getElementById('agency-edit-id').value = a.id;
    document.getElementById('agency-name').value = a.name || '';
    document.getElementById('agency-gstin').value = a.gstin || '';
    document.getElementById('agency-phone').value = a.phone || '';
    document.getElementById('agency-email').value = a.email || '';
    document.getElementById('agency-address1').value = a.address_line1 || '';
    document.getElementById('agency-address2').value = a.address_line2 || '';
    document.getElementById('agency-city').value = a.city || '';
    document.getElementById('agency-state').value = a.state || 'Kerala';
    document.getElementById('agency-pincode').value = a.pincode || '';
    document.getElementById('agencyModalTitle').textContent = 'Edit Agency';
  }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  document.getElementById('btn-open-agency-modal')?.addEventListener('click', () => {
    document.getElementById('agencyForm')?.reset();
    const editId = document.getElementById('agency-edit-id');
    if (editId) editId.value = '';
    const title = document.getElementById('agencyModalTitle');
    if (title) title.textContent = 'Register Agency';
    const state = document.getElementById('agency-state');
    if (state) state.value = 'Kerala';
    agencyModal?.show();
  });

  document.getElementById('agencyForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      name: document.getElementById('agency-name').value.trim(),
      gstin: document.getElementById('agency-gstin').value.trim(),
      phone: document.getElementById('agency-phone').value.trim(),
      email: document.getElementById('agency-email').value.trim(),
      address_line1: document.getElementById('agency-address1').value.trim(),
      address_line2: document.getElementById('agency-address2').value.trim(),
      city: document.getElementById('agency-city').value.trim(),
      state: document.getElementById('agency-state').value.trim(),
      pincode: document.getElementById('agency-pincode').value.trim(),
    };

    const editId = document.getElementById('agency-edit-id').value;
    const url = editId ? `/agencies/${editId}/update` : storeUrl;
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.status) {
      alert(data.message || Object.values(data.errors || {}).flat().join('\n') || 'Save failed');
      return;
    }
    agencyModal?.hide();
    await loadAgencies(1);
  });

  document.getElementById('agencies-tbody')?.addEventListener('click', async (e) => {
    const editId = e.target.closest('[data-edit]')?.dataset.edit;
    const approveId = e.target.closest('[data-approve]')?.dataset.approve;
    const rejectId = e.target.closest('[data-reject]')?.dataset.reject;

    if (editId) {
      const agency = agencies.find((a) => String(a.id) === String(editId));
      if (!agency) return;
      fillAgencyForm(agency);
      agencyModal?.show();
      return;
    }

    if (approveId) {
      if (!confirm('Approve this agency?')) return;
      const res = await fetch(`/agencies/${approveId}/approve`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));
      if (!data.status) { alert(data.message || 'Approve failed'); return; }
      await loadAgencies(currentPage);
    }

    if (rejectId) {
      const reason = prompt('Rejection reason (optional):') ?? '';
      const res = await fetch(`/agencies/${rejectId}/reject`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ rejection_reason: reason }),
      });
      const data = await res.json().catch(() => ({}));
      if (!data.status) { alert(data.message || 'Reject failed'); return; }
      await loadAgencies(currentPage);
    }
  });

  document.getElementById('pagination')?.addEventListener('click', (e) => {
    const link = e.target.closest('[data-page]');
    if (!link || link.closest('.disabled, .active')) return;
    const page = Number(link.dataset.page);
    if (!page || page < 1) return;
    loadAgencies(page);
  });

  loadAgencies(1);
});
</script>
@endsection
