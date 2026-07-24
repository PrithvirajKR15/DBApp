<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Store;
use App\Models\Zone;
use App\Services\StoreOrderAssignmentService;
use App\Services\StoreSyncService;
use App\Services\ZoneCoverageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Admin / store-manager endpoints (Sanctum or session via stateful API).
 */
class AdminDispatchController extends Controller
{
    public function __construct(
        protected StoreOrderAssignmentService $assignments,
        protected StoreSyncService $stores,
        protected ZoneCoverageService $zones
    ) {}

    /**
     * POST /api/v1/admin/stores — manual store create/update from admin panel.
     */
    public function upsertStore(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['sometimes', 'nullable', 'integer', 'exists:stores,id'],
            'external_store_id' => ['required_without:id', 'nullable', 'string', 'max:100'],
            'code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'name' => ['required_without:id', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
            'serviceable_pincodes' => ['sometimes', 'array'],
            'serviceable_pincodes.*' => ['string', 'max:16'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'status' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);

        try {
            $store = $this->stores->upsertForAdmin(
                isset($payload['id']) ? (int) $payload['id'] : null,
                $payload
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Store save failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Store saved.',
            'store' => [
                'id' => $store->id,
                'external_store_id' => $store->external_store_id,
                'name' => $store->name,
                'serviceable_pincodes' => $store->serviceable_pincodes,
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/orders/{order}/assign-driver
     * Store manager directly assigns a STORE_ASSIGNED driver of that store.
     */
    public function assignDriver(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        $user = $request->user();

        // Store admins may only assign within their own store.
        if ($user && method_exists($user, 'isStoreAdmin') && $user->isStoreAdmin()) {
            $managedStoreId = $user->store_id ?? Store::where('user_id', $user->id)->value('id');
            if ($managedStoreId && (int) $managedStoreId !== (int) $order->store_id) {
                return response()->json(['message' => 'Order does not belong to your store.'], 403);
            }
        }

        $driver = Driver::findOrFail($data['driver_id']);

        try {
            $assigned = $this->assignments->assign($order, $driver);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Assignment failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Driver assigned.',
            'order' => [
                'id' => $assigned->id,
                'status' => $assigned->status,
                'driver_id' => $assigned->driver_id,
                'assignment_type' => $assigned->assignment_type,
            ],
        ]);
    }

    /**
     * PUT /api/v1/admin/zones/{zone}/pincodes — map postal codes onto an area.
     * Drivers keep selecting zones; this is admin/ops configuration only.
     */
    public function syncZonePincodes(Request $request, Zone $zone): JsonResponse
    {
        $data = $request->validate([
            'pincodes' => ['required', 'array', 'min:1'],
            'pincodes.*' => ['string', 'max:16'],
        ]);

        $updated = $this->zones->syncZonePincodes($zone, $data['pincodes']);

        return response()->json([
            'message' => 'Zone pincodes updated.',
            'zone' => [
                'id' => $updated->id,
                'code' => $updated->code,
                'name' => $updated->name,
                'pincodes' => $updated->pincodeList(),
            ],
        ]);
    }
}
