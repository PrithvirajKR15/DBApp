<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'code',
        'store_id',
        'driver_id',
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
    ];

    protected function casts(): array
    {
        return [
            'urgent' => 'boolean',
            'value' => 'float',
            'distance_km' => 'float',
            'lat' => 'float',
            'lng' => 'float',
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
}
