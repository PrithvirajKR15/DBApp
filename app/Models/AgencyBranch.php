<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyBranch extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'agency_id',
        'name',
        'cost_per_km',
        'minimum_order_charge',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_km' => 'float',
            'minimum_order_charge' => 'float',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Zones covered by this hub / contract.
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'agency_branch_zones')
            ->withTimestamps();
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function executives(): BelongsToMany
    {
        return $this->belongsToMany(AgencyExecutive::class, 'agency_executive_branches')
            ->withTimestamps();
    }

    public function coversZone(int $zoneId): bool
    {
        if ($this->relationLoaded('zones')) {
            return $this->zones->contains('id', $zoneId);
        }

        return $this->zones()->where('zones.id', $zoneId)->exists();
    }
}
