<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * Solves the "N pending orders, D available store drivers" mini routing
 * problem: exactly one batch per available driver (never more batches than
 * drivers), each batch an optimally-sequenced route from the store hub.
 *
 * This is a straight port of the nearest-neighbor + insertion-cost heuristic
 * that already existed client-side in public/assets/js/batch-generation.js
 * (kept for the manual "Generate Delivery Batch" admin flow), bounded to a
 * fixed number of batches instead of an open-ended count. A nearest-neighbor
 * + cheapest-insertion heuristic was chosen over a 2-opt/exact solve per
 * product decision — this runs on modest Cloudways hosting and batches are
 * small (single digits of stops), so the heuristic quality/cost tradeoff
 * favors simplicity.
 *
 * Any orders that don't fit in any of the D batches (capacity/distance/time
 * constraints exhausted) are returned as `overflow` — the caller
 * (StoreDriverAssignmentService) routes those to the broadcast fallback.
 */
class BatchRouteOptimizerService
{
    private const ROAD_FACTOR = 1.35;

    private const AVG_SPEED_KMH = 22;

    private const EARTH_RADIUS_KM = 6371;

    /**
     * @param  Collection<int, Order>  $pendingOrders
     * @param  Collection<int, Driver>  $availableDrivers  must have driver_type=store, and ideally latestLocation eager-loaded
     * @param  array{orders_per_batch?: int, max_distance_km?: float, max_route_minutes?: int}  $config
     * @return array{batches: list<array{orders: list<Order>, metrics: array, driver: ?Driver}>, overflow: list<Order>}
     */
    public function optimizeForStore(Store $store, Collection $pendingOrders, Collection $availableDrivers, array $config = []): array
    {
        $ordersPerBatch = max(1, (int) ($config['orders_per_batch'] ?? 5));
        $maxDistanceKm = (float) ($config['max_distance_km'] ?? 10);
        $maxRouteMinutes = (int) ($config['max_route_minutes'] ?? 45);
        $driverCount = $availableDrivers->count();

        $hub = ['lat' => (float) $store->lat, 'lng' => (float) $store->lng];

        [$routable, $overflow] = $pendingOrders->partition(
            fn (Order $order) => $order->lat !== null && $order->lng !== null
        );

        $overflow = $overflow->values()->all();

        // An order whose hub round-trip alone already blows the distance/
        // time cap can never legally seed (or join) any batch — send it
        // straight to overflow instead of letting it silently create an
        // over-cap batch (or loop forever trying to re-seed with it).
        [$unassigned, $unreachable] = $routable->partition(function (Order $order) use ($hub, $maxDistanceKm, $maxRouteMinutes) {
            $soloMetrics = $this->routeMetrics($hub, [$order]);

            return $soloMetrics['distance_km'] <= $maxDistanceKm && $soloMetrics['duration_min'] <= $maxRouteMinutes;
        });

        $overflow = array_merge($overflow, $unreachable->values()->all());
        $unassigned = $unassigned->values()->all();

        if ($driverCount === 0 || $unassigned === []) {
            return ['batches' => [], 'overflow' => array_merge($overflow, $unassigned)];
        }

        /** @var list<array{orders: list<Order>, metrics: array}> $batches */
        $batches = [];

        while ($unassigned !== []) {
            $bestPlacement = $this->findBestInsertion($hub, $batches, $unassigned, $ordersPerBatch, $maxDistanceKm, $maxRouteMinutes);

            if ($bestPlacement !== null) {
                $batches[$bestPlacement['batch_index']]['orders'] = $bestPlacement['route'];
                $batches[$bestPlacement['batch_index']]['metrics'] = $bestPlacement['metrics'];
                $unassigned = $this->removeOrder($unassigned, $bestPlacement['order']);

                continue;
            }

            // Never create more batches than there are available drivers —
            // one batch per driver is a hard cap, not a soft target.
            if (count($batches) >= $driverCount) {
                break;
            }

            [$cluster, $unassigned] = $this->growCluster($hub, $unassigned, $ordersPerBatch, $maxDistanceKm, $maxRouteMinutes);
            $optimized = $this->optimizeRoute($hub, $cluster);

            $batches[] = [
                'orders' => $optimized,
                'metrics' => $this->routeMetrics($hub, $optimized),
            ];
        }

        $shaped = $this->assignDrivers($batches, $availableDrivers);

        return ['batches' => $shaped, 'overflow' => array_merge($overflow, $unassigned)];
    }

