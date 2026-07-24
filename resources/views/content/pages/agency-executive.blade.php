@extends('layouts/contentNavbarLayout')

@section('title', 'Executive Drivers')
@section('page-title', 'Executive Drivers')

@section('content')

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
  <div>
    <a href="{{ $backUrl }}" class="text-muted small"><i class="bx bx-arrow-back"></i> Back to agency</a>
    <h4 class="mb-1 mt-1">{{ $executive['name'] ?? 'Executive' }}</h4>
    <p class="text-muted mb-0">
      {{ $agencyName }}
      @if(!empty($executive['email']))
        · {{ $executive['email'] }}
      @endif
    </p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted small">Drivers</div>
        <div class="fs-3 fw-bold" id="stat-drivers">{{ $executive['drivers_count'] ?? 0 }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-9">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small mb-2">Assigned hubs</div>
        @forelse(($executive['branches'] ?? []) as $branch)
          <span class="badge bg-label-primary me-1 mb-1">
            {{ $branch['name'] }}
            @if(!empty($branch['zone_names']))
              <span class="fw-normal">({{ $branch['zone_names'] }})</span>
            @endif
          </span>
        @empty
          <span class="text-muted">No hubs assigned</span>
        @endforelse
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Drivers</h5>
    <span class="text-muted small" id="drivers-meta-label"></span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Zone</th>
            <th>Hub</th>
            <th>Availability</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="drivers-tbody">
          <tr><td colspan="6" class="text-muted text-center py-4">Loading…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
      <div class="text-muted small" id="pagination-info"></div>
      <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const executiveId = @json($executiveId);
  const listUrl = @json(route('agencies.executives.drivers', ['executiveId' => $executiveId]));
  let currentPage = 1;
  const perPage = 10;

  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function renderPagination(meta) {
    const info = document.getElementById('pagination-info');
    const ul = document.getElementById('pagination');
    if (!meta || meta.total === 0) {
      info.textContent = '';
      ul.innerHTML = '';
      return;
    }

    info.textContent = `Showing ${meta.from ?? 0}–${meta.to ?? 0} of ${meta.total}`;
    document.getElementById('drivers-meta-label').textContent = `${meta.total} total`;
    document.getElementById('stat-drivers').textContent = meta.total;

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
    ul.innerHTML = html;
  }

  async function loadDrivers(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('drivers-tbody');
    try {
      const res = await fetch(`${listUrl}?page=${page}&per_page=${perPage}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.status) {
        throw new Error(data.message || `Failed to load drivers (${res.status})`);
      }

      const drivers = data.drivers || [];
      if (!drivers.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-muted text-center py-4">No drivers under this executive’s hubs yet.</td></tr>`;
      } else {
        tbody.innerHTML = drivers.map((d) => `
          <tr>
            <td class="fw-semibold">${esc(d.name)}</td>
            <td>${esc(d.phone || '—')}</td>
            <td>${esc(d.zone || '—')}</td>
            <td>${esc(d.agency_name || '—')}</td>
            <td>${esc(d.availability || '—')}</td>
            <td>${esc(d.status || '—')}</td>
          </tr>
        `).join('');
      }

      renderPagination(data.meta);
    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center py-4">${esc(err.message)}</td></tr>`;
    }
  }

  document.getElementById('pagination')?.addEventListener('click', (e) => {
    const link = e.target.closest('[data-page]');
    if (!link || link.closest('.disabled, .active')) return;
    const page = Number(link.dataset.page);
    if (!page || page < 1) return;
    loadDrivers(page);
  });

  loadDrivers(1);
});
</script>
@endsection
