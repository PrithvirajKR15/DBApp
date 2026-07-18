<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverEarning extends Model
{
    protected $fillable = [
        'driver_id',
        'amount',
        'period',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'earned_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
