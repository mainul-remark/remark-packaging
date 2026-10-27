<?php

namespace App\Models;

use App\Enums\BarcodeType;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBarcode extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'barcode',
        'barcode_type',
        'quantity_multiplier',
        'is_primary',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'barcode_type' => BarcodeType::class,
            'quantity_multiplier' => 'decimal:3',
            'is_primary' => 'boolean',
            'status' => RecordStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
