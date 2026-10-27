<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CartonStatusHistory extends Model
{
    protected $table = 'carton_status_history';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'from_status',
        'to_status',
        'actor_id',
        'reason',
        'occurred_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Carton status history is immutable.'));
        static::deleting(fn () => throw new LogicException('Carton status history is immutable.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
