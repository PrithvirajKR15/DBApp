<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverDocument extends Model
{
    public const TYPE_AADHAAR_FRONT = 'aadhaar_front';
    public const TYPE_AADHAAR_BACK = 'aadhaar_back';
    public const TYPE_DL_FRONT = 'dl_front';
    public const TYPE_DL_BACK = 'dl_back';
    public const TYPE_PAN_CARD = 'pan_card';
    public const TYPE_VEHICLE_RC = 'vehicle_rc';
    public const TYPE_VEHICLE_INSURANCE = 'vehicle_insurance';

    /** @var list<string> */
    public const REQUIRED_TYPES = [
        self::TYPE_AADHAAR_FRONT,
        self::TYPE_AADHAAR_BACK,
        self::TYPE_DL_FRONT,
        self::TYPE_DL_BACK,
        self::TYPE_PAN_CARD,
        self::TYPE_VEHICLE_RC,
        self::TYPE_VEHICLE_INSURANCE,
    ];

    protected $fillable = [
        'driver_id',
        'doc_type',
        'file_path',
        'original_name',
        'mime_type',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
