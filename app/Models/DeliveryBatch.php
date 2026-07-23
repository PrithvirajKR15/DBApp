<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBatch extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Statuses that count as the driver being "busy" (Driver::current_batch_id
     * stays set while a batch is in one of these states).
     */
    public const ACTIVE_STATUSES = [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS];

    protected $fillable = [
        'code',
        'store_id',
        'batch_group_id',
        'driver_id',
        'zone',
        'zone_key',
        'route_label',
        'status',
        'stops',
        'distance',
        'est_time',
        'value',
        'driver_code',
        'driver_name',
        'driver_avatar',
        'hub_lat',
        'hub_lng',
        'hub_name',
        'route_hub_to_first',
        'route_return',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'hub_lat' => 'float',
            'hub_lng' => 'float',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DeliveryBatchGroup::class, 'batch_group_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Child batches can be (re)assigned until delivery has started.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ASSIGNED], true);
    }

    public function batchStops(): HasMany
    {
        return $this->hasMany(DeliveryBatchStop::class)->orderBy('stop');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }
}
