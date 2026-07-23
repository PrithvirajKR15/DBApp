<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchSetting extends Model
{
    protected $fillable = [
        'orders_per_batch',
        'accept_minutes',
        'max_distance_km',
        'max_route_minutes',
        'prefer_store_drivers',
        'auto_fallback_zone',
        'slot_window',
        'broadcast_radius_km',
        'broadcast_offer_seconds',
    ];

    protected function casts(): array
    {
        return [
            'prefer_store_drivers' => 'boolean',
            'auto_fallback_zone' => 'boolean',
            'max_distance_km' => 'float',
            'broadcast_radius_km' => 'float',
        ];
    }
}
