<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\dashboard\DashboardController;
use App\Http\Controllers\pages\LiveMapController;
use App\Http\Controllers\Fleet\StoreDriverController;
use App\Http\Controllers\Fleet\ZoneDriverController;
use App\Http\Controllers\Fleet\DriverPageController;
use App\Http\Controllers\Operations\BatchController;
use App\Http\Controllers\Operations\EarningsController;
use App\Http\Controllers\Operations\OrderController;
use App\Http\Controllers\Operations\PayoutController;
use App\Http\Controllers\Users\UserController;

// Guest authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginBasic::class, 'index'])->name('login');
    Route::post('/login', [LoginBasic::class, 'login']);
    Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('auth-login-basic');
    Route::post('/auth/login-basic', [LoginBasic::class, 'login'])->name('auth-login-basic.post');

    Route::get('/register', [RegisterBasic::class, 'index'])->name('register');
    Route::post('/register', [RegisterBasic::class, 'register']);
    Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('auth-register-basic');
    Route::post('/auth/register-basic', [RegisterBasic::class, 'register'])->name('auth-register-basic.post');

    Route::get('/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('auth-reset-password-basic');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [LoginBasic::class, 'logout'])->name('logout');
    Route::get('/auth/logout', [LoginBasic::class, 'logout'])->name('auth-logout');

    // Post-login entry point: redirects each role to its own dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin dashboard: non-admins are redirected to their own home page
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard-analytics');
});

// Store Admin routes
Route::middleware(['auth', 'role:store_admin'])->group(function () {
    Route::get('/store/dashboard', [DashboardController::class, 'storeAdmin'])->name('store-dashboard');
});

// User routes
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/user/dashboard', [DashboardController::class, 'user'])->name('user-dashboard');
});

// Admin-only pages (Deliverease admin panel)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/live-map', [LiveMapController::class, 'index'])->name('live-map');

    Route::get('/fleet/drivers/store', [StoreDriverController::class, 'index'])->name('fleet-drivers-store');
    Route::get('/fleet/drivers/store/list', [StoreDriverController::class, 'list'])->name('fleet-drivers-store.list');
    Route::post('/fleet/drivers/store', [StoreDriverController::class, 'store'])->name('fleet-drivers-store.store');
    Route::post('/fleet/drivers/store/{code}/update', [StoreDriverController::class, 'update'])->name('fleet-drivers-store.update');
    Route::delete('/fleet/drivers/store/{code}', [StoreDriverController::class, 'destroy'])->name('fleet-drivers-store.destroy');
    Route::post('/fleet/drivers/store/{code}/status', [StoreDriverController::class, 'updateStatus'])->name('fleet-drivers-store.status');

    Route::get('/fleet/drivers/zone', [ZoneDriverController::class, 'index'])->name('fleet-drivers-zone');
    Route::get('/fleet/drivers/zone/list', [ZoneDriverController::class, 'list'])->name('fleet-drivers-zone.list');
    Route::post('/fleet/drivers/zone', [ZoneDriverController::class, 'store'])->name('fleet-drivers-zone.store');
    Route::post('/fleet/drivers/zone/{code}/update', [ZoneDriverController::class, 'update'])->name('fleet-drivers-zone.update');
    Route::delete('/fleet/drivers/zone/{code}', [ZoneDriverController::class, 'destroy'])->name('fleet-drivers-zone.destroy');
    Route::post('/fleet/drivers/zone/{code}/status', [ZoneDriverController::class, 'updateStatus'])->name('fleet-drivers-zone.status');

    Route::get('/fleet/drivers/{id}/profile', [DriverPageController::class, 'profile'])->name('fleet-drivers-profile');

    Route::get('/fleet/drivers', function () {
        return redirect()->route('fleet-drivers-store');
    })->name('fleet-drivers');

    Route::get('/fleet/approvals', function () {
        return view('content.pages.approvals');
    })->name('fleet-approvals');

    Route::get('/fleet/approvals/{id}/review', [DriverPageController::class, 'review'])->name('fleet-approvals-review');

    Route::get('/operations/orders', [OrderController::class, 'index'])->name('operations-orders');
    Route::get('/operations/orders/{id}/completed', [OrderController::class, 'completed'])->name('operations-orders-completed');
    Route::get('/operations/orders/{id}', [OrderController::class, 'show'])->name('operations-orders-detail');

    Route::get('/operations/delivery-batches', [BatchController::class, 'index'])->name('operations-orders-batches');
    Route::get('/operations/delivery-batches/generate', [BatchController::class, 'generate'])->name('operations-orders-batches-generate');
    Route::get('/operations/delivery-batches/settings', [BatchController::class, 'settings'])->name('operations-orders-batches-settings');

    Route::get('/operations/earnings', [EarningsController::class, 'index'])->name('operations-earnings');
    Route::get('/operations/payouts', [PayoutController::class, 'index'])->name('operations-payouts');
    Route::get('/operations/payouts/drivers/{id}', [PayoutController::class, 'driver'])->name('operations-payouts-driver-detail');

    Route::get('/system/analytics', function () {
        return view('content.pages.analytics');
    })->name('system-analytics');

    Route::get('/system/users', [UserController::class, 'index'])->name('system-users');
    Route::post('/store/users', [UserController::class, 'store'])->name('user.store');
    Route::get('/users/list', [UserController::class, 'list'])->name('users.list');
    Route::get('/users/{id}/edit', [UserController::class,'edit'])->name('users.edit');
    Route::post('/users/{id}/update',[UserController::class,'update'])->name('users.update');
    Route::post('users/{id}/toggle-status',[UserController::class,'toggleStatus'])->name('users.toggleStatus');

    Route::get('/system/settings', function () {
        return view('content.pages.settings');
    })->name('system-settings');
});