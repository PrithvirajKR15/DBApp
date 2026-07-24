<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    protected $fillable = [
        'order_id',
        'customer_code',
        'vip',
        'phone_alt',
        'avatar',
        'landmark',
        'instructions',
        'packages',
        'weight',
        'card_last4',
    ];

    protected function casts(): array
    {
        return [
            'vip' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
