<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CartonCrossCheck extends Model
{
    public $timestamps = false;

    // Keep microseconds so several rows within one second still sort in order.
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'verified_weight_g',
        'manual_weight_g',
        'machine_weight_g',
        'recorded_weight_g',
        'system_weight_g',
        'checker_weight_g',
        'difference_g',
        'tolerance_g',
        'direction',
        'note',
        'checked_by',
        'checked_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'verified_weight_g' => 'decimal:3',
            'manual_weight_g' => 'decimal:3',
            'machine_weight_g' => 'decimal:3',
            'recorded_weight_g' => 'decimal:3',
            'system_weight_g' => 'decimal:3',
            'checker_weight_g' => 'decimal:3',
            'difference_g' => 'decimal:3',
            'tolerance_g' => 'decimal:3',
            'checked_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Carton cross-checks are immutable.'));
        static::deleting(fn () => throw new LogicException('Carton cross-checks are immutable.'));
    }

    public function carton(): BelongsTo
    {
        return $this->belongsTo(Carton::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
