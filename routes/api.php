<?php

use App\Http\Controllers\Api\BroadcastOfferController;
use App\Http\Controllers\Api\V1\AdminDispatchController;
use App\Http\Controllers\Api\V1\OrderAcceptController;
use App\Http\Controllers\Api\V1\OrderSyncController;
use App\Http\Controllers\Api\V1\StoreSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner ingest API (shared token) — /api/v1/*
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('partner.token')->group(function () {
    Route::post('/stores/sync', [StoreSyncController::class, 'sync'])
        ->name('api.v1.stores.sync');
    Route::get('/stores', [StoreSyncController::class, 'index'])
        ->name('api.v1.stores.index');

    Route::post('/orders/sync', [OrderSyncController::class, 'sync'])
        ->name('api.v1.orders.sync');
    Route::post('/orders/{order}/ready-for-pickup', [OrderSyncController::class, 'readyForPickup'])
        ->name('api.v1.orders.ready-for-pickup');
});

/*
|--------------------------------------------------------------------------
| Driver-facing API (Sanctum token auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('driver')->group(function () {
        Route::get('/broadcast-offers', [BroadcastOfferController::class, 'index'])
            ->name('api.driver.broadcast-offers.index');

        Route::post('/broadcast-offers/{offer}/accept', [BroadcastOfferController::class, 'accept'])
            ->name('api.driver.broadcast-offers.accept');
    });

    // Atomic claim: POST /api/v1/orders/{id}/accept
    Route::post('/v1/orders/{order}/accept', [OrderAcceptController::class, 'accept'])
        ->name('api.v1.orders.accept');

    // Admin / store-manager dispatch helpers
    Route::prefix('v1/admin')->middleware('role:admin,store_admin')->group(function () {
        Route::post('/stores', [AdminDispatchController::class, 'upsertStore'])
            ->name('api.v1.admin.stores.upsert');
        Route::post('/orders/{order}/assign-driver', [AdminDispatchController::class, 'assignDriver'])
            ->name('api.v1.admin.orders.assign-driver');
        Route::put('/zones/{zone}/pincodes', [AdminDispatchController::class, 'syncZonePincodes'])
            ->name('api.v1.admin.zones.pincodes');
    });
});
