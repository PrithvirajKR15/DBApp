<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePincode extends Model
{
    protected $fillable = [
        'store_id',
        'pincode',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
