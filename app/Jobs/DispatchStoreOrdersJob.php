<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\StoreDriverAssignmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued rather than synchronous: this runs the route optimizer (an O(n²)-
 * ish heuristic over pending orders/batches) and shouldn't block the web
 * request that created the order or freed up a driver. Fits the existing
 * Cloudways cron -> `queue:work --stop-when-empty` pattern — the cron just
 * needs to run frequently enough (recommend every minute) to drain these
 * promptly.
 *
 * Dispatched whenever: a new order is created (Order::booted()), or a
 * store driver's batch completes/cancels and frees them up
 * (BatchService::finishBatch()).
 */
class DispatchStoreOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Store $store) {}

    public function handle(StoreDriverAssignmentService $service): void
    {
        $service->dispatchPendingOrders($this->store);
    }
}
