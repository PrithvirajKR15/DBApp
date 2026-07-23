<?php

namespace App\Events;

use App\Models\BroadcastOffer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a single third-party driver's private channel when they're
 * offered a broadcast (single-order) delivery.
 *
 * Queued (ShouldBroadcast, not ShouldBroadcastNow): if Reverb is briefly
 * unreachable, this retries on the queue's own backoff instead of throwing
 * inside BroadcastDispatchService::broadcast() and aborting the rest of the
 * dispatch — the offer row already exists and is the source of truth
 * (drivers can also just poll GET /api/driver/broadcast-offers), the push
 * is a convenience notification on top of it, not the only path to it.
 */
class BroadcastOfferCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BroadcastOffer $offer) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('driver.'.$this->offer->driver_id)];
    }

    public function broadcastAs(): string
    {
        return 'broadcast-offer.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $order = $this->offer->order;

        return [
            'offer_id' => $this->offer->id,
            'order_code' => $order->code,
            'customer' => $order->customer,
            'address' => $order->address,
            'value' => $order->value,
            'lat' => $order->lat,
            'lng' => $order->lng,
            'expires_at' => $this->offer->expires_at?->toIso8601String(),
        ];
    }
}
