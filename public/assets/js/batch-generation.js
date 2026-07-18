/**
 * Distance-based delivery batch generation.
 * Groups orders by road-distance proximity and route efficiency — not fixed zones.
 */
(function (global) {
    'use strict';

    const ROAD_FACTOR = 1.35;
    const AVG_SPEED_KMH = 22;
    const EARTH_RADIUS_KM = 6371;

    function toRad(deg) {
        return (deg * Math.PI) / 180;
    }

    function haversineKm(a, b) {
        const dLat = toRad(b.lat - a.lat);
        const dLng = toRad(b.lng - a.lng);
        const lat1 = toRad(a.lat);
        const lat2 = toRad(b.lat);
        const h =
            Math.sin(dLat / 2) ** 2 +
            Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
        return 2 * EARTH_RADIUS_KM * Math.asin(Math.sqrt(h));
    }

    function roadKm(a, b) {
        return haversineKm(a, b) * ROAD_FACTOR;
    }

    function formatDistance(km) {
        if (km < 1) return `${Math.round(km * 1000)} m`;
        return `${km.toFixed(1)} km`;
    }

    function formatDuration(minutes) {
        const m = Math.round(minutes);
        if (m < 60) return `${m}m`;
        const h = Math.floor(m / 60);
        const rem = m % 60;
        return rem ? `${h}h ${rem}m` : `${h}h`;
    }

    function durationFromDistanceKm(km, speedKmh) {
        return (km / speedKmh) * 60;
    }

    function routeMetrics(hub, stops, speedKmh = AVG_SPEED_KMH) {
        if (!stops.length) {
            return { distanceKm: 0, durationMin: 0, hubToFirstKm: 0, returnKm: 0 };
        }
        let distanceKm = roadKm(hub, stops[0]);
        const hubToFirstKm = distanceKm;
        for (let i = 0; i < stops.length - 1; i++) {
            distanceKm += roadKm(stops[i], stops[i + 1]);
        }
        const returnKm = roadKm(stops[stops.length - 1], hub);
        distanceKm += returnKm;
        return {
            distanceKm,
            durationMin: durationFromDistanceKm(distanceKm, speedKmh),
            hubToFirstKm,
            returnKm,
        };
    }

  /** Nearest-neighbor TSP heuristic from hub. */
    function optimizeRoute(hub, orders) {
        const remaining = [...orders];
        const route = [];
        let current = hub;

        while (remaining.length) {
            let bestIdx = 0;
            let bestDist = Infinity;
            for (let i = 0; i < remaining.length; i++) {
                const d = roadKm(current, remaining[i]);
                if (d < bestDist) {
                    bestDist = d;
                    bestIdx = i;
                }
            }
            const next = remaining.splice(bestIdx, 1)[0];
            route.push(next);
            current = next;
        }
        return route;
    }

    function insertionCost(hub, route, order) {
        if (!route.length) {
            const dist = roadKm(hub, order) * 2;
            return { extraKm: dist, position: 0, route: [order] };
        }

        let best = { extraKm: Infinity, position: 0, route: null };

        for (let pos = 0; pos <= route.length; pos++) {
            const trial = [...route.slice(0, pos), order, ...route.slice(pos)];
            const base = routeMetrics(hub, route).distanceKm;
            const trialMetrics = routeMetrics(hub, trial).distanceKm;
            const extraKm = trialMetrics - base;
            if (extraKm < best.extraKm) {
                best = { extraKm, position: pos, route: trial };
            }
        }

        return best;
    }

    function paymentLabel(payment) {
        const map = {
            online: 'Paid',
            cod: 'COD',
            apple: 'Apple Pay',
        };
        return map[payment] || payment;
    }

    function prepLabel(prep) {
        const map = {
            ready: 'Ready',
            packing: 'Packing',
            not_started: 'Not Started',
        };
        return map[prep] || prep;
    }

    function routeAreaLabel(orders) {
        const areas = [...new Set(orders.map((o) => o.locality || o.area).filter(Boolean))];
        if (areas.length <= 2) return areas.join(' → ');
        return `${areas[0]} → ${areas[1]} +${areas.length - 2}`;
    }

    function reportingZoneKey(orders) {
        const counts = {};
        orders.forEach((o) => {
            const key = o.zone_key || 'central';
            counts[key] = (counts[key] || 0) + 1;
        });
        return Object.entries(counts).sort((a, b) => b[1] - a[1])[0]?.[0] || 'central';
    }

    function assignDriver(batch, drivers, config) {
        const hub = batch.hub;
        const firstStop = batch.orders[0];
        const target = firstStop || hub;

        const available = drivers.filter((d) => d.status === 'available');
        if (!available.length) return null;

        const scored = available
            .map((driver) => {
                const driverPoint = { lat: driver.lat, lng: driver.lng };
                const distToRoute = roadKm(driverPoint, target);
                const typePenalty = config.preferStoreDrivers && driver.type !== 'store' ? 2 : 0;
                const storeBonus =
                    config.preferStoreDrivers &&
                    driver.type === 'store' &&
                    driver.store_id === batch.store_id
                        ? -1.5
                        : 0;
                const loadPenalty = (driver.load || 0) * 0.3;
                const score = distToRoute + typePenalty + storeBonus + loadPenalty;
                return { driver, score, distToRoute };
            })
            .sort((a, b) => a.score - b.score);

        return scored[0]?.driver || null;
    }

    function generateBatches(pendingOrders, hub, storeMeta, drivers, config) {
        const {
            ordersPerBatch = 5,
            maxDistanceKm = 10,
            maxRouteMinutes = 45,
            preferStoreDrivers = true,
            autoFallbackZone = true,
        } = config;

        const speedKmh = AVG_SPEED_KMH;
        let unassigned = pendingOrders.filter((o) => o.lat != null && o.lng != null);
        const batches = [];
        let batchIndex = 1;

        function detectBorderOrders(routeOrders) {
            if (routeOrders.length <= 1) return [];
            const dominant = reportingZoneKey(routeOrders);
            return routeOrders
                .filter((o) => o.zone_key && o.zone_key !== dominant)
                .map((o) => o.id);
        }

        while (unassigned.length > 0) {
            let bestPlacement = null;

            for (const order of unassigned) {
                for (let i = 0; i < batches.length; i++) {
                    const batch = batches[i];
                    if (batch._orders.length >= ordersPerBatch) continue;

                    const insert = insertionCost(hub, batch._orders, order);
                    const metrics = routeMetrics(hub, insert.route);

                    if (
                        metrics.distanceKm <= maxDistanceKm &&
                        metrics.durationMin <= maxRouteMinutes
                    ) {
                        const candidate = {
                            type: 'insert',
                            batchIdx: i,
                            order,
                            extraKm: insert.extraKm,
                            route: insert.route,
                            metrics,
                        };
                        if (!bestPlacement || candidate.extraKm < bestPlacement.extraKm) {
                            bestPlacement = candidate;
                        }
                    }
                }
            }

            if (bestPlacement) {
                const batch = batches[bestPlacement.batchIdx];
                batch._orders = bestPlacement.route;
                batch._metrics = bestPlacement.metrics;
                batch._borderOrders = detectBorderOrders(batch._orders);
                unassigned = unassigned.filter((o) => o.id !== bestPlacement.order.id);
                continue;
            }

            unassigned.sort(
                (a, b) => roadKm(hub, a) - roadKm(hub, b)
            );
            const seed = unassigned.shift();
            const cluster = [seed];

            while (cluster.length < ordersPerBatch && unassigned.length > 0) {
                const last = cluster[cluster.length - 1];
                unassigned.sort((a, b) => roadKm(last, a) - roadKm(last, b));

                let added = false;
                for (let i = 0; i < unassigned.length; i++) {
                    const candidate = unassigned[i];
                    const trialRoute = optimizeRoute(hub, [...cluster, candidate]);
                    const metrics = routeMetrics(hub, trialRoute);
                    if (
                        metrics.distanceKm <= maxDistanceKm &&
                        metrics.durationMin <= maxRouteMinutes
                    ) {
                        cluster.push(candidate);
                        unassigned.splice(i, 1);
                        added = true;
                        break;
                    }
                }
                if (!added) break;
            }

            // Re-optimize after greedy cluster growth
            const optimized = optimizeRoute(hub, cluster);
            const metrics = routeMetrics(hub, optimized);
            const zoneKey = reportingZoneKey(optimized);

            batches.push({
                _orders: optimized,
                _metrics: metrics,
                _borderOrders: detectBorderOrders(optimized),
                _batchIndex: batchIndex++,
                _zoneKey: zoneKey,
            });
        }

        const storeId = storeMeta.id;
        const storeName = storeMeta.name;
        const prefix = storeId.substring(0, 2).toUpperCase();
        const timestamp = Date.now().toString().slice(-4);

        return batches.map((b, idx) => {
            const orders = b._orders.map((o, stopIdx) => ({
                stop: stopIdx + 1,
                id: o.id,
                customer: o.customer,
                address: o.address,
                value: o.value,
                payment: paymentLabel(o.payment),
                prep: prepLabel(o.prep),
                delivery: 'Waiting',
                locality: o.locality,
                lat: o.lat,
                lng: o.lng,
            }));

            const totalValue = orders.reduce((sum, o) => sum + o.value, 0);
            const routeLabel = routeAreaLabel(b._orders);
            const batchId = `BT-${timestamp}${idx}-${prefix}`;
            const suggestedDriver = assignDriver(
                { hub, orders: b._orders, store_id: storeId },
                drivers.filter(
                    (d) =>
                        d.type === 'store'
                            ? d.store_id === storeId
                            : autoFallbackZone
                ),
                { preferStoreDrivers, autoFallbackZone }
            );

            return {
                id: batchId,
                zone: `Route: ${routeLabel}`,
                zone_key: b._zoneKey,
                route_label: routeLabel,
                store: storeName,
                store_id: storeId,
                status: 'pending',
                stops: orders.length,
                distance: formatDistance(b._metrics.distanceKm),
                distance_km: b._metrics.distanceKm,
                est_time: formatDuration(b._metrics.durationMin),
                est_minutes: b._metrics.durationMin,
                value: Math.round(totalValue * 100) / 100,
                driver: null,
                suggested_driver: suggestedDriver
                    ? {
                          id: suggestedDriver.id,
                          name: suggestedDriver.name,
                          avatar: suggestedDriver.avatar,
                          type: suggestedDriver.type,
                          reason:
                              suggestedDriver.type === 'store'
                                  ? 'Nearest store driver'
                                  : 'Lowest extra travel (zone fallback)',
                      }
                    : null,
                orders,
                route: {
                    hub_to_first: formatDistance(b._metrics.hubToFirstKm),
                    return: formatDistance(b._metrics.returnKm),
                },
                hub,
                border_orders: b._borderOrders,
                generated: true,
                generation_method: 'distance_optimized',
            };
        });
    }

    global.DeliverEaseBatchGen = {
        generateBatches,
        haversineKm,
        roadKm,
        formatDistance,
        formatDuration,
        optimizeRoute,
        routeMetrics,
    };
})(typeof window !== 'undefined' ? window : global);
