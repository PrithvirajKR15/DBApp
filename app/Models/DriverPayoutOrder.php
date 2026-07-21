<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverPayoutOrder extends Model
{
    protected $fillable = [
        'driver_payout_id',
        'order_code',
        'delivered_at',
        'delivery_fee',
        'bonus',
        'deduction',
        'net',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'delivery_fee' => 'float',
            'bonus' => 'float',
            'deduction' => 'float',
            'net' => 'float',
        ];
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(DriverPayout::class, 'driver_payout_id');
    }
}
