<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWeightSample extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'product_weight_spec_id',
        'measured_weight_g',
        'scale_id',
        'measurement_source',
        'measured_by',
        'measured_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'measured_weight_g' => 'decimal:3',
            'measured_at' => 'immutable_datetime',
        ];
    }

    public function spec(): BelongsTo
    {
        return $this->belongsTo(ProductWeightSpec::class, 'product_weight_spec_id');
    }

    public function measurer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }
}
