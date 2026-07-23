<?php

namespace App\Models;

use App\Jobs\DispatchStoreOrdersJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /**
     * Canonical dispatch lifecycle (new — separate from the legacy free-text
     * `delivery` column, which existing pages still read/write).
     */
    public const STATUS_PENDING = 'pending';

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
        'store_id',
        'driver_id',
        'delivery_batch_id',
        'status',
        'assignment_type',
        'customer',
        'phone',
        'area',
        'address',
        'slot',
        'slot_label',
        'placed_at',
        'urgent',
        'value',
        'items',
        'payment',
        'prep',
        'prep_pct',
        'delivery',
        'eta',
        'distance_km',
        'lat',
        'lng',
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
     * Entry point into the assignment/batching/broadcast flow: every newly
     * created order for a store gets a dispatch pass queued for that store.
     * Safe to fire on seeded/historical orders too — dispatchPendingOrders()
     * only ever acts on orders currently in `pending` status.
     */
    protected static function booted(): void
    {
        static::created(function (Order $order) {
            if ($order->store_id && $order->status === self::STATUS_PENDING) {
                DispatchStoreOrdersJob::dispatch($order->store);
            }
        });
    }
}
