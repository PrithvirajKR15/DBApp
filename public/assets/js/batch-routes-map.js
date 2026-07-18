/**
 * Multi-batch delivery route map (Leaflet) — Flipkart / Uber style visualization.
 */
(function (global) {
    'use strict';

    const BATCH_COLORS = [
        { line: '#3b82f6', fill: '#3b82f6', name: 'Batch 1' },
        { line: '#22c55e', fill: '#22c55e', name: 'Batch 2' },
        { line: '#f97316', fill: '#f97316', name: 'Batch 3' },
        { line: '#a855f7', fill: '#a855f7', name: 'Batch 4' },
        { line: '#ec4899', fill: '#ec4899', name: 'Batch 5' },
        { line: '#14b8a6', fill: '#14b8a6', name: 'Batch 6' },
        { line: '#eab308', fill: '#eab308', name: 'Batch 7' },
        { line: '#6366f1', fill: '#6366f1', name: 'Batch 8' },
    ];

    const TILE_URL = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
    const TILE_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>';

    function waitForLeaflet(cb, tries = 0) {
        if (typeof L !== 'undefined') {
            cb();
            return;
        }
        if (tries > 80) return;
        setTimeout(() => waitForLeaflet(cb, tries + 1), 50);
    }

    function hubIcon() {
        return L.divIcon({
            className: 'batch-map-hub-icon',
            html: `<div class="batch-map-hub-marker">
                <div class="batch-map-hub-ring"></div>
                <div class="batch-map-hub-core">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 2v4M12 18v4M2 12h4M18 12h4"></path>
                    </svg>
                </div>
            </div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
        });
    }

    function stopIcon(color, num) {
        return L.divIcon({
            className: 'batch-map-stop-icon',
            html: `<div class="batch-map-stop-marker" style="background:${color};border-color:#fff;">
                <span>${num}</span>
            </div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14],
        });
    }

    function driverIcon(color) {
        return L.divIcon({
            className: 'batch-map-driver-icon',
            html: `<div class="batch-map-driver-marker" style="background:${color};">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="#fff">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
            </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });
    }

    function routePoints(hub, orders) {
        const stops = orders
            .filter((o) => o.lat != null && o.lng != null)
            .sort((a, b) => (a.stop || 0) - (b.stop || 0))
            .map((o) => [o.lat, o.lng]);

        if (!stops.length) return [];
        return [[hub.lat, hub.lng], ...stops, [hub.lat, hub.lng]];
    }

    function batchLabel(batch, idx) {
        if (batch.driver?.name) {
            return `${batch.driver.name} · ${batch.route_label || batch.zone?.replace(/^Route:\s*/, '') || `Route ${idx + 1}`}`;
        }
        const route = batch.route_label || batch.zone?.replace(/^Route:\s*/, '') || `Route ${idx + 1}`;
        return `Batch ${idx + 1} (${route})`;
    }

    function createLegend(container, batches, colors, onSelect, options = {}) {
        const { title = 'Routes', hubLabel = 'Store Hub' } = options;
        const legend = document.createElement('div');
        legend.className = 'batch-map-legend';
        legend.innerHTML = `
            <div class="batch-map-legend-title">${title}</div>
            <div class="batch-map-legend-item batch-map-legend-hub">
                <span class="batch-map-legend-swatch hub"></span>
                <span>${hubLabel}</span>
            </div>`;

        batches.forEach((batch, idx) => {
            const color = colors[idx % colors.length];
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'batch-map-legend-item';
            item.dataset.batchIdx = String(idx);
            const stops = (batch.orders || []).length;
            item.innerHTML = `
                <span class="batch-map-legend-swatch" style="background:${color.line};"></span>
                <span>
                    <strong style="display:block;font-size:0.78rem;">${batch.driver?.name || batchLabel(batch, idx)}</strong>
                    <span style="color:#64748b;font-size:0.72rem;">${stops} stop${stops === 1 ? '' : 's'}${batch.id ? ' · ' + batch.id : ''}</span>
                </span>`;
            item.addEventListener('click', () => onSelect(idx));
            legend.appendChild(item);
        });

        container.appendChild(legend);
        return legend;
    }

    function createBatchRoutesMap(containerId, options = {}) {
        const {
            hub,
            batches = [],
            drivers = [],
            showAllBatches = true,
            highlightIndex = null,
            showDriverOnFirst = true,
            showAssignedDrivers = false,
            height = 420,
            onBatchSelect,
            onOrderClick,
            legendTitle = 'Routes',
            interactiveOrders = false,
        } = options;

        const el = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        if (!el) return null;

        el.innerHTML = '';
        el.classList.add('batch-routes-map-wrap');
        el.style.height = `${height}px`;

        const mapShell = document.createElement('div');
        mapShell.className = 'batch-routes-map-canvas';
        mapShell.id = `${el.id || 'batch-map'}-canvas`;
        el.appendChild(mapShell);

        const state = {
            map: null,
            layers: [],
            legend: null,
            highlightIndex: highlightIndex,
            batches,
        };

        function resolveHub(batch) {
            return batch.hub || hub;
        }

        function render() {
            waitForLeaflet(() => {
                if (state.map) {
                    state.map.remove();
                    state.map = null;
                }
                state.layers = [];
                el.querySelector('.batch-map-legend')?.remove();

                const validBatches = (state.batches || batches).filter((b) =>
                    (b.orders || []).some((o) => o.lat != null && o.lng != null)
                );

                if (!validBatches.length) {
                    mapShell.innerHTML = '<div class="batch-map-empty">No assigned drivers with mapped stops yet.</div>';
                    return;
                }

                const primaryHub = resolveHub(validBatches[0]) || hub;
                if (!primaryHub || primaryHub.lat == null) {
                    mapShell.innerHTML = '<div class="batch-map-empty">Map unavailable — store location missing.</div>';
                    return;
                }

                mapShell.innerHTML = '';
                const mapId = mapShell.id;
                state.map = L.map(mapId, {
                    zoomControl: true,
                    scrollWheelZoom: true,
                });

                L.tileLayer(TILE_URL, {
                    maxZoom: 20,
                    attribution: TILE_ATTR,
                }).addTo(state.map);

                const bounds = L.latLngBounds([]);
                const hubsDrawn = new Set();

                validBatches.forEach((batch) => {
                    const h = resolveHub(batch);
                    if (!h || h.lat == null) return;
                    const key = `${h.lat.toFixed(4)},${h.lng.toFixed(4)}`;
                    if (hubsDrawn.has(key)) return;
                    hubsDrawn.add(key);
                    bounds.extend([h.lat, h.lng]);
                    L.marker([h.lat, h.lng], { icon: hubIcon(), zIndexOffset: 1000 })
                        .addTo(state.map)
                        .bindPopup(`<strong>Store Hub</strong><br>${h.name || 'Fulfillment center'}`);
                });

                const batchesToDraw = showAllBatches
                    ? validBatches
                    : validBatches.filter((_, i) => i === (state.highlightIndex ?? 0));

                batchesToDraw.forEach((batch, drawIdx) => {
                    const batchIdx = showAllBatches ? drawIdx : (state.highlightIndex ?? 0);
                    const color = BATCH_COLORS[batchIdx % BATCH_COLORS.length];
                    const batchHub = resolveHub(batch) || primaryHub;
                    const points = routePoints(batchHub, batch.orders || []);
                    if (points.length < 2) return;

                    points.forEach((p) => bounds.extend(p));

                    const dimmed =
                        state.highlightIndex != null &&
                        showAllBatches &&
                        batchIdx !== state.highlightIndex;
                    const opacity = dimmed ? 0.25 : 0.9;
                    const weight = dimmed ? 3 : batchIdx === state.highlightIndex ? 5 : 4;

                    const line = L.polyline(points, {
                        color: color.line,
                        weight,
                        opacity,
                        dashArray: dimmed ? '6, 10' : '10, 8',
                        lineCap: 'round',
                        lineJoin: 'round',
                    }).addTo(state.map);

                    line.on('click', () => setHighlight(batchIdx));
                    state.layers.push({ type: 'line', layer: line, batchIdx });

                    (batch.orders || [])
                        .filter((o) => o.lat != null && o.lng != null)
                        .forEach((order) => {
                            const marker = L.marker([order.lat, order.lng], {
                                icon: stopIcon(color.fill, order.stop || '?'),
                                opacity: dimmed ? 0.45 : 1,
                                riseOnHover: true,
                            }).addTo(state.map);

                            const driverName = batch.driver?.name || 'Unassigned';
                            const moveHint = interactiveOrders
                                ? `<br><button type="button" class="batch-map-move-btn" data-order-id="${order.id}" data-batch-id="${batch.id}">Move to another driver</button>`
                                : '';
                            marker.bindPopup(
                                `<strong>Stop ${order.stop}</strong> · ${driverName}<br>
                                ${order.customer || ''} · <span style="color:#64748b">${order.id || ''}</span><br>
                                <span style="color:#64748b">${order.locality || order.address || ''}</span>${moveHint}`
                            );

                            marker.on('click', () => {
                                setHighlight(batchIdx);
                                if (typeof onOrderClick === 'function') {
                                    onOrderClick(order, batch, batchIdx);
                                }
                            });

                            marker.on('popupopen', () => {
                                const btn = document.querySelector(`.batch-map-move-btn[data-order-id="${order.id}"]`);
                                if (btn && typeof onOrderClick === 'function') {
                                    btn.addEventListener('click', (e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        onOrderClick(order, batch, batchIdx);
                                        state.map.closePopup();
                                    });
                                }
                            });

                            state.layers.push({ type: 'marker', layer: marker, batchIdx, orderId: order.id });
                        });

                    if (showAssignedDrivers && batch.driver) {
                        const driver = drivers.find((d) => d.id === batch.driver.id);
                        const firstStop = (batch.orders || []).find((o) => o.lat != null);
                        const dLat = driver?.lat ?? (firstStop ? firstStop.lat + 0.0015 : null);
                        const dLng = driver?.lng ?? (firstStop ? firstStop.lng + 0.0015 : null);
                        if (dLat != null && dLng != null) {
                            const dMarker = L.marker([dLat, dLng], {
                                icon: driverIcon(color.line),
                                zIndexOffset: 500,
                            })
                                .addTo(state.map)
                                .bindPopup(
                                    `<strong>${batch.driver.name}</strong><br>${batch.id}<br>${(batch.orders || []).length} stops · Not started`
                                );
                            bounds.extend([dLat, dLng]);
                            state.layers.push({ type: 'driver', layer: dMarker, batchIdx });
                        }
                    } else if (
                        showDriverOnFirst &&
                        batchIdx === 0 &&
                        batch.suggested_driver &&
                        drivers.length
                    ) {
                        const driver = drivers.find((d) => d.id === batch.suggested_driver.id);
                        if (driver && driver.lat != null) {
                            const dMarker = L.marker([driver.lat, driver.lng], {
                                icon: driverIcon(color.line),
                                zIndexOffset: 500,
                            })
                                .addTo(state.map)
                                .bindPopup(
                                    `<strong>${driver.name}</strong><br>Suggested driver<br>${driver.vehicle || ''}`
                                );
                            bounds.extend([driver.lat, driver.lng]);
                            state.layers.push({ type: 'driver', layer: dMarker, batchIdx });
                        }
                    }
                });

                if (showAllBatches && validBatches.length > 1) {
                    state.legend = createLegend(el, validBatches, BATCH_COLORS, setHighlight, {
                        title: legendTitle,
                    });
                }

                if (bounds.isValid()) {
                    state.map.fitBounds(bounds.pad(0.12));
                }
                setTimeout(() => state.map.invalidateSize(), 100);
                setTimeout(() => state.map.invalidateSize(), 400);
            });
        }

        function setHighlight(idx) {
            state.highlightIndex = idx;
            if (showAllBatches) {
                state.layers.forEach(({ layer, type, batchIdx }) => {
                    const dimmed = batchIdx !== idx;
                    if (type === 'line') {
                        layer.setStyle({
                            opacity: dimmed ? 0.2 : 0.95,
                            weight: dimmed ? 3 : 5,
                            dashArray: dimmed ? '6, 10' : '10, 8',
                        });
                    } else if (type === 'marker') {
                        layer.setOpacity(dimmed ? 0.35 : 1);
                    }
                });
                el.querySelectorAll('.batch-map-legend-item[data-batch-idx]').forEach((item) => {
                    item.classList.toggle('active', parseInt(item.dataset.batchIdx, 10) === idx);
                });
            }
            if (typeof onBatchSelect === 'function') onBatchSelect(idx);
        }

        function updateBatches(nextBatches) {
            state.batches = nextBatches;
            render();
        }

        render();

        return {
            refresh: render,
            highlight: setHighlight,
            updateBatches,
            invalidateSize() {
                state.map?.invalidateSize();
            },
            destroy() {
                state.map?.remove();
                state.map = null;
            },
        };
    }

    global.DeliverEaseBatchMap = {
        createBatchRoutesMap,
        BATCH_COLORS,
        routePoints,
    };
})(typeof window !== 'undefined' ? window : global);
