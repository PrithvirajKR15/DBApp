@extends('layouts/contentNavbarLayout')

@section('title', 'Store Dashboard')
@section('page-title', 'Store Dashboard')

@section('content')
<div class="container-xxl px-0">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
      <h4 class="mb-1">Store Dashboard</h4>
      <p class="text-muted mb-0">Welcome back, {{ Auth::user()->name }}</p>
    </div>
    <a href="{{ route('store-agencies') }}" class="btn btn-primary">
      <i class="bx bx-buildings me-1"></i> Third Party Agencies
    </a>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="mb-2">Agencies</h5>
          <p class="text-muted mb-3">Register third-party agencies for your store zones. New agencies need Admin approval before they go active.</p>
          <a href="{{ route('store-agencies') }}" class="btn btn-outline-primary btn-sm">Open agencies</a>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="mb-2">Executives</h5>
          <p class="text-muted mb-3">After an agency is approved (or while viewing available agencies), open the agency detail page to add zone-branch executives.</p>
          <a href="{{ route('store-agencies') }}" class="btn btn-outline-primary btn-sm">Manage via agencies</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
