<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'gstin',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'status',
        'created_by',
        'store_id',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(AgencyBranch::class);
    }

    public function executives(): HasMany
    {
        return $this->hasMany(AgencyExecutive::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