    /**
     * @param  list<array{orders: list<Order>, metrics: array}>  $batches
     * @param  list<Order>  $unassigned
     * @return array{batch_index: int, order: Order, extra_km: float, route: list<Order>, metrics: array}|null
     */
    private function findBestInsertion(array $hub, array $batches, array $unassigned, int $ordersPerBatch, float $maxDistanceKm, int $maxRouteMinutes): ?array
    {
        $best = null;

        foreach ($unassigned as $order) {
            foreach ($batches as $i => $batch) {
                if (count($batch['orders']) >= $ordersPerBatch) {
                    continue;
                }

                $insert = $this->insertionCost($hub, $batch['orders'], $order);
                $metrics = $this->routeMetrics($hub, $insert['route']);

                if ($metrics['distance_km'] > $maxDistanceKm || $metrics['duration_min'] > $maxRouteMinutes) {
                    continue;
                }

                if ($best === null || $insert['extra_km'] < $best['extra_km']) {
                    $best = [
                        'batch_index' => $i,
                        'order' => $order,
                        'extra_km' => $insert['extra_km'],
                        'route' => $insert['route'],
                        'metrics' => $metrics,
                    ];
                }
            }
        }

        return $best;
    }

    /**
     * Seed a new cluster with the nearest-to-hub unassigned order, then
     * greedily grow it (nearest-to-last-stop first) while it still respects
     * the distance/time/capacity caps.
     *
     * @param  list<Order>  $unassigned
     * @return array{0: list<Order>, 1: list<Order>} [cluster, remaining unassigned]
     */
    private function growCluster(array $hub, array $unassigned, int $ordersPerBatch, float $maxDistanceKm, int $maxRouteMinutes): array
    {
        usort($unassigned, fn (Order $a, Order $b) => $this->roadKm($hub, $this->point($a)) <=> $this->roadKm($hub, $this->point($b)));

        $seed = array_shift($unassigned);
        $cluster = [$seed];

        while (count($cluster) < $ordersPerBatch && $unassigned !== []) {
            $last = $cluster[count($cluster) - 1];
            usort($unassigned, fn (Order $a, Order $b) => $this->roadKm($this->point($last), $this->point($a)) <=> $this->roadKm($this->point($last), $this->point($b)));

            $added = false;

            foreach ($unassigned as $idx => $candidate) {
                $trialRoute = $this->optimizeRoute($hub, array_merge($cluster, [$candidate]));
                $metrics = $this->routeMetrics($hub, $trialRoute);

                if ($metrics['distance_km'] <= $maxDistanceKm && $metrics['duration_min'] <= $maxRouteMinutes) {
                    $cluster[] = $candidate;
                    array_splice($unassigned, $idx, 1);
                    $added = true;
                    break;
                }
            }

            if (! $added) {
                break;
            }
        }

        return [$cluster, $unassigned];
    }

    /**
     * Nearest-neighbor TSP heuristic from the hub.
     *
     * @param  list<Order>  $orders
     * @return list<Order>
     */
    private function optimizeRoute(array $hub, array $orders): array
    {
        $remaining = $orders;
        $route = [];
        $current = $hub;

        while ($remaining !== []) {
            $bestIdx = 0;
            $bestDist = INF;

            foreach ($remaining as $i => $candidate) {
                $d = $this->roadKm($current, $this->point($candidate));
                if ($d < $bestDist) {
                    $bestDist = $d;
                    $bestIdx = $i;
                }
            }

            $next = $remaining[$bestIdx];
            array_splice($remaining, $bestIdx, 1);
            $route[] = $next;
            $current = $this->point($next);
        }

        return $route;
    }

    /**
     * Cheapest position to insert a new order into an existing route.
     *
     * @param  list<Order>  $route
     * @return array{extra_km: float, position: int, route: list<Order>}
     */
    private function insertionCost(array $hub, array $route, Order $order): array
    {
        if ($route === []) {
            return [
                'extra_km' => $this->roadKm($hub, $this->point($order)) * 2,
                'position' => 0,
                'route' => [$order],
            ];
        }

        $best = ['extra_km' => INF, 'position' => 0, 'route' => $route];
        $baseKm = $this->routeMetrics($hub, $route)['distance_km'];

        for ($pos = 0; $pos <= count($route); $pos++) {
            $trial = array_merge(array_slice($route, 0, $pos), [$order], array_slice($route, $pos));
            $trialKm = $this->routeMetrics($hub, $trial)['distance_km'];
            $extraKm = $trialKm - $baseKm;

            if ($extraKm < $best['extra_km']) {
                $best = ['extra_km' => $extraKm, 'position' => $pos, 'route' => $trial];
            }
        }

        return $best;
    }

