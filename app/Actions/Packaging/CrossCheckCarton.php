<?php

namespace App\Actions\Packaging;

use App\Models\Carton;
use App\Models\CartonCrossCheck;
use App\Models\CartonCrossCheckDecision;
use App\Models\CartonItem;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Security re-weigh and the final clear / flag decision for a carton. Neither changes the carton itself. */
class CrossCheckCarton
{
    /**
     * Compares a checker reading with the weight recorded at packing (verified, else manual, else machine).
     *
     * @return array{recorded: ?string, difference: ?string, tolerance: string, direction: string}
     */
    public function compare(Carton $carton, string $checker): array
    {
        $recorded = $carton->verified_weight_g ?? $carton->manual_weight_g ?? $carton->machine_weight_g;
        $difference = $recorded === null ? null : bcsub($checker, (string) $recorded, 3);
        $tolerance = (string) ($carton->weight_tolerance_upper_g ?? '0.000');
        $direction = $difference === null || bccomp(ltrim($difference, '-'), $tolerance, 3) <= 0 ? 'MATCH' : (bccomp($difference, '0', 3) > 0 ? 'INCREASED' : 'DECREASED');

        return ['recorded' => $recorded === null ? null : (string) $recorded, 'difference' => $difference, 'tolerance' => $tolerance, 'direction' => $direction];
    }

    /** A carton is cross-checked only once its whole order is packed. */
    public function assertFullyPacked(Carton $carton): void
    {
        foreach (OrderItem::query()->where('order_id', $carton->order_id)->get() as $line) {
            $packed = CartonItem::query()->join('cartons', 'cartons.id', '=', 'carton_items.carton_id')
                ->where('cartons.status', '!=', 'CANCELLED')->where('carton_items.order_item_id', $line->id)->sum('carton_items.quantity');
            if (bccomp(number_format((float) $packed, 3, '.', ''), (string) $line->required_quantity, 3) < 0) {
                throw new PackagingException('ORDER_NOT_FULLY_PACKED', 'This carton can be cross-checked once every product on its order is packed.', ['order_id' => $carton->order_id, 'sku' => $line->original_sku], 409);
            }
        }
    }

    /** Records the checker's own re-weigh. */
    public function recordCheck(int $cartonId, string $checkerWeight, ?string $note, User $checker): CartonCrossCheck
    {
        $carton = Carton::query()->findOrFail($cartonId);
        $this->assertFullyPacked($carton);
        $checkerWeight = number_format((float) $checkerWeight, 3, '.', '');
        $weights = $this->compare($carton, $checkerWeight);

        return CartonCrossCheck::query()->create([
            'carton_id' => $carton->id,
            'verified_weight_g' => $carton->verified_weight_g, 'manual_weight_g' => $carton->manual_weight_g, 'machine_weight_g' => $carton->machine_weight_g,
            'recorded_weight_g' => $weights['recorded'], 'system_weight_g' => $carton->system_weight_g ?? $carton->expected_weight_g,
            'checker_weight_g' => $checkerWeight, 'difference_g' => $weights['difference'], 'tolerance_g' => $weights['tolerance'], 'direction' => $weights['direction'],
            'note' => $note, 'checked_by' => $checker->id, 'checked_at' => now(),
        ]);
    }

    /**
     * Records clear / flag once per carton.
     *
     * @param  array{decision: string, label_matched: bool, seal_intact: bool, condition_acceptable: bool, checker_weight_g?: ?string, note?: ?string}  $data
     */
    public function decide(int $cartonId, array $data, User $decider): CartonCrossCheckDecision
    {
        return DB::transaction(function () use ($cartonId, $data, $decider): CartonCrossCheckDecision {
            $carton = Carton::query()->whereKey($cartonId)->lockForUpdate()->firstOrFail();
            $this->assertFullyPacked($carton);
            if (CartonCrossCheckDecision::query()->where('carton_id', $carton->id)->exists()) {
                throw new PackagingException('DECISION_ALREADY_RECORDED', 'A cross-check decision has already been recorded for this carton.', ['carton_id' => $carton->id], 409);
            }

            $recorded = $carton->verified_weight_g ?? $carton->manual_weight_g ?? $carton->machine_weight_g;
            $checker = isset($data['checker_weight_g']) ? number_format((float) $data['checker_weight_g'], 3, '.', '') : ($recorded === null ? null : (string) $recorded);
            $weights = $checker === null ? ['recorded' => null, 'difference' => null, 'tolerance' => (string) ($carton->weight_tolerance_upper_g ?? '0.000'), 'direction' => null] : $this->compare($carton, $checker);
            $note = trim((string) ($data['note'] ?? ''));

            if ($data['decision'] === 'CLEARED') {
                if (! ($data['label_matched'] && $data['seal_intact'] && $data['condition_acceptable'])) {
                    throw new PackagingException('CHECKLIST_INCOMPLETE', 'Every inspection check must pass before a carton can be cleared.');
                }
                if ($checker === null) {
                    throw new PackagingException('WEIGHT_REQUIRED', 'Enter the checker weight; this carton has no recorded weight to compare with.');
                }
                if ($weights['direction'] !== 'MATCH') {
                    throw new PackagingException('WEIGHT_OUT_OF_TOLERANCE', 'The weight is outside tolerance. Flag the carton for review instead.', ['difference_g' => $weights['difference']]);
                }
            } elseif (mb_strlen($note) < 3) {
                throw new PackagingException('NOTE_REQUIRED', 'A note is required when flagging a carton for review.');
            }

            $decision = CartonCrossCheckDecision::query()->create([
                'carton_id' => $carton->id, 'decision' => $data['decision'],
                'label_matched' => $data['label_matched'], 'seal_intact' => $data['seal_intact'], 'condition_acceptable' => $data['condition_acceptable'],
                'note' => $note === '' ? null : $note, 'recorded_weight_g' => $weights['recorded'], 'checker_weight_g' => $checker,
                'difference_g' => $weights['difference'], 'tolerance_g' => $weights['tolerance'], 'direction' => $weights['direction'],
                'decided_by' => $decider->id, 'decided_at' => now(),
            ]);
            activity()->performedOn($carton)->causedBy($decider)->withProperties(['decision' => $data['decision'], 'difference_g' => $weights['difference']])
                ->log('Carton '.$carton->carton_number.' cross-check '.strtolower($data['decision']));

            return $decision;
        });
    }
}
