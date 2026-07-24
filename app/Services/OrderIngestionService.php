<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ingests orders from the external checkout app.
 *
 * Critical: `store_id` in the payload is the *external* store key — we resolve
 * it to an internal Store row and persist that FK. We never re-assign stores
 * by pincode; coverage is informational / for third-party matching only.
 */
class OrderIngestionService
{
    public function __construct(
        protected GeocodingService $geocoder,
        protected OrderTimelineService $timeline
    ) {}

    /**
     * @param  array{
     *   external_order_id: string,
     *   store_id: string,
     *   customer_name: string,
     *   customer_phone: string,
     *   full_address: string,
     *   pincode: string,
     *   value?: float|null,
     *   items?: list<array{
     *     code: string,
     *     name?: string|null,
     *     qty?: float|int|null,
     *     quantity?: float|int|null,
     *     price?: float|null,
     *     unit?: string|null,
     *     category?: string|null,
     *     status?: string|null,
     *     icon?: string|null
     *   }>|null,
     *   payment?: string|null,
     *   urgent?: bool|null,
     *   customer_code?: string|null,
     *   phone_alt?: string|null,
     *   landmark?: string|null,
     *   instructions?: string|null,
     *   packages?: string|null,
     *   weight?: string|null,
     *   card_last4?: string|null,
     *   vip?: bool|null
     * }  $payload
     */
    public function sync(array $payload): Order
    {
        $externalOrderId = trim((string) $payload['external_order_id']);
        $externalStoreId = trim((string) $payload['store_id']);
        $pincode = preg_replace('/\s+/', '', (string) $payload['pincode']);

        $store = Store::query()
            ->where(function ($q) use ($externalStoreId) {
                $q->where('external_store_id', $externalStoreId)
                    ->orWhere('code', $externalStoreId);
            })
            ->first();

        if (! $store) {
            throw ValidationException::withMessages([
                'store_id' => "Unknown store_id [{$externalStoreId}]. Sync the store first.",
            ]);
        }

        $coords = $this->geocoder->geocode(
            (string) $payload['full_address'],
            $pincode
        );

        $lineItems = $this->normalizeLineItems($payload['items'] ?? null);
        $itemCount = $this->countLineItems($lineItems);

        return DB::transaction(function () use ($payload, $externalOrderId, $store, $pincode, $coords, $lineItems, $itemCount) {
            $order = Order::query()
                ->where('external_order_id', $externalOrderId)
                ->lockForUpdate()
                ->first();

            $attributes = [
                'external_order_id' => $externalOrderId,
                'code' => $order?->code ?? $externalOrderId,
                'store_id' => $store->id,
                'customer' => $payload['customer_name'],
                'phone' => $payload['customer_phone'],
                'address' => $payload['full_address'],
                'pincode' => $pincode,
                'area' => $pincode,
                'value' => $payload['value'] ?? $this->sumLineItemValue($lineItems) ?? $order?->value ?? 0,
                'items' => $itemCount > 0 ? $itemCount : ($order?->items ?? 0),
                'line_items' => $lineItems ?? $order?->line_items,
                'payment' => $payload['payment'] ?? $order?->payment,
                'urgent' => (bool) ($payload['urgent'] ?? false),
                'placed_at' => $order?->placed_at ?? now()->format('h:i A'),
                'delivery' => $order?->delivery ?? 'waiting',
                // Stay pending until prep finishes or a store manager assigns.
                // READY_FOR_PICKUP (separate endpoint) triggers third-party broadcast.
                'status' => $order?->status ?? Order::STATUS_PENDING,
            ];

            if ($coords) {
                $attributes['lat'] = $coords['lat'];
                $attributes['lng'] = $coords['lng'];
                $attributes['geocoded_at'] = now();
                $attributes['geocode_status'] = 'success';
            } else {
                $attributes['geocode_status'] = 'failed';
            }

            if ($order) {
                // Never overwrite an already-assigned / delivered order's driver path.
                if (in_array($order->status, [
                    Order::STATUS_ASSIGNED,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_CANCELLED,
                    Order::STATUS_BROADCASTING,
                    Order::STATUS_BATCHED,
                    Order::STATUS_READY_FOR_PICKUP,
                ], true)) {
                    $order->update([
                        'customer' => $attributes['customer'],
                        'phone' => $attributes['phone'],
                        'address' => $attributes['address'],
                        'pincode' => $attributes['pincode'],
                        'lat' => $attributes['lat'] ?? $order->lat,
                        'lng' => $attributes['lng'] ?? $order->lng,
                        'geocoded_at' => $attributes['geocoded_at'] ?? $order->geocoded_at,
                        'geocode_status' => $attributes['geocode_status'],
                        'value' => $attributes['value'],
                        'items' => $attributes['items'],
                        'line_items' => $attributes['line_items'],
                        'payment' => $attributes['payment'],
                    ]);
                } else {
                    $order->update($attributes);
                }

                $this->upsertDetail($order->fresh(), $payload);

                return $order->fresh(['store', 'detail', 'timelineSteps']);
            }

            $created = Order::withoutEvents(fn () => Order::create($attributes));
            $this->upsertDetail($created, $payload);
            $this->timeline->bootstrap($created, $created->placed_at);

            return $created->fresh(['store', 'detail', 'timelineSteps']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function upsertDetail(Order $order, array $payload): void
    {
        $hasDetailFields = array_key_exists('customer_code', $payload)
            || array_key_exists('phone_alt', $payload)
            || array_key_exists('landmark', $payload)
            || array_key_exists('instructions', $payload)
            || array_key_exists('packages', $payload)
            || array_key_exists('weight', $payload)
            || array_key_exists('card_last4', $payload)
            || array_key_exists('vip', $payload);

        $existing = $order->detail;
        if (! $hasDetailFields && $existing) {
            return;
        }

        $order->detail()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_code' => $payload['customer_code'] ?? $existing?->customer_code,
                'vip' => (bool) ($payload['vip'] ?? $existing?->vip ?? false),
                'phone_alt' => $payload['phone_alt'] ?? $existing?->phone_alt,
                'avatar' => $existing?->avatar ?? '1.png',
                'landmark' => $payload['landmark'] ?? $existing?->landmark,
                'instructions' => $payload['instructions'] ?? $existing?->instructions,
                'packages' => $payload['packages'] ?? $existing?->packages,
                'weight' => $payload['weight'] ?? $existing?->weight,
                'card_last4' => $payload['card_last4'] ?? $existing?->card_last4,
            ]
        );
    }

    /**
     * @param  list<array<string, mixed>>|null  $items
     * @return list<array{code: string, name: ?string, qty: float, price: ?float, unit: ?string, category: ?string, status: ?string, icon: ?string}>|null
     */
    protected function normalizeLineItems(?array $items): ?array
    {
        if ($items === null) {
            return null;
        }

        $normalized = [];

        foreach ($items as $item) {
            $qty = $item['qty'] ?? $item['quantity'] ?? 1;

            $normalized[] = [
                'code' => (string) ($item['code'] ?? ''),
                'name' => isset($item['name']) ? (string) $item['name'] : null,
                'qty' => (float) $qty,
                'price' => isset($item['price']) ? (float) $item['price'] : null,
                'unit' => isset($item['unit']) ? (string) $item['unit'] : null,
                'category' => isset($item['category']) ? (string) $item['category'] : null,
                'status' => isset($item['status']) ? (string) $item['status'] : null,
                'icon' => isset($item['icon']) ? (string) $item['icon'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array{qty: float}>|null  $items
     */
    protected function countLineItems(?array $items): int
    {
        if ($items === null || $items === []) {
            return 0;
        }

        return (int) round(array_sum(array_map(
            fn (array $item) => (float) ($item['qty'] ?? 0),
            $items
        )));
    }

    /**
     * @param  list<array{qty: float, price: ?float}>|null  $items
     */
    protected function sumLineItemValue(?array $items): ?float
    {
        if ($items === null || $items === []) {
            return null;
        }

        $total = 0.0;
        $hasPrice = false;

        foreach ($items as $item) {
            if ($item['price'] === null) {
                continue;
            }
            $hasPrice = true;
            $total += ((float) $item['qty']) * ((float) $item['price']);
        }

        return $hasPrice ? round($total, 2) : null;
    }

    /**
     * Mark an order ready and fan out to third-party drivers covering its pincode.
     */
    public function markReadyForPickup(Order $order): Order
    {
        if ($order->driver_id !== null) {
            throw ValidationException::withMessages([
                'order' => 'Order is already assigned to a driver.',
            ]);
        }

        if (in_array($order->status, [
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
            Order::STATUS_ASSIGNED,
            Order::STATUS_BATCHED,
        ], true)) {
            throw ValidationException::withMessages([
                'order' => "Cannot mark order ready from status [{$order->status}].",
            ]);
        }

        $order->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'delivery' => 'ready',
            'prep' => 'ready',
            'prep_pct' => 100,
        ]);

        $this->timeline->syncFromOrderState($order->fresh(), [
            \App\Models\OrderTimelineStep::KEY_READY => now()->format('h:i A'),
        ]);

        // Order::updated observer dispatches BroadcastOrderJob.
        return $order->fresh();
    }
}
