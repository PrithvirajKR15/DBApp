<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryBatchStop extends Model
{
    protected $fillable = [
        'delivery_batch_id',
        'order_id',
        'stop',
        'order_code',
        'customer',
        'address',
        'locality',
        'lat',
        'lng',
        'value',
        'payment',
        'prep',
        'delivery',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DeliveryBatch::class, 'delivery_batch_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
