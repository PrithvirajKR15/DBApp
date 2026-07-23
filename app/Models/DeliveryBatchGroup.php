<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBatchGroup extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'store_id',
        'status',
        'batch_count',
        'order_count',
        'overflow_count',
        'slot_window',
    ];

    protected function casts(): array
    {
        return [
            'batch_count' => 'integer',
            'order_count' => 'integer',
            'overflow_count' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(DeliveryBatch::class, 'batch_group_id')->orderBy('id');
    }

    /**
     * Roll parent status up from child batches after a child status change.
     */
    public function refreshStatusFromChildren(): void
    {
        $statuses = $this->batches()->pluck('status');

        if ($statuses->isEmpty()) {
            $this->update(['status' => self::STATUS_OPEN]);

            return;
        }

        if ($statuses->every(fn ($s) => $s === DeliveryBatch::STATUS_CANCELLED)) {
            $this->update(['status' => self::STATUS_CANCELLED]);

            return;
        }

        if ($statuses->every(fn ($s) => in_array($s, [DeliveryBatch::STATUS_COMPLETED, DeliveryBatch::STATUS_CANCELLED], true))) {
            $this->update(['status' => self::STATUS_COMPLETED]);

            return;
        }

        if ($statuses->contains(DeliveryBatch::STATUS_IN_PROGRESS)) {
            $this->update(['status' => self::STATUS_IN_PROGRESS]);

            return;
        }

        $this->update(['status' => self::STATUS_OPEN]);
    }
}
