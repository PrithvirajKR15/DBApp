<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformOrderEarning extends Model
{
    protected $fillable = [
        'order_code',
        'earned_at',
        'store_name',
        'customer',
        'driver_name',
        'order_amount',
        'delivery_fee',
        'refund',
        'net_earning',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
            'order_amount' => 'float',
            'delivery_fee' => 'float',
            'refund' => 'float',
            'net_earning' => 'float',
        ];
    }
}
