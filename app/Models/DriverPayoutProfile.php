<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverPayoutProfile extends Model
{
    protected $fillable = [
        'driver_code',
        'name',
        'phone',
        'type',
        'zone',
        'avatar',
        'lifetime_paid',
        'pending_amount',
        'paid_orders',
        'last_payout_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'lifetime_paid' => 'float',
            'pending_amount' => 'float',
            'last_payout_at' => 'datetime',
        ];
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(DriverPayout::class)->orderByDesc('paid_at');
    }
}
