<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_brand_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_brand_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
