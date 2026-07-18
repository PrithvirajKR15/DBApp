<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $fillable = [
        'code',
        'name',
        'region',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    /**
     * Independent drivers assigned to this zone (via the connecting table).
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'driver_assignments')
            ->withPivot(['type', 'is_active'])
            ->withTimestamps();
    }
}
