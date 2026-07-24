<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

/**
 * Driver profile. Identity (name, email, phone, image, code, status, ...) lives
 * on the related User; operational availability (Online/Offline/Transit) lives here;
 * live location lives in driver_locations; zones in driver_assignments;
 * earnings in driver_earnings; per-delivery figures on orders. This model only
 * holds durable driver attributes.
 */
class Driver extends Model
{
    /**
     * Store drivers belong to exactly one store and are eligible for
     * batching. Third-party (independent) drivers are the broadcast
     * fallback pool and are never batched — see BroadcastDispatchService.
     *
     * Spec aliases: STORE_ASSIGNED ↔ store, THIRD_PARTY ↔ third_party.
     */
    public const TYPE_STORE = 'store';

    public const TYPE_STORE_ASSIGNED = 'store';

    public const TYPE_THIRD_PARTY = 'third_party';

    public const DISPATCH_IDLE = 'IDLE';

    public const DISPATCH_BUSY = 'BUSY';

    public const DISPATCH_OFFLINE = 'OFFLINE';

    protected $fillable = [
        'user_id',
        'driver_type',
        'store_id',
        'current_batch_id',
        'rating',
        'joined_at',
        'availability',
        'dispatch_status',
        'vehicle_type',
        'vehicle_brand',
        'vehicle_model',
        'plate_number',
        'vehicle_fuel',
        'license_number',
        'shift',
        'working_days',
        'partner_type',
        'agency_branch_id',
        'agency_registration_number',
        'service_areas',
    ];

    protected function casts(): array
    {
        return [
            'service_areas' => 'array',
            'working_days' => 'array',
            'joined_at' => 'date',
            'rating' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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
     * The batch this store driver is currently out delivering, if any.
     * Null means "not busy" for batching purposes.
     */
    public function currentBatch(): BelongsTo
    {
        return $this->belongsTo(DeliveryBatch::class, 'current_batch_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(DeliveryBatch::class);
    }

    public function broadcastOffers(): HasMany
    {
        return $this->hasMany(BroadcastOffer::class);
    }

    /**
     * The driver's currently active store/zone assignment.
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DriverAssignment::class)->where('is_active', true);
    }

    public function agencyBranch(): BelongsTo
    {
        return $this->belongsTo(AgencyBranch::class);
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

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    /**
     * Drivers assigned to a store (active store assignment). Backed by the
     * denormalized driver_type column for a fast, indexed check; the
     * activeAssignment relation still carries the specific store_id.
     */
    public function scopeStoreDrivers(Builder $query): Builder
    {
        return $query->where('driver_type', self::TYPE_STORE);
    }

    /**
     * Independent / third-party drivers (the broadcast fallback pool).
     * Called "zone" drivers in the UI/admin — they're organized by zone for
     * reporting/coverage — but the underlying type is third_party.
     */
    public function scopeZoneDrivers(Builder $query): Builder
    {
        return $query->where('driver_type', self::TYPE_THIRD_PARTY);
    }

    public function scopeThirdPartyDrivers(Builder $query): Builder
    {
        return $query->where('driver_type', self::TYPE_THIRD_PARTY);
    }

    /**
     * Store drivers currently free to receive a new batch: online/idle and not
     * already out on another batch. "Busy" = has any active batch at all.
     */
    public function scopeAvailableForBatch(Builder $query): Builder
    {
        return $query->storeDrivers()
            ->where(function (Builder $q) {
                $q->where('dispatch_status', self::DISPATCH_IDLE)
                    ->orWhere(function (Builder $inner) {
                        $inner->whereNull('dispatch_status')
                            ->where('availability', 'Online');
                    });
            })
            ->whereNull('current_batch_id');
    }

    /**
     * Third-party drivers eligible to receive a broadcast offer: idle and
     * not already mid-delivery on a previously accepted single order.
     */
    public function scopeAvailableForBroadcast(Builder $query): Builder
    {
        return $query->thirdPartyDrivers()
            ->where(function (Builder $q) {
                $q->where('dispatch_status', self::DISPATCH_IDLE)
                    ->orWhere(function (Builder $inner) {
                        $inner->whereNull('dispatch_status')
                            ->where('availability', 'Online');
                    });
            })
            ->whereDoesntHave('orders', function (Builder $q) {
                $q->where('status', 'assigned');
            });
    }

    /**
     * Third-party drivers whose primary zone (or service_areas) covers a
     * zone that owns the given delivery pincode.
     */
    public function scopeServingPincode(Builder $query, string $pincode): Builder
    {
        $normalized = preg_replace('/\s+/', '', $pincode);

        $zones = Zone::query()
            ->whereHas('pincodes', fn (Builder $q) => $q->where('pincode', $normalized))
            ->get(['id', 'code']);

        if ($zones->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        $zoneIds = $zones->pluck('id')->all();
        $zoneCodes = $zones->pluck('code')->all();

        return $query->where(function (Builder $q) use ($zoneIds, $zoneCodes) {
            $q->whereHas('activeAssignment', function (Builder $assignment) use ($zoneIds) {
                $assignment->where('type', 'zone')
                    ->whereIn('zone_id', $zoneIds);
            });

            foreach ($zoneCodes as $code) {
                $q->orWhereJsonContains('service_areas', $code);
            }
        });
    }

    public function isStoreDriver(): bool
    {
        return $this->driver_type === self::TYPE_STORE;
    }

    public function isThirdPartyDriver(): bool
    {
        return $this->driver_type === self::TYPE_THIRD_PARTY;
    }

    public function isIdle(): bool
    {
        return ($this->dispatch_status ?? self::DISPATCH_IDLE) === self::DISPATCH_IDLE
            && $this->availability !== 'Offline';
    }

    public function isBusy(): bool
    {
        if ($this->dispatch_status === self::DISPATCH_BUSY) {
            return true;
        }

        return $this->isStoreDriver()
            ? $this->current_batch_id !== null
            : $this->orders()->where('status', 'assigned')->exists();
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
