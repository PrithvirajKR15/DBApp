<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StorePincode;
use App\Models\Zone;
use App\Models\ZonePincode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Zones are the human-facing "areas" drivers pick at registration.
 * Pincodes hang off zones so order.pincode can resolve to the same areas
 * without asking drivers to know postal codes.
 */
class ZoneCoverageService
{
    public const DEFAULT_MAP_CENTER = [8.5241, 76.9366];

    /**
     * @param  list<string>  $pincodes
     * @param  array{lat?: float|null, lng?: float|null}|null  $coords
     *         Optional override applied to the zone and copied onto each pin.
     */
    public function syncZonePincodes(Zone $zone, array $pincodes, ?array $coords = null): Zone
    {
        $normalized = $this->normalizePincodes($pincodes);

        return DB::transaction(function () use ($zone, $normalized, $coords) {
            if ($coords !== null) {
                $zone->update([
                    'lat' => $coords['lat'] ?? $zone->lat,
                    'lng' => $coords['lng'] ?? $zone->lng,
                ]);
                $zone->refresh();
            }

            ZonePincode::where('zone_id', $zone->id)
                ->whereNotIn('pincode', $normalized)
                ->delete();

            foreach ($normalized as $pincode) {
                $row = ZonePincode::firstOrNew([
                    'zone_id' => $zone->id,
                    'pincode' => $pincode,
                ]);

                // New pins inherit the zone centroid; existing pins keep their
                // own coords unless the admin just updated the zone lat/lng.
                if (! $row->exists || $coords !== null) {
                    $row->lat = $zone->lat;
                    $row->lng = $zone->lng;
                } elseif ($row->lat === null || $row->lng === null) {
                    $row->lat = $zone->lat;
                    $row->lng = $zone->lng;
                }

                $row->save();
            }

            return $zone->fresh(['pincodes']);
        });
    }

    /**
     * Resolve map coordinates for an order: prefer geocoded point, then
     * pincode pin, then zone/area name, then city default.
     *
     * @return array{0: float, 1: float}
     */
    public function resolveCoordinates(
        ?float $lat,
        ?float $lng,
        ?string $pincode = null,
        ?string $area = null
    ): array {
        if ($lat !== null && $lng !== null && ! ($lat == 0.0 && $lng == 0.0)) {
            return [(float) $lat, (float) $lng];
        }

        if ($pincode) {
            $fromPin = $this->coordinatesForPincode($pincode);
            if ($fromPin) {
                return $fromPin;
            }
        }

        if ($area) {
            $fromArea = $this->coordinatesForArea($area);
            if ($fromArea) {
                return $fromArea;
            }
            // Ingested orders sometimes store the pincode in `area`.
            $fromAreaAsPin = $this->coordinatesForPincode($area);
            if ($fromAreaAsPin) {
                return $fromAreaAsPin;
            }
        }

        return self::DEFAULT_MAP_CENTER;
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    public function coordinatesForPincode(string $pincode): ?array
    {
        $normalized = preg_replace('/\s+/', '', $pincode);

        $pin = ZonePincode::query()
            ->with('zone')
            ->where('pincode', $normalized)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->first();

        if ($pin) {
            return [(float) $pin->lat, (float) $pin->lng];
        }

        $zone = Zone::query()
            ->whereHas('pincodes', fn ($q) => $q->where('pincode', $normalized))
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->first();

        return $zone ? [(float) $zone->lat, (float) $zone->lng] : null;
    }

    /**
     * Match by zone name (e.g. "Pattom") or region label (e.g. "North Zone").
     *
     * @return array{0: float, 1: float}|null
     */
    public function coordinatesForArea(string $area): ?array
    {
        $name = trim($area);
        if ($name === '') {
            return null;
        }

        $zone = Zone::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->first();

        if ($zone) {
            return [(float) $zone->lat, (float) $zone->lng];
        }

        // "North Zone" / "north" → average of zones in that region.
        $region = Str::lower(preg_replace('/\s+zone$/i', '', $name) ?? $name);
        $regionZones = Zone::query()
            ->whereRaw('LOWER(region) = ?', [$region])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        if ($regionZones->isEmpty()) {
            return null;
        }

        return [
            (float) $regionZones->avg('lat'),
            (float) $regionZones->avg('lng'),
        ];
    }

    /**
     * Zones that service a delivery pincode.
     *
     * @return Collection<int, Zone>
     */
    public function zonesForPincode(string $pincode): Collection
    {
        $normalized = preg_replace('/\s+/', '', $pincode);

        return Zone::query()
            ->whereHas('pincodes', fn ($q) => $q->where('pincode', $normalized))
            ->get();
    }

    /**
     * Assign zones to a store, then derive serviceable_pincodes from those
     * zones' zone_pincodes (union). Replaces free-text pin entry for admin UI.
     *
     * @param  list<int|string>  $zoneIds
     */
    public function syncStoreZones(Store $store, array $zoneIds): Store
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $zoneIds))));

        return DB::transaction(function () use ($store, $ids) {
            $store->zones()->sync($ids);

            $pincodes = ZonePincode::query()
                ->whereIn('zone_id', $ids ?: [0])
                ->orderBy('pincode')
                ->pluck('pincode')
                ->unique()
                ->values()
                ->all();

            StorePincode::where('store_id', $store->id)
                ->whereNotIn('pincode', $pincodes)
                ->delete();

            foreach ($pincodes as $pincode) {
                StorePincode::firstOrCreate([
                    'store_id' => $store->id,
                    'pincode' => $pincode,
                ]);
            }

            $store->update(['serviceable_pincodes' => $pincodes]);

            return $store->fresh(['pincodes', 'zones.pincodes']);
        });
    }

    /**
     * Attach a store to every zone that covers any of its serviceable pincodes.
     * Used by partner API sync when only pincodes are provided (no zone list).
     *
     * @param  list<string>  $pincodes
     */
    public function syncStoreZonesFromPincodes(Store $store, array $pincodes): void
    {
        $normalized = $this->normalizePincodes($pincodes);

        if ($normalized === []) {
            return;
        }

        $zoneIds = ZonePincode::query()
            ->whereIn('pincode', $normalized)
            ->pluck('zone_id')
            ->unique()
            ->values()
            ->all();

        if ($zoneIds !== []) {
            $store->zones()->syncWithoutDetaching($zoneIds);
        }
    }

    /**
     * @param  list<mixed>  $pincodes
     * @return list<string>
     */
    public function normalizePincodes(array $pincodes): array
    {
        $clean = [];

        foreach ($pincodes as $pin) {
            $value = preg_replace('/\s+/', '', (string) $pin);
            if ($value !== '') {
                $clean[] = $value;
            }
        }

        return array_values(array_unique($clean));
    }
}
