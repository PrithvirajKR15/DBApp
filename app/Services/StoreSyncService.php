<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StorePincode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create / update stores from Admin panel payloads or partner API syncs.
 * Authoritative pincode coverage lives in `store_pincodes`; the JSON column
 * on `stores` is kept in sync as a convenient cache for API responses.
 * Matching zones (areas) are attached via zone_pincodes so store_zones stays
 * aligned with the same area model drivers register against.
 */
class StoreSyncService
{
    public function __construct(
        protected ZoneCoverageService $zones
    ) {}

    /**
     * @param  array{
     *   external_store_id: string,
     *   name: string,
     *   latitude?: float|null,
     *   longitude?: float|null,
     *   serviceable_pincodes?: list<string>,
     *   address?: string|null,
     *   phone?: string|null,
     *   area?: string|null,
     *   status?: string|null,
     *   code?: string|null
     * }  $payload
     */
    public function sync(array $payload): Store
    {
        $externalId = trim((string) $payload['external_store_id']);
        $pincodes = $this->zones->normalizePincodes($payload['serviceable_pincodes'] ?? []);

        return DB::transaction(function () use ($payload, $externalId, $pincodes) {
            $store = Store::query()
                ->where(function ($q) use ($externalId) {
                    $q->where('external_store_id', $externalId)
                        ->orWhere('code', $externalId);
                })
                ->lockForUpdate()
                ->first();

            $attributes = [
                'external_store_id' => $externalId,
                'name' => $payload['name'],
                'lat' => $payload['latitude'] ?? $payload['lat'] ?? null,
                'lng' => $payload['longitude'] ?? $payload['lng'] ?? null,
                'address' => $payload['address'] ?? ($store?->address),
                'phone' => $payload['phone'] ?? ($store?->phone),
                'area' => $payload['area'] ?? ($store?->area),
                'status' => $payload['status'] ?? ($store?->status ?? 'active'),
                'serviceable_pincodes' => $pincodes,
            ];

            if ($store) {
                $store->update($attributes);
            } else {
                $attributes['code'] = $payload['code'] ?? $externalId;
                $store = Store::create($attributes);
            }

            $this->syncPincodes($store, $pincodes);
            $this->zones->syncStoreZonesFromPincodes($store, $pincodes);

            return $store->fresh(['pincodes', 'zones']);
        });
    }

    /**
     * Admin-panel create/update that also accepts an internal id.
     *
     * @param  array<string, mixed>  $payload
     */
    public function upsertForAdmin(?int $storeId, array $payload): Store
    {
        if (empty($payload['external_store_id']) && empty($payload['code'])) {
            throw ValidationException::withMessages([
                'external_store_id' => 'external_store_id or code is required.',
            ]);
        }

        if ($storeId) {
            $store = Store::findOrFail($storeId);
            $payload['external_store_id'] = $payload['external_store_id']
                ?? $store->external_store_id
                ?? $store->code;
            $payload['name'] = $payload['name'] ?? $store->name;
        }

        return $this->sync($payload);
    }

    /**
     * @param  list<string>  $pincodes
     */
    public function syncPincodes(Store $store, array $pincodes): void
    {
        $normalized = $this->zones->normalizePincodes($pincodes);

        StorePincode::where('store_id', $store->id)
            ->whereNotIn('pincode', $normalized)
            ->delete();

        foreach ($normalized as $pincode) {
            StorePincode::firstOrCreate([
                'store_id' => $store->id,
                'pincode' => $pincode,
            ]);
        }

        $store->update(['serviceable_pincodes' => $normalized]);
    }
}
