<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Zone;
use App\Services\ZoneCoverageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected ZoneCoverageService $zones
    ) {}

    public function index(): View
    {
        $zones = Zone::query()
            ->with('pincodes')
            ->orderBy('region')
            ->orderBy('name')
            ->get();

        $stores = Store::query()
            ->with(['pincodes', 'zones.pincodes'])
            ->orderBy('name')
            ->get();

        return view('content.pages.settings', [
            'zones' => $zones,
            'stores' => $stores,
            'activeSection' => request('section', 'general-settings'),
        ]);
    }

    public function saveZonePincodes(Request $request, Zone $zone): JsonResponse
    {
        $data = $request->validate([
            'pincodes' => ['required', 'string'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ]);

        $pins = preg_split('/[\s,;]+/', $data['pincodes'], -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $coords = null;
        if (array_key_exists('latitude', $data) || array_key_exists('longitude', $data)) {
            $coords = [
                'lat' => $data['latitude'] ?? $zone->lat,
                'lng' => $data['longitude'] ?? $zone->lng,
            ];
        }

        $updated = $this->zones->syncZonePincodes($zone, $pins, $coords);

        return response()->json([
            'message' => 'Zone pincodes saved.',
            'zone' => [
                'id' => $updated->id,
                'code' => $updated->code,
                'name' => $updated->name,
                'lat' => $updated->lat,
                'lng' => $updated->lng,
                'pincodes' => $updated->pincodeList(),
            ],
        ]);
    }

    public function saveStore(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'external_store_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
            'zone_ids' => ['required', 'array', 'min:1'],
            'zone_ids.*' => ['integer', 'exists:zones,id'],
            'status' => ['sometimes', 'nullable', 'string', 'max:40'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);

        $store->update([
            'name' => $data['name'] ?? $store->name,
            'external_store_id' => $data['external_store_id'] ?? $store->external_store_id ?? $store->code,
            'lat' => $data['latitude'] ?? $store->lat,
            'lng' => $data['longitude'] ?? $store->lng,
            'status' => $data['status'] ?? $store->status,
            'address' => $data['address'] ?? $store->address,
            'phone' => $data['phone'] ?? $store->phone,
        ]);

        // Zones first — serviceable pincodes are derived from zone_pincodes.
        $updated = $this->zones->syncStoreZones($store, $data['zone_ids']);

        return response()->json([
            'message' => 'Store settings saved.',
            'store' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'external_store_id' => $updated->external_store_id,
                'zone_ids' => $updated->zones->pluck('id')->values(),
                'zones' => $updated->zones->pluck('name')->values(),
                'serviceable_pincodes' => $updated->serviceable_pincodes ?? [],
            ],
        ]);
    }

    public function destroyZone(Zone $zone): JsonResponse
    {
        $name = $zone->name;
        $zone->delete();

        return response()->json([
            'message' => "Zone \"{$name}\" deleted.",
        ]);
    }

    public function destroyStore(Store $store): JsonResponse
    {
        $name = $store->name;
        $store->delete();

        return response()->json([
            'message' => "Store \"{$name}\" deleted.",
        ]);
    }
}
