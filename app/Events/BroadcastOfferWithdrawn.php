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
 * Sent to every other notified driver once one of them accepts (or an offer
 * expires), so their app can pull the offer off screen instead of letting
 * them tap "accept" on something already gone. Queued for the same
 * Reverb-resilience reason as BroadcastOfferCreated — the offer row's
 * status is already the source of truth by the time this is dispatched.
 */
class BroadcastOfferWithdrawn implements ShouldBroadcast
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
        return 'broadcast-offer.withdrawn';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'offer_id' => $this->offer->id,
            'order_code' => $this->offer->order?->code,
        ];
    }
}
