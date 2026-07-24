<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\dashboard\DashboardController;
use App\Http\Controllers\pages\LiveMapController;
use App\Http\Controllers\Fleet\StoreDriverController;
use App\Http\Controllers\Fleet\ZoneDriverController;
use App\Http\Controllers\Fleet\ApprovalsController;
use App\Http\Controllers\Fleet\DriverPageController;
use App\Http\Controllers\Operations\BatchController;
use App\Http\Controllers\Operations\EarningsController;
use App\Http\Controllers\Operations\OrderController;
use App\Http\Controllers\Operations\PayoutController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Agencies\AgencyController;
use App\Http\Controllers\Executive\ExecutiveDashboardController;
use App\Http\Controllers\pages\SettingsController;

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

    // Shared agency branch lookup for zone-driver forms
    Route::get('/agencies/branches-for-zone', [AgencyController::class, 'branchesForZone'])
        ->middleware('role:admin,store_admin,executive')
        ->name('agencies.branches-for-zone');
});

// Store Admin routes
Route::middleware(['auth', 'role:store_admin'])->prefix('store')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'storeAdmin'])->name('store-dashboard');

    Route::get('/agencies', [AgencyController::class, 'index'])->name('store-agencies');
    Route::get('/agencies/{id}', [AgencyController::class, 'show'])->name('store-agencies.show');
});

// Executive routes
Route::middleware(['auth', 'role:executive'])->prefix('executive')->group(function () {
    Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('executive-dashboard');

    Route::get('/drivers', [ZoneDriverController::class, 'index'])->name('executive-drivers');
    Route::get('/drivers/list', [ZoneDriverController::class, 'list'])->name('executive-drivers.list');
    Route::post('/drivers', [ZoneDriverController::class, 'store'])->name('executive-drivers.store');
    Route::post('/drivers/{code}/update', [ZoneDriverController::class, 'update'])->name('executive-drivers.update');
    Route::delete('/drivers/{code}', [ZoneDriverController::class, 'destroy'])->name('executive-drivers.destroy');
    Route::post('/drivers/{code}/status', [ZoneDriverController::class, 'updateStatus'])->name('executive-drivers.status');
});

// User routes
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/user/dashboard', [DashboardController::class, 'user'])->name('user-dashboard');
});

// Agency management shared by Admin + Store Admin
Route::middleware(['auth', 'role:admin,store_admin'])->prefix('agencies')->group(function () {
    Route::get('/list', [AgencyController::class, 'list'])->name('agencies.list');
    Route::post('/', [AgencyController::class, 'store'])->name('agencies.store');
    Route::get('/{id}/detail', [AgencyController::class, 'detail'])->name('agencies.detail');
    Route::post('/{id}/update', [AgencyController::class, 'update'])->name('agencies.update');
    Route::delete('/{id}', [AgencyController::class, 'destroy'])->name('agencies.destroy');

    Route::post('/{id}/branches', [AgencyController::class, 'storeBranch'])->name('agencies.branches.store');
    Route::post('/branches/{branchId}/update', [AgencyController::class, 'updateBranch'])->name('agencies.branches.update');
    Route::delete('/branches/{branchId}', [AgencyController::class, 'destroyBranch'])->name('agencies.branches.destroy');

    Route::post('/{id}/executives', [AgencyController::class, 'storeExecutive'])->name('agencies.executives.store');
    Route::get('/executives/{executiveId}', [AgencyController::class, 'showExecutive'])->name('agencies.executives.show');
    Route::get('/executives/{executiveId}/drivers', [AgencyController::class, 'executiveDrivers'])->name('agencies.executives.drivers');
    Route::post('/executives/{executiveId}/branches', [AgencyController::class, 'syncExecutiveBranches'])->name('agencies.executives.branches');
});

