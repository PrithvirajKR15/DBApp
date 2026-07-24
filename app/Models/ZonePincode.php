<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZonePincode extends Model
{
    protected $fillable = [
        'zone_id',
        'pincode',
        'lat',
        'lng',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
