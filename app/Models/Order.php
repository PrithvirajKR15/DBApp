<?php

namespace App\Models;

use App\Jobs\BroadcastOrderJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /**
     * Canonical dispatch lifecycle (new — separate from the legacy free-text
     * `delivery` column, which existing pages still read/write).
     */
    public const STATUS_PENDING = 'pending';

    /** Prep complete — third-party broadcast is triggered from this state. */
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STATUS_BATCHED = 'batched';

    public const STATUS_BROADCASTING = 'broadcasting';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * How the order was assigned. Permanent once set to broadcast — a
     * broadcast order can never later be pulled into a store batch.
     */
    public const ASSIGNMENT_STORE_BATCH = 'store_batch';

    public const ASSIGNMENT_BROADCAST = 'broadcast';

    protected $fillable = [
        'code',
        'external_order_id',
        'store_id',
        'driver_id',
        'delivery_batch_id',
        'status',
        'assignment_type',
        'customer',
        'phone',
        'area',
        'address',
        'pincode',
        'slot',
        'slot_label',
        'placed_at',
        'urgent',
        'value',
        'items',
        'line_items',
        'payment',
        'prep',
        'prep_pct',
        'delivery',
        'eta',
        'distance_km',
        'lat',
        'lng',
        'geocoded_at',
        'geocode_status',
        'views',
        'locality',
        'zone_key',
    ];

    protected function casts(): array
    {
        return [
            'urgent' => 'boolean',
            'value' => 'float',
            'distance_km' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'views' => 'array',
            'line_items' => 'array',
            'geocoded_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DeliveryBatch::class, 'delivery_batch_id');
    }

    public function broadcastOffers(): HasMany
    {
        return $this->hasMany(BroadcastOffer::class);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(OrderDetail::class);
    }

    public function timelineSteps(): HasMany
    {
        return $this->hasMany(OrderTimelineStep::class)->orderBy('sort_order');
    }

    /**
     * A broadcast order is always a single-order delivery and can never be
     * pulled into a multi-order batch, regardless of its current status.
     */
    public function isBroadcastEligible(): bool
    {
        return $this->assignment_type === null || $this->assignment_type === self::ASSIGNMENT_BROADCAST;
    }

    public function canBeBatched(): bool
    {
        return $this->assignment_type === null || $this->assignment_type === self::ASSIGNMENT_STORE_BATCH;
    }

    /**
     * Dual-dispatch entry points:
     * - Store drivers are assigned explicitly by a store manager
     *   (StoreOrderAssignmentService) or via the existing batching tools.
     * - Third-party drivers are notified only when status becomes
     *   READY_FOR_PICKUP (pincode-matched broadcast).
     */
    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            if (
                $order->wasChanged('status')
                && $order->status === self::STATUS_READY_FOR_PICKUP
                && $order->driver_id === null
            ) {
                BroadcastOrderJob::dispatch($order);
            }
        });
    }
}
