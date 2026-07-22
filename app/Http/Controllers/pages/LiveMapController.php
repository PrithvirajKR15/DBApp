<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;

class LiveMapController extends Controller
{
    /**
     * Live map: renders every driver that currently has a location fix,
     * shaped exactly the way the front-end map script expects.
     */
    public function index()
    {
        $order = ['Transit' => 0, 'Idle' => 1, 'Offline' => 2];

        $drivers = Driver::onMap()
            ->with(['user', 'latestLocation', 'currentOrder', 'activeAssignment.zone'])
            ->get()
            ->map(fn (Driver $d) => $this->shapeDriver($d))
            ->sortBy(fn ($d) => $order[$d['status']] ?? 3)
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
            'orders_today' => Order::count(),
            'avg_eta' => $etas->count() ? (int) round($etas->avg()) : 0,
        ];

        return view('content.pages.live-map', [
            'drivers' => $drivers,
            'stats' => $stats,
            'googleMapsKey' => config('services.google.maps_key'),
        ]);
    }

    /**
     * Convert a Driver model into the array structure used by the map JS.
     */
    private function shapeDriver(Driver $d): array
    {
        $location = $d->latestLocation;
        $zone = $d->activeAssignment?->zone;
        $status = $location?->live_status ?: 'Offline';

        $base = [
            'id' => $d->id,
            'name' => $d->name,
            'status' => $status,
            'statusClass' => strtolower($status),
            'zone' => $zone?->region ?? 'central',
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
