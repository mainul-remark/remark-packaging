<?php

namespace App\Actions\Packaging;

use App\Enums\CartonStatus;
use App\Models\Carton;
use App\Models\CartonStatusHistory;
use App\Models\User;
use App\Models\WeightVerification;

class ChangeCartonStatus
{
    /** Single place where a carton changes state: validates the transition, bumps the version, writes history and activity. */
    public function apply(Carton $carton, CartonStatus $next, User $actor, ?string $reason): void
    {
        $previous = $carton->status;
        if (! $previous->canTransitionTo($next)) {
            throw new PackagingException('INVALID_CARTON_STATE', "A {$previous->value} carton cannot move to {$next->value}.", [
                'carton_id' => $carton->id, 'status' => $previous->value, 'requested' => $next->value,
            ], 409);
        }

        $carton->update(['status' => $next, 'version' => $carton->version + 1]);
        if (in_array($next, [CartonStatus::PACKING, CartonStatus::REOPENED, CartonStatus::CANCELLED], true)) {
            $this->invalidateAcceptance($carton, $reason ?? 'Carton returned for changes');
        }
        CartonStatusHistory::query()->create([
            'carton_id' => $carton->id, 'from_status' => $previous->value, 'to_status' => $next->value,
            'actor_id' => $actor->id, 'reason' => $reason, 'occurred_at' => now(),
        ]);
        activity()->performedOn($carton)->causedBy($actor)
            ->withProperties(['from' => $previous->value, 'to' => $next->value, 'reason' => $reason])
            ->log('Carton '.$carton->carton_number.' moved to '.$next->value);
    }

    /** Contents may change again, so any earlier weight acceptance and supervisor verification no longer hold. */
    private function invalidateAcceptance(Carton $carton, string $reason): void
    {
        WeightVerification::query()->where('carton_id', $carton->id)->where('status', 'ACTIVE')
            ->update(['status' => 'INVALIDATED', 'invalidated_at' => now(), 'invalidation_reason' => $reason]);
        $carton->update(['verified_weight_g' => null, 'verified_by' => null]);
    }
}
