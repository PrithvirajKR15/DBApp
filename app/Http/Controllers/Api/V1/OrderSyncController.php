<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderSyncController extends Controller
{
    public function __construct(
        protected OrderIngestionService $ingestion
    ) {}

    /**
     * POST /api/v1/orders/sync
     *
     * External checkout app pushes an order that already carries store_id.
     * We persist the store FK as-is (resolved from external_store_id), geocode
     * the address, and leave dispatch to READY_FOR_PICKUP / admin assign.
     */
    public function sync(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'external_order_id' => ['required', 'string', 'max:100'],
            'store_id' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'full_address' => ['required', 'string', 'max:1000'],
            'pincode' => ['required', 'string', 'max:16'],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.code' => ['required_with:items', 'string', 'max:100'],
            'items.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.qty' => ['sometimes', 'numeric', 'min:0.001'],
            'items.*.quantity' => ['sometimes', 'numeric', 'min:0.001'],
            'items.*.price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['sometimes', 'nullable', 'string', 'max:40'],
            'items.*.category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'items.*.status' => ['sometimes', 'nullable', 'string', 'max:40'],
            'items.*.icon' => ['sometimes', 'nullable', 'string', 'max:40'],
            'payment' => ['sometimes', 'nullable', 'string', 'max:50'],
            'urgent' => ['sometimes', 'boolean'],
            'customer_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'phone_alt' => ['sometimes', 'nullable', 'string', 'max:40'],
            'landmark' => ['sometimes', 'nullable', 'string', 'max:255'],
            'instructions' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'packages' => ['sometimes', 'nullable', 'string', 'max:100'],
            'weight' => ['sometimes', 'nullable', 'string', 'max:40'],
            'card_last4' => ['sometimes', 'nullable', 'string', 'max:4'],
            'vip' => ['sometimes', 'boolean'],
        ]);

        try {
            $order = $this->ingestion->sync($payload);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Order sync failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Order synced.',
            'order' => [
                'id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'code' => $order->code,
                'store_id' => $order->store?->external_store_id,
                'internal_store_id' => $order->store_id,
                'status' => $order->status,
                'pincode' => $order->pincode,
                'lat' => $order->lat,
                'lng' => $order->lng,
                'geocode_status' => $order->geocode_status,
                'item_count' => (int) $order->items,
                'items' => $order->line_items ?? [],
            ],
        ], 201);
    }

    /**
     * POST /api/v1/orders/{order}/ready-for-pickup
     *
     * Triggers third-party pincode broadcast via Order observer.
     */
    public function readyForPickup(int $order): JsonResponse
    {
        $model = \App\Models\Order::findOrFail($order);

        try {
            $updated = $this->ingestion->markReadyForPickup($model);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Cannot mark ready for pickup.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Order marked ready for pickup; third-party broadcast queued.',
            'order' => [
                'id' => $updated->id,
                'status' => $updated->status,
                'pincode' => $updated->pincode,
            ],
        ]);
    }
}
