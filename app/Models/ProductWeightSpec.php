<?php

namespace App\Models;

use App\Enums\WeightMeasurementMethod;
use App\Enums\WeightSpecStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ProductWeightSpec extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'version',
        'reference_weight_g',
        'lower_tolerance_g',
        'upper_tolerance_g',
        'measurement_method',
        'verification_status',
        'approved_by',
        'approved_at',
        'effective_from',
        'effective_to',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reference_weight_g' => 'decimal:3',
            'lower_tolerance_g' => 'decimal:3',
            'upper_tolerance_g' => 'decimal:3',
            'measurement_method' => WeightMeasurementMethod::class,
            'verification_status' => WeightSpecStatus::class,
            'approved_at' => 'immutable_datetime',
            'effective_from' => 'immutable_datetime',
            'effective_to' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ProductWeightSpec $spec): void {
            if ($spec->getRawOriginal('verification_status') === WeightSpecStatus::VERIFIED->value) {
                $changed = array_keys($spec->getDirty());
                $allowed = $changed === ['effective_to']
                    && $spec->getRawOriginal('effective_to') === null
                    && $spec->effective_to !== null
                    && $spec->effective_to->greaterThan($spec->effective_from);

                if (! $allowed) {
                    throw new LogicException('An approved product weight specification is immutable. Create a new version.');
                }
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Product weight specifications are retained as historical records.');
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(ProductWeightSample::class);
    }
}
