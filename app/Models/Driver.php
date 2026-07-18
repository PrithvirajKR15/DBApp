<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Driver profile. Identity (name, email, phone, image, code, status, ...) lives
 * on the related User; live location lives in driver_locations; zones in
 * driver_assignments; earnings in driver_earnings; per-delivery figures on
 * orders. This model only holds durable driver attributes.
 */
class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'joined_at',
        'vehicle_type',
        'vehicle_brand',
        'vehicle_model',
        'plate_number',
        'vehicle_fuel',
        'license_number',
        'shift',
        'partner_type',
        'service_areas',
    ];

    protected function casts(): array
    {
        return [
            'service_areas' => 'array',
            'joined_at' => 'date',
            'rating' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Order the driver is currently delivering (used by the live map).
     */
    public function currentOrder(): HasOne
    {
        return $this->hasOne(Order::class)->whereIn('delivery', ['out', 'transit', 'Transit']);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    /**
     * The driver's currently active store/zone assignment.
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DriverAssignment::class)->where('is_active', true);
    }

    /**
     * Zones this (independent) driver covers.
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'driver_assignments')
            ->withPivot(['type', 'is_active'])
            ->withTimestamps();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }

    /**
     * Most recent location ping (drives the live map).
     */
    public function latestLocation(): HasOne
    {
        return $this->hasOne(DriverLocation::class)->latestOfMany('recorded_at');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(DriverEarning::class);
    }

    /**
     * Drivers that have at least one location ping (shown on the live map).
     */
    public function scopeOnMap($query)
    {
        return $query->whereHas('locations');
    }

    // --- Identity accessors proxied from the linked user account ---

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->mobile;
    }

    public function getImageAttribute(): ?string
    {
        return $this->user?->image;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->user?->code;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->user?->status;
    }

    // --- Derived delivery stats (computed from orders, never stored) ---

    public function getDeliveriesAttribute(): int
    {
        return $this->countOrdersByDelivery(['delivered', 'Delivered']);
    }

    public function getFailedDeliveriesAttribute(): int
    {
        return $this->countOrdersByDelivery(['failed', 'Failed', 'cancelled', 'Cancelled']);
    }

    /**
     * Count the driver's orders in the given delivery states, reusing the
     * loaded relation when available to avoid extra queries.
     *
     * @param  array<int, string>  $statuses
     */
    private function countOrdersByDelivery(array $statuses): int
    {
        if ($this->relationLoaded('orders')) {
            return $this->orders->whereIn('delivery', $statuses)->count();
        }

        return $this->orders()->whereIn('delivery', $statuses)->count();
    }
}
