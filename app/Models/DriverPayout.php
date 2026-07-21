<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverPayout extends Model
{
    protected $fillable = [
        'code',
        'driver_payout_profile_id',
        'paid_at',
        'period',
        'method',
        'reference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DriverPayoutProfile::class, 'driver_payout_profile_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(DriverPayoutOrder::class);
    }
}
