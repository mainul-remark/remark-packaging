<?php

namespace App\Models;

use App\Enums\ProductVerificationStatus;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'category_id',
        'brand_id',
        'variant_id',
        'sku',
        'name',
        'description',
        'base_uom',
        'total_shelf_life',
        'units_per_carton',
        'image_path',
        'image_updated_at',
        'requires_batch',
        'requires_expiry',
        'verification_status',
        'status',
        'verified_by',
        'verified_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'verification_status' => ProductVerificationStatus::class,
            'status' => RecordStatus::class,
            'units_per_carton' => 'integer',
            'requires_batch' => 'boolean',
            'requires_expiry' => 'boolean',
            'verified_at' => 'immutable_datetime',
            'image_updated_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function primaryBarcode(): HasOne
    {
        return $this->hasOne(ProductBarcode::class)
            ->where('is_primary', true)
            ->where('status', RecordStatus::ACTIVE->value);
    }

    public function weightSpecs(): HasMany
    {
        return $this->hasMany(ProductWeightSpec::class)->orderByDesc('version');
    }

    public function verificationEvents(): HasMany
    {
        return $this->hasMany(ProductVerificationEvent::class)->orderBy('occurred_at');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
