<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Zone;
use App\Services\ZoneCoverageService;
use Carbon\Carbon;

class LiveMapController extends Controller
{
    /**
     * Live map: renders every driver that currently has a location fix,
     * shaped exactly the way the front-end map script expects.
     */
    public function index()
    {
        $drivers = Driver::onMap()
            ->with(['user', 'latestLocation', 'currentOrder', 'activeAssignment.zone'])
            ->orderByRaw("CASE availability WHEN 'Transit' THEN 0 WHEN 'Online' THEN 1 WHEN 'Offline' THEN 2 ELSE 3 END")
            ->get()
            ->map(fn (Driver $d) => $this->shapeDriver($d))
            ->values();

        $transit = $drivers->where('status', 'Transit')->count();
        $idle = $drivers->where('status', 'Idle')->count();
        $offline = $drivers->where('status', 'Offline')->count();

        $etas = $drivers->where('status', 'Transit')
            ->map(fn ($d) => (int) filter_var($d['eta'] ?? '', FILTER_SANITIZE_NUMBER_INT))
            ->filter();

        $stats = [
            'transit' => $transit,
            'idle' => $idle,
            'offline' => $offline,
            'total' => $drivers->count(),
            'active' => $transit + $idle,
            'orders_today' => Order::where('placed_at', Carbon::today())->count(),
            'avg_eta' => $etas->count() ? (int) round($etas->avg()) : 0,
        ];

        $zones = Zone::query()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->orderBy('region')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'region', 'lat', 'lng'])
            ->map(fn (Zone $z) => [
                'id' => $z->id,
                'code' => $z->code,
                'name' => $z->name,
                'region' => $z->region,
                'lat' => (float) $z->lat,
                'lng' => (float) $z->lng,
            ])
            ->values();

        [$centerLat, $centerLng] = ZoneCoverageService::DEFAULT_MAP_CENTER;
        if ($zones->isNotEmpty()) {
            $centerLat = (float) $zones->avg('lat');
            $centerLng = (float) $zones->avg('lng');
        }

        return view('content.pages.live-map', [
            'drivers' => $drivers,
            'stats' => $stats,
            'zones' => $zones,
            'zoneRadiusMeters' => config('services.zone_radius_meters'),
            'mapCenter' => ['lat' => $centerLat, 'lng' => $centerLng],
            'googleMapsKey' => config('services.google.maps_key'),
        ]);
    }

    /**
     * Convert a Driver model into the array structure used by the map JS.
     *
     * Status comes from drivers.availability (Online / Offline / Transit).
     * Online is presented as Idle on the map.
     */
    private function shapeDriver(Driver $d): array
    {
        $location = $d->latestLocation;
        $zone = $d->activeAssignment?->zone;
        $status = match ($d->availability) {
            'Transit' => 'Transit',
            'Online' => 'Idle',
            default => 'Offline',
        };

        $base = [
            'id' => $d->id,
            'name' => $d->name,
            'status' => $status,
            'statusClass' => strtolower($status),
            'zone' => $zone?->code ?? '',
            'zoneName' => $zone?->name ?? '',
            'region' => $zone?->region ?? '',
            'image' => $d->image ?? '1.png',
            'lat' => (float) ($location?->lat ?? 0),
            'lng' => (float) ($location?->lng ?? 0),
            'rating' => $d->rating !== null ? number_format((float) $d->rating, 1) : '—',
        ];

        if ($status === 'Transit') {
            $current = $d->currentOrder;
            $distanceNum = $current && $current->distance_km !== null ? (string) $current->distance_km : '0';
            $speedNum = $location?->speed_kmh !== null ? (string) $location->speed_kmh : '0';

            return array_merge($base, [
                'orderId' => $current?->code ? '#' . $current->code : '#—',
                'distance' => $distanceNum . ' km away',
                'distanceNum' => $distanceNum,
                'eta' => $current?->eta ?? '—',
                'speed' => $speedNum . ' km/h',
                'speedNum' => $speedNum,
                'destination' => $current?->address ?? '—',
            ]);
        }

        if ($status === 'Idle') {
            return array_merge($base, [
                'details' => 'Available &middot; ' . ($zone?->name ?? 'On standby'),
            ]);
        }

        // Offline: derive "last seen" from the latest location timestamp.
        $lastSeen = $location?->recorded_at ? 'Last seen ' . $location->recorded_at->diffForHumans() : 'Offline';

        return array_merge($base, [
            'details' => $lastSeen,
        ]);
    }
}
