<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (order, third-party driver notified). See
 * BroadcastDispatchService for the atomic first-accept-wins logic.
 */
class BroadcastOffer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'driver_id',
        'status',
        'notified_at',
        'expires_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
