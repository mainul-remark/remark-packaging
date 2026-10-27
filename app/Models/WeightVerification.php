<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightVerification extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'weight_measurement_id',
        'method',
        'override_reason',
        'accepted_by',
        'accepted_at',
        'status',
        'invalidated_at',
        'invalidation_reason',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'immutable_datetime',
            'invalidated_at' => 'immutable_datetime',
        ];
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(WeightMeasurement::class, 'weight_measurement_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
