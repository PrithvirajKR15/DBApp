<?php

namespace App\Services;

use App\Events\BroadcastOfferCreated;
use App\Events\BroadcastOfferWithdrawn;
use App\Models\BatchSetting;
use App\Models\BroadcastOffer;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fallback path: notifies eligible third-party drivers of a single-order
 * delivery and handles the race-condition-safe "first to accept wins" flow.
 *
 * Broadcast orders are permanently single-order — once assignment_type is
 * set to 'broadcast' it never changes, and Order::canBeBatched() will
 * always return false for it (enforced again defensively in
 * BatchService::storeGeneratedBatches / StoreDriverAssignmentService).
 */
class BroadcastDispatchService
{
    public function __construct(
        protected BatchRouteOptimizerService $geo
    ) {}

    public function broadcast(Order $order): void
    {
        // Defensive: a store-batched order must never reach the broadcast
        // path. This should be unreachable given upstream checks.
        if ($order->assignment_type === Order::ASSIGNMENT_STORE_BATCH) {
            return;
        }

        $settings = BatchSetting::query()->first();
        $radiusKm = (float) ($settings?->broadcast_radius_km ?? 5);
        $offerSeconds = max(15, (int) ($settings?->broadcast_offer_seconds ?? 90));

        $order->update([
            'status' => Order::STATUS_BROADCASTING,
            'assignment_type' => Order::ASSIGNMENT_BROADCAST,
        ]);

        $eligibleDrivers = $this->eligibleDrivers($order, $radiusKm);

        if ($eligibleDrivers->isEmpty()) {
            // No one to notify right now (all offline, or none in range).
            // Leave it broadcasting — the offer-expiry job retries this
            // periodically against whichever drivers are online then.
            return;
        }

        $expiresAt = now()->addSeconds($offerSeconds);

        foreach ($eligibleDrivers as $driver) {
            $offer = BroadcastOffer::where('order_id', $order->id)
                ->where('driver_id', $driver->id)
                ->first();

            // Never re-offer to a driver who already turned it down or
            // whose acceptance already stuck (defensive, shouldn't happen).
            if ($offer && in_array($offer->status, [BroadcastOffer::STATUS_ACCEPTED, BroadcastOffer::STATUS_REJECTED], true)) {
                continue;
            }

            $offer = BroadcastOffer::updateOrCreate(
                ['order_id' => $order->id, 'driver_id' => $driver->id],
                [
                    'status' => BroadcastOffer::STATUS_PENDING,
                    'notified_at' => now(),
                    'expires_at' => $expiresAt,
                    'responded_at' => null,
                ]
            );

            BroadcastOfferCreated::dispatch($offer);
        }
    }

    /**
     * Atomically accept an offer. First driver in wins; every other
     * request for the same order is rejected. Race-safety comes from
     * `lockForUpdate()` on the order row: two concurrent transactions
     * accepting different offers for the *same* order will serialize on
     * that row lock — the second to acquire it re-reads driver_id, sees the
     * first winner already assigned, and fails cleanly (no double-accept).
     * The conditional `where('status', pending)->update()` on the offer row
     * itself is a second, redundant guard against the same offer being
     * accepted twice.
     *
     * @throws ValidationException if the offer (or the order) is no longer available
     */
    public function acceptOffer(BroadcastOffer $offer, Driver $driver): Order
    {
        if ((int) $offer->driver_id !== (int) $driver->id) {
            throw ValidationException::withMessages([
                'offer' => 'This offer was not sent to you.',
            ]);
        }

        return DB::transaction(function () use ($offer) {
            $order = Order::where('id', $offer->order_id)->lockForUpdate()->first();

            if (! $order || $order->driver_id !== null) {
                throw ValidationException::withMessages([
                    'offer' => 'This offer is no longer available.',
                ]);
            }

            $accepted = BroadcastOffer::where('id', $offer->id)
                ->where('status', BroadcastOffer::STATUS_PENDING)
                ->update([
                    'status' => BroadcastOffer::STATUS_ACCEPTED,
                    'responded_at' => now(),
                ]);

            if ($accepted === 0) {
                throw ValidationException::withMessages([
                    'offer' => 'This offer is no longer available.',
                ]);
            }

            $order->update([
                'driver_id' => $offer->driver_id,
                'status' => Order::STATUS_ASSIGNED,
                'delivery' => 'assigned',
            ]);

            $siblingOffers = BroadcastOffer::where('order_id', $order->id)
                ->where('id', '!=', $offer->id)
                ->where('status', BroadcastOffer::STATUS_PENDING)
                ->get();

            BroadcastOffer::whereIn('id', $siblingOffers->pluck('id'))
                ->update(['status' => BroadcastOffer::STATUS_EXPIRED, 'responded_at' => now()]);

            $siblingOffers->each(fn (BroadcastOffer $sibling) => BroadcastOfferWithdrawn::dispatch($sibling));

            return $order->fresh();
        });
    }

    /**
     * Expire offers past their window, then retry broadcasting any order
     * that's still unassigned and has no pending offers left.
     */
    public function expireStaleOffers(): void
    {
        $stale = BroadcastOffer::where('status', BroadcastOffer::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->get();

        if ($stale->isEmpty()) {
            return;
        }

        BroadcastOffer::whereIn('id', $stale->pluck('id'))
            ->update(['status' => BroadcastOffer::STATUS_EXPIRED, 'responded_at' => now()]);

        $stale->each(fn (BroadcastOffer $offer) => BroadcastOfferWithdrawn::dispatch($offer));

        Order::where('status', Order::STATUS_BROADCASTING)
            ->whereNull('driver_id')
            ->whereDoesntHave('broadcastOffers', fn ($q) => $q->where('status', BroadcastOffer::STATUS_PENDING))
            ->get()
            ->each(fn (Order $order) => $this->broadcast($order));
    }

    /**
     * @return Collection<int, Driver>
     */
    protected function eligibleDrivers(Order $order, float $radiusKm): Collection
    {
        $drivers = Driver::query()
            ->availableForBroadcast()
            ->with(['user', 'latestLocation'])
            ->get();

        if ($order->lat === null || $order->lng === null || $drivers->isEmpty()) {
            return $drivers;
        }

        $target = ['lat' => (float) $order->lat, 'lng' => (float) $order->lng];

        $inRange = $drivers->filter(function (Driver $driver) use ($target, $radiusKm) {
            $location = $driver->latestLocation;

            if (! $location) {
                return false;
            }

            $distance = $this->geo->roadKm($target, ['lat' => (float) $location->lat, 'lng' => (float) $location->lng]);

            return $distance <= $radiusKm;
        })->values();

        // Degrade gracefully: if nobody has a recent location fix (rather
        // than "nobody is in range"), notify the full available pool
        // instead of silently notifying no one.
        if ($inRange->isEmpty() && $drivers->every(fn (Driver $d) => ! $d->latestLocation)) {
            return $drivers;
        }

        return $inRange;
    }
}
