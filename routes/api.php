<?php

use App\Http\Controllers\Api\BroadcastOfferController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver-facing API (Sanctum token auth)
|--------------------------------------------------------------------------
|
| Minimal surface for the driver mobile app to receive and act on
| broadcast (third-party) delivery offers. Store-driver batch delivery is
| managed entirely from the Operations/Fleet admin dashboard (web routes).
*/
Route::middleware('auth:sanctum')->prefix('driver')->group(function () {
    Route::get('/broadcast-offers', [BroadcastOfferController::class, 'index'])
        ->name('api.driver.broadcast-offers.index');

    Route::post('/broadcast-offers/{offer}/accept', [BroadcastOfferController::class, 'accept'])
        ->name('api.driver.broadcast-offers.accept');
});
