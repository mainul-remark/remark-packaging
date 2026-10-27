<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingMaterial extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'reference_weight_g',
        'tolerance_g',
        'status',
        'image_path',
        'image_updated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reference_weight_g' => 'decimal:3',
            'tolerance_g' => 'decimal:3',
            'image_updated_at' => 'datetime',
        ];
    }
}
