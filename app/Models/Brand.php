<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'parent_brand_id',
        'code',
        'sap_id',
        'name',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }
}
