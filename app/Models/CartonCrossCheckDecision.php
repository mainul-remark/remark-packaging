<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CartonCrossCheckDecision extends Model
{
    public $timestamps = false;

    // Keep microseconds so several rows within one second still sort in order.
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'decision',
        'label_matched',
        'seal_intact',
        'condition_acceptable',
        'note',
        'recorded_weight_g',
        'checker_weight_g',
        'difference_g',
        'tolerance_g',
        'direction',
        'decided_by',
        'decided_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'label_matched' => 'boolean',
            'seal_intact' => 'boolean',
            'condition_acceptable' => 'boolean',
            'recorded_weight_g' => 'decimal:3',
            'checker_weight_g' => 'decimal:3',
            'difference_g' => 'decimal:3',
            'tolerance_g' => 'decimal:3',
            'decided_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Cross-check decisions are immutable.'));
        static::deleting(fn () => throw new LogicException('Cross-check decisions are immutable.'));
    }

    public function carton(): BelongsTo
    {
        return $this->belongsTo(Carton::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
