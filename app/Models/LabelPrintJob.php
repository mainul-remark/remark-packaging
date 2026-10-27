<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class LabelPrintJob extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'carton_id',
        'copy_number',
        'format',
        'is_reprint',
        'reprint_reason',
        'label_snapshot',
        'printed_by',
        'printed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_reprint' => 'boolean',
            'label_snapshot' => 'array',
            'printed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Label print jobs are immutable.'));
        static::deleting(fn () => throw new LogicException('Label print jobs are immutable.'));
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
