<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartonMaterial extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'material_id',
        'quantity',
        'unit_weight_snapshot_g',
        'unit_tolerance_snapshot_g',
        'total_weight_g',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_weight_snapshot_g' => 'decimal:3',
            'unit_tolerance_snapshot_g' => 'decimal:3',
            'total_weight_g' => 'decimal:3',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(PackagingMaterial::class, 'material_id');
    }
}
