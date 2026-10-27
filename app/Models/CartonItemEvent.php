<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CartonItemEvent extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'carton_item_id',
        'product_id',
        'order_item_id',
        'scanned_barcode',
        'quantity_delta',
        'event_type',
        'operator_id',
        'idempotency_key',
        'occurred_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:3',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Carton item events are immutable.'));
        static::deleting(fn () => throw new LogicException('Carton item events are immutable.'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
