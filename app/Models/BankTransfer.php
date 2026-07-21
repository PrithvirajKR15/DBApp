<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    protected $fillable = [
        'code',
        'requested_date',
        'bank',
        'account_ending',
        'amount',
        'status',
        'settled_date',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'settled_date' => 'date',
            'amount' => 'float',
        ];
    }
}
