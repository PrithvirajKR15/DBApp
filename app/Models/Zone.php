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

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_zones')->withTimestamps();
    }

    public function agencyBranches(): BelongsToMany
    {
        return $this->belongsToMany(AgencyBranch::class, 'agency_branch_zones')
            ->withTimestamps();
    }

    public function pincodes(): HasMany
    {
        return $this->hasMany(ZonePincode::class);
    }

    /**
     * @return list<string>
     */
    public function pincodeList(): array
    {
        if ($this->relationLoaded('pincodes')) {
            return $this->pincodes->pluck('pincode')->values()->all();
        }

        return $this->pincodes()->pluck('pincode')->all();
    }

    public function coversPincode(string $pincode): bool
    {
        $normalized = preg_replace('/\s+/', '', $pincode);

        if ($this->relationLoaded('pincodes')) {
            return $this->pincodes->contains('pincode', $normalized);
        }

        return $this->pincodes()->where('pincode', $normalized)->exists();
    }
}
