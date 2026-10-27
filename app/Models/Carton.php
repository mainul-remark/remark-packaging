<?php

namespace App\Models;

use App\Enums\CartonStatus;
use App\Enums\PackagingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carton extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'carton_number',
        'sscc',
        'seal_number',
        'box_number',
        'order_id',
        'carton_type_id',
        'packaging_mode',
        'status',
        'expected_weight_g',
        'verified_weight_g',
        'packed_by',
        'verified_by',
        'sealed_at',
        'version',
        'creation_channel',
        'unit_count',
        'system_weight_g',
        'weight_tolerance_lower_g',
        'weight_tolerance_upper_g',
        'weight_lower_limit_g',
        'weight_upper_limit_g',
        'machine_weight_g',
        'manual_weight_g',
        'weight_flag',
        'weight_variance_g',
        'weight_flag_acknowledged_by',
        'weight_flag_acknowledged_at',
        'weight_breakdown',
        'draft_reference',
        'batch_key',
        'batch_sequence',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'packaging_mode' => PackagingMode::class,
            'status' => CartonStatus::class,
            'sealed_at' => 'immutable_datetime',
            'expected_weight_g' => 'decimal:3',
            'verified_weight_g' => 'decimal:3',
            'unit_count' => 'decimal:3',
            'system_weight_g' => 'decimal:3',
            'weight_tolerance_lower_g' => 'decimal:3',
            'weight_tolerance_upper_g' => 'decimal:3',
            'weight_lower_limit_g' => 'decimal:3',
            'weight_upper_limit_g' => 'decimal:3',
            'machine_weight_g' => 'decimal:3',
            'manual_weight_g' => 'decimal:3',
            'weight_variance_g' => 'decimal:3',
            'weight_flag_acknowledged_at' => 'immutable_datetime',
            'weight_breakdown' => 'array',
            'version' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cartonType(): BelongsTo
    {
        return $this->belongsTo(CartonType::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartonItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CartonItemEvent::class)->orderBy('occurred_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CartonStatusHistory::class)->orderBy('occurred_at');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CartonMaterial::class);
    }

    public function weightMeasurements(): HasMany
    {
        return $this->hasMany(WeightMeasurement::class)->orderBy('measured_at');
    }

    public function weightVerifications(): HasMany
    {
        return $this->hasMany(WeightVerification::class)->orderBy('accepted_at');
    }

    public function labelPrints(): HasMany
    {
        return $this->hasMany(LabelPrintJob::class)->orderBy('copy_number');
    }

    public function crossChecks(): HasMany
    {
        return $this->hasMany(CartonCrossCheck::class)->orderBy('checked_at');
    }

    public function crossCheckDecisions(): HasMany
    {
        return $this->hasMany(CartonCrossCheckDecision::class)->orderBy('decided_at');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }
}