// Admin-only pages (Deliverease admin panel)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/live-map', [LiveMapController::class, 'index'])->name('live-map');

    Route::prefix('agencies')->group(function () {
        Route::get('/pending', [AgencyController::class, 'pending'])->name('agencies-pending');
        Route::get('/approved', [AgencyController::class, 'approved'])->name('agencies-approved');
        Route::get('/{id}', [AgencyController::class, 'show'])->name('agencies.show');
        Route::post('/{id}/approve', [AgencyController::class, 'approve'])->name('agencies.approve');
        Route::post('/{id}/reject', [AgencyController::class, 'reject'])->name('agencies.reject');
    });

    Route::prefix('fleet')->group(function () {
        Route::get('/drivers/store', [StoreDriverController::class, 'index'])->name('fleet-drivers-store');
        Route::get('/drivers/store/list', [StoreDriverController::class, 'list'])->name('fleet-drivers-store.list');
        Route::post('/drivers/store', [StoreDriverController::class, 'store'])->name('fleet-drivers-store.store');
        Route::post('/drivers/store/{code}/update', [StoreDriverController::class, 'update'])->name('fleet-drivers-store.update');
        Route::delete('/drivers/store/{code}', [StoreDriverController::class, 'destroy'])->name('fleet-drivers-store.destroy');
        Route::post('/drivers/store/{code}/status', [StoreDriverController::class, 'updateStatus'])->name('fleet-drivers-store.status');

        Route::get('/drivers/zone', [ZoneDriverController::class, 'index'])->name('fleet-drivers-zone');
        Route::get('/drivers/zone/list', [ZoneDriverController::class, 'list'])->name('fleet-drivers-zone.list');
        Route::post('/drivers/zone', [ZoneDriverController::class, 'store'])->name('fleet-drivers-zone.store');
        Route::post('/drivers/zone/{code}/update', [ZoneDriverController::class, 'update'])->name('fleet-drivers-zone.update');
        Route::delete('/drivers/zone/{code}', [ZoneDriverController::class, 'destroy'])->name('fleet-drivers-zone.destroy');
        Route::post('/drivers/zone/{code}/status', [ZoneDriverController::class, 'updateStatus'])->name('fleet-drivers-zone.status');

        Route::get('/drivers/{id}/profile', [DriverPageController::class, 'profile'])->name('fleet-drivers-profile');
        Route::post('/drivers/{id}/profile', [DriverPageController::class, 'updateProfile'])->name('fleet-drivers-profile.update');
        Route::post('/drivers/{id}/status', [DriverPageController::class, 'updateStatus'])->name('fleet-drivers-profile.status');

        Route::get('/drivers', function () {
            return redirect()->route('fleet-drivers-store');
        })->name('fleet-drivers');

        Route::get('/approvals', [ApprovalsController::class, 'index'])->name('fleet-approvals');
        Route::get('/approvals/list', [ApprovalsController::class, 'list'])->name('fleet-approvals.list');
        Route::post('/approvals/bulk-status', [ApprovalsController::class, 'bulkUpdateStatus'])->name('fleet-approvals.bulk-status');
        Route::post('/approvals/{code}/status', [ApprovalsController::class, 'updateStatus'])->name('fleet-approvals.status');

        Route::get('/approvals/{id}/review', [DriverPageController::class, 'review'])->name('fleet-approvals-review');
    });

    Route::prefix('operations')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('operations-orders');
        Route::get('/orders/{id}/completed', [OrderController::class, 'completed'])->name('operations-orders-completed');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('operations-orders-detail');

        Route::get('/delivery-batches', [BatchController::class, 'index'])->name('operations-orders-batches');
        Route::get('/delivery-batches/generate', [BatchController::class, 'generate'])->name('operations-orders-batches-generate');
        Route::get('/delivery-batches/settings', [BatchController::class, 'settings'])->name('operations-orders-batches-settings');
        Route::post('/delivery-batches', [BatchController::class, 'store'])->name('operations-orders-batches.store');
        Route::post('/delivery-batches/settings', [BatchController::class, 'saveSettings'])->name('operations-orders-batches.settings.save');
        Route::post('/delivery-batches/{code}/assign', [BatchController::class, 'assign'])->name('operations-orders-batches.assign');
        Route::post('/delivery-batches/move-order', [BatchController::class, 'moveOrder'])->name('operations-orders-batches.move-order');
        Route::post('/delivery-batches/{code}/reorder-stops', [BatchController::class, 'reorderStops'])->name('operations-orders-batches.reorder-stops');
        Route::delete('/delivery-batches/groups/{code}', [BatchController::class, 'destroyGroup'])->name('operations-orders-batches.groups.destroy');
        Route::delete('/delivery-batches/{code}', [BatchController::class, 'destroy'])->name('operations-orders-batches.destroy');
        Route::post('/delivery-batches/{code}/complete', [BatchController::class, 'complete'])->name('operations-orders-batches.complete');
        Route::post('/delivery-batches/{code}/cancel', [BatchController::class, 'cancel'])->name('operations-orders-batches.cancel');

        Route::get('/earnings', [EarningsController::class, 'index'])->name('operations-earnings');
        Route::get('/payouts', [PayoutController::class, 'index'])->name('operations-payouts');
        Route::get('/payouts/drivers/{id}', [PayoutController::class, 'driver'])->name('operations-payouts-driver-detail');
    });

    Route::get('/system/analytics', function () {
        return view('content.pages.analytics');
    })->name('system-analytics');

    Route::get('/system/users', [UserController::class, 'index'])->name('system-users');
    Route::post('/store/users', [UserController::class, 'store'])->name('user.store');
    Route::get('/users/list', [UserController::class, 'list'])->name('users.list');
    Route::get('/users/{id}/edit', [UserController::class,'edit'])->name('users.edit');
    Route::post('/users/{id}/update',[UserController::class,'update'])->name('users.update');
    Route::post('users/{id}/toggle-status',[UserController::class,'toggleStatus'])->name('users.toggleStatus');

    Route::get('/system/settings', [SettingsController::class, 'index'])->name('system-settings');
    Route::post('/system/settings/zones/{zone}/pincodes', [SettingsController::class, 'saveZonePincodes'])
        ->name('system-settings.zones.pincodes');
    Route::delete('/system/settings/zones/{zone}', [SettingsController::class, 'destroyZone'])
        ->name('system-settings.zones.destroy');
    Route::post('/system/settings/stores/{store}', [SettingsController::class, 'saveStore'])
        ->name('system-settings.stores.save');
    Route::delete('/system/settings/stores/{store}', [SettingsController::class, 'destroyStore'])
        ->name('system-settings.stores.destroy');
});