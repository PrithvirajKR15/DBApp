<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BroadcastOffer;
use App\Services\BroadcastDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Minimal driver mobile-app surface for the broadcast fallback pool. Store
 * drivers never hit this — their batches are pushed to them from the
 * Operations/Fleet dashboard.
 */
class BroadcastOfferController extends Controller
{
    public function __construct(
        protected BroadcastDispatchService $broadcasts
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (! $driver) {
            return response()->json(['message' => 'No driver profile for this account.'], 403);
        }

        $offers = BroadcastOffer::with('order')
            ->where('driver_id', $driver->id)
            ->where('status', BroadcastOffer::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->latest('notified_at')
            ->get()
            ->map(fn (BroadcastOffer $offer) => [
                'offer_id' => $offer->id,
                'order_code' => $offer->order->code,
                'customer' => $offer->order->customer,
                'address' => $offer->order->address,
                'value' => $offer->order->value,
                'lat' => $offer->order->lat,
                'lng' => $offer->order->lng,
                'expires_at' => $offer->expires_at?->toIso8601String(),
            ]);

        return response()->json(['offers' => $offers]);
    }

    public function accept(Request $request, BroadcastOffer $offer): JsonResponse
    {
        $driver = $request->user()->driver;

        if (! $driver) {
            return response()->json(['message' => 'No driver profile for this account.'], 403);
        }

        try {
            $order = $this->broadcasts->acceptOffer($offer, $driver);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 409);
        }

        return response()->json([
            'message' => 'Offer accepted.',
            'order' => [
                'code' => $order->code,
                'customer' => $order->customer,
                'address' => $order->address,
                'value' => $order->value,
            ],
        ]);
    }
}
