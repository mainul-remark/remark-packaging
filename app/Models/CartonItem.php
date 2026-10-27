<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartonItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'order_item_id',
        'product_id',
        'weight_spec_id',
        'quantity',
        'unit_weight_snapshot_g',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_weight_snapshot_g' => 'decimal:3',
        ];
    }

    public function carton(): BelongsTo
    {
        return $this->belongsTo(Carton::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
