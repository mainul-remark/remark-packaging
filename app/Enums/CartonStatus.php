<?php

namespace App\Enums;

enum CartonStatus: string
{
    case CREATED = 'CREATED';
    case PACKING = 'PACKING';
    case CONTENTS_VERIFIED = 'CONTENTS_VERIFIED';
    case AWAITING_WEIGHT = 'AWAITING_WEIGHT';
    case WEIGHT_FAILED = 'WEIGHT_FAILED';
    case WEIGHT_VERIFIED = 'WEIGHT_VERIFIED';
    case AWAITING_APPROVAL = 'AWAITING_APPROVAL';
    case VERIFIED = 'VERIFIED';
    case SEALED = 'SEALED';
    case REOPENED = 'REOPENED';
    case CANCELLED = 'CANCELLED';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::CREATED => [self::PACKING, self::CONTENTS_VERIFIED, self::CANCELLED],
            self::PACKING, self::REOPENED => [self::CONTENTS_VERIFIED, self::CANCELLED],
            self::CONTENTS_VERIFIED => [self::PACKING, self::AWAITING_WEIGHT, self::CANCELLED],
            self::AWAITING_WEIGHT => [self::WEIGHT_VERIFIED, self::WEIGHT_FAILED, self::PACKING, self::CANCELLED],
            // A failed attempt may be re-measured directly, or contents reviewed by returning to packing.
            self::WEIGHT_FAILED => [self::WEIGHT_VERIFIED, self::PACKING, self::CANCELLED],
            self::WEIGHT_VERIFIED => [self::AWAITING_APPROVAL, self::PACKING, self::CANCELLED],
            self::AWAITING_APPROVAL => [self::VERIFIED, self::PACKING, self::CANCELLED],
            self::VERIFIED => [self::SEALED, self::PACKING, self::CANCELLED],
            // Reopening a seal is a controlled action; it invalidates the earlier verification.
            self::SEALED => [self::REOPENED],
            default => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** Packaging materials may be set until the carton is accepted on weight. */
    public function acceptsMaterials(): bool
    {
        return in_array($this, [self::CREATED, self::PACKING, self::CONTENTS_VERIFIED, self::AWAITING_WEIGHT, self::WEIGHT_FAILED], true);
    }

    /** Contents may only change while the carton is still being packed. */
    public function acceptsScans(): bool
    {
        return in_array($this, [self::CREATED, self::PACKING, self::REOPENED], true);
    }
}
