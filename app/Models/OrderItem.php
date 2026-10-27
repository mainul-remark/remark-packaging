<?php

namespace App\Models;

use App\Enums\CartonStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'external_line_number',
        'product_id',
        'original_sku',
        'original_description',
        'required_quantity',
        'uom',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'required_quantity' => 'decimal:3',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Units already placed in non-cancelled cartons, keyed by order item id.
     *
     * @param  list<int>  $orderItemIds
     * @return array<int, float>
     */
    public static function packedQuantities(array $orderItemIds): array
    {
        return CartonItem::query()->join('cartons', 'cartons.id', '=', 'carton_items.carton_id')
            ->where('cartons.status', '!=', CartonStatus::CANCELLED->value)->whereIn('carton_items.order_item_id', $orderItemIds)
            ->selectRaw('carton_items.order_item_id as order_item_id, SUM(carton_items.quantity) as packed')->groupBy('carton_items.order_item_id')
            ->pluck('packed', 'order_item_id')->map(fn ($sum): float => (float) $sum)->all();
    }
}
