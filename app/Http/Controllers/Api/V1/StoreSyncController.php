<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\StoreSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreSyncController extends Controller
{
    public function __construct(
        protected StoreSyncService $stores
    ) {}

    /**
     * POST /api/v1/stores/sync — partner / ordering-app store upsert.
     */
    public function sync(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'external_store_id' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
            'lat' => ['sometimes', 'nullable', 'numeric'],
            'lng' => ['sometimes', 'nullable', 'numeric'],
            'serviceable_pincodes' => ['sometimes', 'array'],
            'serviceable_pincodes.*' => ['string', 'max:16'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'area' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'nullable', 'string', 'max:40'],
            'code' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $store = $this->stores->sync($payload);

        return response()->json([
            'message' => 'Store synced.',
            'store' => $this->shape($store),
        ], 201);
    }

    /**
     * GET /api/v1/stores
     */
    public function index(): JsonResponse
    {
        $stores = Store::with('pincodes')->orderBy('name')->get()
            ->map(fn (Store $store) => $this->shape($store));

        return response()->json(['stores' => $stores]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function shape(Store $store): array
    {
        return [
            'id' => $store->id,
            'external_store_id' => $store->external_store_id,
            'code' => $store->code,
            'name' => $store->name,
            'latitude' => $store->lat,
            'longitude' => $store->lng,
            'serviceable_pincodes' => $store->serviceable_pincodes
                ?? $store->pincodes->pluck('pincode')->values()->all(),
            'status' => $store->status,
        ];
    }
}
