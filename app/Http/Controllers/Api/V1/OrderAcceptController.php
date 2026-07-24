<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BroadcastDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Atomic first-to-claim endpoint for third-party drivers.
 * POST /api/v1/orders/{order}/accept
 */
class OrderAcceptController extends Controller
{
    public function __construct(
        protected BroadcastDispatchService $broadcasts
    ) {}

    public function accept(Request $request, Order $order): JsonResponse
    {
        $driver = $request->user()?->driver;

        if (! $driver) {
            return response()->json(['message' => 'No driver profile for this account.'], 403);
        }

        if (! $driver->isThirdPartyDriver()) {
            return response()->json([
                'message' => 'Only third-party drivers can accept broadcast orders.',
            ], 403);
        }

        try {
            $accepted = $this->broadcasts->acceptOrder($order, $driver);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Order could not be accepted.',
                'errors' => $e->errors(),
            ], 409);
        }

        return response()->json([
            'message' => 'Order accepted.',
            'order' => [
                'id' => $accepted->id,
                'external_order_id' => $accepted->external_order_id,
                'code' => $accepted->code,
                'status' => $accepted->status,
                'customer' => $accepted->customer,
                'address' => $accepted->address,
                'pincode' => $accepted->pincode,
                'lat' => $accepted->lat,
                'lng' => $accepted->lng,
            ],
        ]);
    }
}
