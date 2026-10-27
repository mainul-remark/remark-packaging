<?php

namespace App\Models;

use App\Enums\MeasurementSource;
use App\Enums\WeightResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class WeightMeasurement extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'expected_weight_g',
        'measured_weight_g',
        'lower_limit_g',
        'upper_limit_g',
        'difference_g',
        'result',
        'measurement_source',
        'calculation_inputs',
        'operator_id',
        'idempotency_key',
        'measured_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expected_weight_g' => 'decimal:3',
            'measured_weight_g' => 'decimal:3',
            'lower_limit_g' => 'decimal:3',
            'upper_limit_g' => 'decimal:3',
            'difference_g' => 'decimal:3',
            'result' => WeightResult::class,
            'measurement_source' => MeasurementSource::class,
            'calculation_inputs' => 'array',
            'measured_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Weight measurements are immutable.'));
        static::deleting(fn () => throw new LogicException('Weight measurements are immutable.'));
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
