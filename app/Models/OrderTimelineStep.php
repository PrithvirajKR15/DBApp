<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTimelineStep extends Model
{
    public const KEY_PLACED = 'placed';

    public const KEY_PICKING = 'picking';

    public const KEY_PACKING = 'packing';

    public const KEY_READY = 'ready';

    public const KEY_ASSIGNED = 'assigned';

    public const KEY_PICKED_UP = 'picked_up';

    public const KEY_OUT = 'out';

    public const KEY_DELIVERED = 'delivered';

    /**
     * Default lifecycle labels (sort order = array index).
     *
     * @return list<array{key: string, label: string}>
     */
    public static function defaultCatalog(): array
    {
        return [
            ['key' => self::KEY_PLACED, 'label' => 'Order Placed'],
            ['key' => self::KEY_PICKING, 'label' => 'Picking Products'],
            ['key' => self::KEY_PACKING, 'label' => 'Packing'],
            ['key' => self::KEY_READY, 'label' => 'Ready for Pickup'],
            ['key' => self::KEY_ASSIGNED, 'label' => 'Driver Assigned'],
            ['key' => self::KEY_PICKED_UP, 'label' => 'Picked Up'],
            ['key' => self::KEY_OUT, 'label' => 'Out for Delivery'],
            ['key' => self::KEY_DELIVERED, 'label' => 'Delivered'],
        ];
    }

    protected $fillable = [
        'order_id',
        'step_key',
        'label',
        'occurred_at',
        'is_done',
        'is_current',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'is_current' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
