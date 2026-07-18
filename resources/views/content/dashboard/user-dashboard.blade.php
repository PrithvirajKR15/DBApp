@extends('layouts/blankLayout')

@section('title', 'My Dashboard')

@section('content')
<div class="container-xxl container-p-y">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1">My Dashboard</h4>
                    <p class="text-muted mb-0">Welcome back, {{ Auth::user()->name }} ({{ Auth::user()->role->name }})</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="icon-base bx bx-log-out me-1"></i> Logout
                    </button>
                </form>
            </div>
            <div class="alert alert-info mb-0" role="alert">
                The user area is under development. Your orders and account details will appear here.
            </div>
        </div>
    </div>
</div>
@endsection
