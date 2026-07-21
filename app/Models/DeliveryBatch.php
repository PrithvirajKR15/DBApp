<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBatch extends Model
{
    protected $fillable = [
        'code',
        'store_id',
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

    public function batchStops(): HasMany
    {
        return $this->hasMany(DeliveryBatchStop::class)->orderBy('stop');
    }
}
