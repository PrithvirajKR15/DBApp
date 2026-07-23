<?php

namespace App\Jobs;

use App\Services\BroadcastDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Run every minute from routes/console.php's scheduler (which itself needs
 * `schedule:run` on a cron tick — same cron that also drives
 * `queue:work --stop-when-empty`). Expires stale pending offers and retries
 * broadcasting any order left without a driver and without any pending
 * offers.
 */
class ExpireBroadcastOffersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(BroadcastDispatchService $service): void
    {
        $service->expireStaleOffers();
    }
}
