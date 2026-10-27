<?php

namespace App\Actions\Packaging;

use App\Enums\CartonStatus;
use App\Models\Carton;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** Supervisor verification, sealing and controlled reopening of a carton. */
class ApproveAndSealCarton
{
    public function __construct(private readonly ChangeCartonStatus $status) {}

    /** The packer hands a weight-accepted carton to a supervisor. */
    public function requestApproval(int $cartonId, User $actor): Carton
    {
        return $this->move($cartonId, CartonStatus::AWAITING_APPROVAL, $actor, null);
    }

    /** Supervisor accepts the contents and weight. The verifier is recorded on the carton. */
    public function verify(int $cartonId, User $supervisor): Carton
    {
        return DB::transaction(function () use ($cartonId, $supervisor): Carton {
            $carton = $this->lock($cartonId);
            $this->status->apply($carton, CartonStatus::VERIFIED, $supervisor, null);
            $carton->update(['verified_by' => $supervisor->id]);

            return $carton;
        });
    }

    /** Supervisor sends the carton back to packing; the earlier weight acceptance is invalidated. */
    public function reject(int $cartonId, string $reason, User $supervisor): Carton
    {
        return $this->move($cartonId, CartonStatus::PACKING, $supervisor, $reason);
    }

    /** Applies a physical seal to a verified carton. From here the carton is operationally immutable. */
    public function seal(int $cartonId, string $sealNumber, User $actor): Carton
    {
        try {
            return DB::transaction(function () use ($cartonId, $sealNumber, $actor): Carton {
                $carton = $this->lock($cartonId);
                $this->status->apply($carton, CartonStatus::SEALED, $actor, null);
                $carton->update(['seal_number' => $sealNumber, 'sealed_at' => now()]);

                return $carton;
            });
        } catch (UniqueConstraintViolationException) {
            throw new PackagingException('SEAL_NUMBER_IN_USE', "Seal {$sealNumber} has already been used.", ['seal_number' => $sealNumber]);
        }
    }

    /**
     * Controlled reopening: removes the seal, invalidates the earlier verification and returns the carton to
     * REOPENED so it must be re-confirmed, re-weighed, re-verified and re-sealed.
     */
    public function reopen(int $cartonId, string $reason, User $actor): Carton
    {
        return DB::transaction(function () use ($cartonId, $reason, $actor): Carton {
            $carton = $this->lock($cartonId);
            $removedSeal = $carton->seal_number;
            $this->status->apply($carton, CartonStatus::REOPENED, $actor, $reason);
            $carton->update(['seal_number' => null, 'sealed_at' => null]);
            activity()->performedOn($carton)->causedBy($actor)->withProperties(['seal_number' => $removedSeal, 'reason' => $reason])->log('Carton '.$carton->carton_number.' reopened');

            return $carton;
        });
    }

    private function move(int $cartonId, CartonStatus $next, User $actor, ?string $reason): Carton
    {
        return DB::transaction(function () use ($cartonId, $next, $actor, $reason): Carton {
            $carton = $this->lock($cartonId);
            $this->status->apply($carton, $next, $actor, $reason);

            return $carton;
        });
    }

    private function lock(int $cartonId): Carton
    {
        return Carton::query()->whereKey($cartonId)->lockForUpdate()->firstOrFail();
    }
}
