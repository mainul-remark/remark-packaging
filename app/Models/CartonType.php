<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartonType extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'tare_weight_g',
        'tare_tolerance_g',
        'max_gross_weight_g',
        'length_mm',
        'width_mm',
        'height_mm',
        'status',
        'image_path',
        'image_updated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tare_weight_g' => 'decimal:3',
            'tare_tolerance_g' => 'decimal:3',
            'max_gross_weight_g' => 'decimal:3',
            'image_updated_at' => 'datetime',
        ];
    }
}
