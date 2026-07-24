@extends('layouts/contentNavbarLayout')

@section('title', 'Executive Dashboard')
@section('page-title', 'Executive Dashboard')

@section('content')
<div class="mb-4">
  <h4 class="mb-1">Welcome, {{ Auth::user()->name }}</h4>
  <p class="text-muted mb-0">
    @if($executive?->agency)
      Managing <strong>{{ $executive->agency->name }}</strong>
      @if($branches->isNotEmpty())
        · Branches: {{ $branches->pluck('name')->join(', ') }}
      @endif
    @else
      Your executive profile is not linked to an agency yet.
    @endif
  </p>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted small">Total drivers</div>
        <div class="fs-3 fw-bold">{{ $stats['drivers_total'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted small">Online</div>
        <div class="fs-3 fw-bold text-success">{{ $stats['drivers_online'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted small">In transit</div>
        <div class="fs-3 fw-bold text-warning">{{ $stats['drivers_transit'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted small">Completed orders</div>
        <div class="fs-3 fw-bold">{{ $stats['orders_completed'] }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Recent drivers</h5>
    <a href="{{ route('executive-drivers') }}" class="btn btn-sm btn-primary">Manage drivers</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Zone</th>
            <th>Agency branch</th>
            <th>Availability</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($drivers as $driver)
            <tr>
              <td class="fw-semibold">{{ $driver['name'] }}</td>
              <td>{{ $driver['zone'] ?? '—' }}</td>
              <td>{{ $driver['agency_name'] ?? '—' }}</td>
              <td>{{ $driver['availability'] ?? '—' }}</td>
              <td>{{ $driver['status'] ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">No drivers yet. Add drivers from My Drivers.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
