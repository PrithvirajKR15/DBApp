<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformTransaction extends Model
{
    protected $fillable = [
        'code',
        'occurred_at',
        'type',
        'order_code',
        'driver_name',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'amount' => 'float',
        ];
    }
}
