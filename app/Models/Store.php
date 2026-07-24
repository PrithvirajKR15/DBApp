<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'code',
        'external_store_id',
        'name',
        'user_id',
        'area',
        'address',
        'phone',
        'lat',
        'lng',
        'serviceable_pincodes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'serviceable_pincodes' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    /**
     * Drivers assigned to this store via the connecting table.
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'driver_assignments')
            ->withPivot(['type', 'is_active', 'zone'])
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pincodes(): HasMany
    {
        return $this->hasMany(StorePincode::class);
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'store_zones')->withTimestamps();
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class);
    }

    public function servesPincode(string $pincode): bool
    {
        $normalized = preg_replace('/\s+/', '', $pincode);

        if ($this->relationLoaded('pincodes')) {
            return $this->pincodes->contains('pincode', $normalized);
        }

        return $this->pincodes()->where('pincode', $normalized)->exists();
    }
}
