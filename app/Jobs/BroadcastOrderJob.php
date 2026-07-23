<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\BroadcastDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued so the web request (or the DispatchStoreOrdersJob run) that decided
 * this order overflows to broadcast doesn't wait on notification fan-out.
 * SerializesModels re-fetches a fresh Order when the job runs, so we always
 * act on current data even if this sits in the queue for a moment.
 */
class BroadcastOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Order $order) {}

    public function handle(BroadcastDispatchService $service): void
    {
        $service->broadcast($this->order);
    }
}
