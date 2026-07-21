<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchHub extends Model
{
    protected $fillable = [
        'code',
        'name',
        'zone',
        'branch',
        'pending',
        'drivers_count',
        'est_batches',
        'status',
        'slot',
        'color',
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
}