    /**
     * @param  list<Order>  $stops
     * @return array{distance_km: float, duration_min: float, hub_to_first_km: float, return_km: float}
     */
    private function routeMetrics(array $hub, array $stops): array
    {
        if ($stops === []) {
            return ['distance_km' => 0.0, 'duration_min' => 0.0, 'hub_to_first_km' => 0.0, 'return_km' => 0.0];
        }

        $hubToFirstKm = $this->roadKm($hub, $this->point($stops[0]));
        $distanceKm = $hubToFirstKm;

        for ($i = 0; $i < count($stops) - 1; $i++) {
            $distanceKm += $this->roadKm($this->point($stops[$i]), $this->point($stops[$i + 1]));
        }

        $returnKm = $this->roadKm($this->point($stops[count($stops) - 1]), $hub);
        $distanceKm += $returnKm;

        return [
            'distance_km' => $distanceKm,
            'duration_min' => ($distanceKm / self::AVG_SPEED_KMH) * 60,
            'hub_to_first_km' => $hubToFirstKm,
            'return_km' => $returnKm,
        ];
    }

    /**
     * Assign the nearest available driver (by current location, falling
     * back to store distance) to each batch, one driver per batch.
     *
     * @param  list<array{orders: list<Order>, metrics: array}>  $batches
     * @param  Collection<int, Driver>  $availableDrivers
     * @return list<array{orders: list<Order>, metrics: array, driver: ?Driver}>
     */
    private function assignDrivers(array $batches, Collection $availableDrivers): array
    {
        $pool = $availableDrivers->values()->all();
        $shaped = [];

        foreach ($batches as $batch) {
            $firstStop = $batch['orders'][0] ?? null;
            $driver = $this->pickNearestDriver($pool, $firstStop);

            if ($driver !== null) {
                $pool = array_values(array_filter($pool, fn (Driver $d) => $d->id !== $driver->id));
            }

            $shaped[] = [
                'orders' => $batch['orders'],
                'metrics' => $batch['metrics'],
                'driver' => $driver,
            ];
        }

        return $shaped;
    }

    /**
     * @param  list<Driver>  $pool
     */
    private function pickNearestDriver(array $pool, ?Order $target): ?Driver
    {
        if ($pool === []) {
            return null;
        }

        if ($target === null || $target->lat === null || $target->lng === null) {
            return $pool[0];
        }

        $targetPoint = $this->point($target);
        $best = null;
        $bestDist = INF;

        foreach ($pool as $driver) {
            $location = $driver->relationLoaded('latestLocation') ? $driver->latestLocation : $driver->latestLocation()->first();

            if (! $location) {
                $dist = INF;
            } else {
                $dist = $this->roadKm($targetPoint, ['lat' => (float) $location->lat, 'lng' => (float) $location->lng]);
            }

            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $driver;
            }
        }

        return $best ?? $pool[0];
    }

    /**
     * @param  list<Order>  $orders
     * @return list<Order>
     */
    private function removeOrder(array $orders, Order $target): array
    {
        return array_values(array_filter($orders, fn (Order $o) => $o->id !== $target->id));
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function point(Order $order): array
    {
        return ['lat' => (float) $order->lat, 'lng' => (float) $order->lng];
    }

    /**
     * @param  array{lat: float, lng: float}  $a
     * @param  array{lat: float, lng: float}  $b
     */
    public function haversineKm(array $a, array $b): float
    {
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);
        $lat1 = deg2rad($a['lat']);
        $lat2 = deg2rad($b['lat']);

        $h = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1, sqrt($h)));
    }

    /**
     * @param  array{lat: float, lng: float}  $a
     * @param  array{lat: float, lng: float}  $b
     */
    public function roadKm(array $a, array $b): float
    {
        return $this->haversineKm($a, $b) * self::ROAD_FACTOR;
    }
}
