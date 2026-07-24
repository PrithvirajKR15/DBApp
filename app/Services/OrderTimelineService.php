<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTimelineStep;
use Illuminate\Support\Facades\DB;

/**
 * Persists the activity timeline that ops/mobile can advance independently
 * of the legacy prep/delivery chip columns.
 */
class OrderTimelineService
{
    /**
     * Create the full step catalog for a new order (idempotent).
     */
    public function bootstrap(Order $order, ?string $placedAt = null): void
    {
        if ($order->timelineSteps()->exists()) {
            return;
        }

        $rows = [];
        foreach (OrderTimelineStep::defaultCatalog() as $i => $step) {
            $isPlaced = $step['key'] === OrderTimelineStep::KEY_PLACED;
            $rows[] = [
                'order_id' => $order->id,
                'step_key' => $step['key'],
                'label' => $step['label'],
                'occurred_at' => $isPlaced ? ($placedAt ?? $order->placed_at) : null,
                'is_done' => $isPlaced,
                'is_current' => ! $isPlaced && $i === 1,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        OrderTimelineStep::insert($rows);
    }

    /**
     * Recompute done/current flags from the order's prep + delivery state.
     * Used by seeders and when status chips change from web/mobile.
     */
    public function syncFromOrderState(Order $order, array $times = []): void
    {
        $this->bootstrap($order, $times[OrderTimelineStep::KEY_PLACED] ?? $order->placed_at);

        $prep = $order->prep;
        $delivery = $order->delivery;
        $hasDriver = (bool) $order->driver_id;

        $done = [
            OrderTimelineStep::KEY_PLACED => true,
            OrderTimelineStep::KEY_PICKING => true,
            OrderTimelineStep::KEY_PACKING => in_array($prep, ['packing', 'ready'], true),
            OrderTimelineStep::KEY_READY => $prep === 'ready',
            OrderTimelineStep::KEY_ASSIGNED => $hasDriver || in_array($delivery, ['assigned', 'accepted', 'out', 'delivered'], true),
            OrderTimelineStep::KEY_PICKED_UP => in_array($delivery, ['out', 'delivered'], true),
            OrderTimelineStep::KEY_OUT => in_array($delivery, ['out', 'delivered'], true),
            OrderTimelineStep::KEY_DELIVERED => $delivery === 'delivered',
        ];

        $currentKey = null;
        if ($delivery === 'delivered') {
            $currentKey = OrderTimelineStep::KEY_DELIVERED;
        } elseif ($delivery === 'out') {
            $currentKey = OrderTimelineStep::KEY_OUT;
        } elseif ($prep === 'ready' && in_array($delivery, ['new', 'waiting', 'ready', null, ''], true) && ! $hasDriver) {
            $currentKey = OrderTimelineStep::KEY_READY;
        }

        DB::transaction(function () use ($order, $done, $currentKey, $times) {
            $steps = $order->timelineSteps()->orderBy('sort_order')->get();

            if ($currentKey === null) {
                foreach ($steps as $step) {
                    if (! ($done[$step->step_key] ?? false)) {
                        $currentKey = $step->step_key;
                        break;
                    }
                }
            }

            foreach ($steps as $step) {
                $step->update([
                    'is_done' => (bool) ($done[$step->step_key] ?? false),
                    'is_current' => $step->step_key === $currentKey,
                    'occurred_at' => $times[$step->step_key]
                        ?? $step->occurred_at
                        ?? (($done[$step->step_key] ?? false) ? ($step->occurred_at ?? '—') : null),
                ]);
            }
        });
    }

    /**
     * Mark a single step done (and optionally current) — for admin/mobile updates.
     */
    public function advance(Order $order, string $stepKey, ?string $occurredAt = null): void
    {
        $this->bootstrap($order);

        $steps = $order->timelineSteps()->orderBy('sort_order')->get();
        $target = $steps->firstWhere('step_key', $stepKey);
        if (! $target) {
            return;
        }

        foreach ($steps as $step) {
            if ($step->sort_order < $target->sort_order) {
                $step->update([
                    'is_done' => true,
                    'is_current' => false,
                    'occurred_at' => $step->occurred_at ?? '—',
                ]);
            } elseif ($step->id === $target->id) {
                $step->update([
                    'is_done' => true,
                    'is_current' => false,
                    'occurred_at' => $occurredAt ?? $step->occurred_at ?? now()->format('h:i A'),
                ]);
            } elseif ($step->sort_order === $target->sort_order + 1) {
                $step->update([
                    'is_done' => false,
                    'is_current' => true,
                ]);
            } else {
                $step->update(['is_current' => false]);
            }
        }
    }
}
