<?php

namespace App\Actions\Packaging;

use App\Enums\CartonStatus;
use App\Enums\MeasurementSource;
use App\Enums\WeightResult;
use App\Models\Carton;
use App\Models\User;
use App\Models\WeightMeasurement;
use App\Models\WeightVerification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class WeighCarton
{
    public function __construct(private readonly ChangeCartonStatus $status, private readonly WeightExpectation $expectation) {}

    /** Moves a contents-verified carton to the scale. Fails early if its expected weight cannot be computed. */
    public function requestWeighing(int $cartonId, User $actor): Carton
    {
        return DB::transaction(function () use ($cartonId, $actor): Carton {
            $carton = $this->lock($cartonId);
            $expected = $this->expectation->forCarton($carton->refresh());
            $carton->update(['expected_weight_g' => $expected['expected_g']]);
            $this->status->apply($carton, CartonStatus::AWAITING_WEIGHT, $actor, null);

            return $carton;
        });
    }

    /** Sends a carton back to packing so its contents can be corrected after a weight problem. */
    public function returnToPacking(int $cartonId, string $reason, User $actor): Carton
    {
        return DB::transaction(function () use ($cartonId, $reason, $actor): Carton {
            $carton = $this->lock($cartonId);
            $this->status->apply($carton, CartonStatus::PACKING, $actor, $reason);

            return $carton;
        });
    }

    /**
     * Records one weighing attempt with its frozen expectation and limits. A pass accepts the carton on weight;
     * a fail leaves it in WEIGHT_FAILED. Every attempt is kept.
     *
     * @return array{carton: Carton, measurement: WeightMeasurement, replayed: bool}
     */
    public function measure(int $cartonId, string $measuredWeight, string $idempotencyKey, User $operator): array
    {
        try {
            return DB::transaction(fn () => $this->record($cartonId, $measuredWeight, $idempotencyKey, $operator));
        } catch (UniqueConstraintViolationException) {
            return $this->replay($cartonId, $idempotencyKey)
                ?? throw new PackagingException('IDEMPOTENCY_KEY_REUSED', 'This idempotency key belongs to a different measurement.', [], 409);
        }
    }

    /** Supervisor accepts a carton whose latest attempt failed, with a recorded reason. The failure stays visible. */
    public function override(int $cartonId, string $reason, User $supervisor): Carton
    {
        return DB::transaction(function () use ($cartonId, $reason, $supervisor): Carton {
            $carton = $this->lock($cartonId);
            $failed = WeightMeasurement::query()->where('carton_id', $carton->id)->where('result', WeightResult::FAIL->value)->orderByDesc('measured_at')->orderByDesc('id')->first();
            if ($carton->status !== CartonStatus::WEIGHT_FAILED || ! $failed) {
                throw new PackagingException('INVALID_CARTON_STATE', 'Only a carton with a failed weight check can be overridden.', ['carton_id' => $carton->id, 'status' => $carton->status->value], 409);
            }

            WeightVerification::query()->create([
                'carton_id' => $carton->id, 'weight_measurement_id' => $failed->id, 'method' => 'SUPERVISOR_OVERRIDE',
                'override_reason' => $reason, 'accepted_by' => $supervisor->id, 'accepted_at' => now(), 'status' => 'ACTIVE',
            ]);
            $carton->update(['verified_weight_g' => $failed->measured_weight_g]);
            $this->status->apply($carton, CartonStatus::WEIGHT_VERIFIED, $supervisor, $reason);

            return $carton;
        });
    }

    /** @return array{carton: Carton, measurement: WeightMeasurement, replayed: bool} */
    private function record(int $cartonId, string $measured, string $key, User $operator): array
    {
        if ($replay = $this->replay($cartonId, $key)) {
            return $replay;
        }
        $carton = $this->lock($cartonId);
        if (! in_array($carton->status, [CartonStatus::AWAITING_WEIGHT, CartonStatus::WEIGHT_FAILED], true)) {
            throw new PackagingException('INVALID_CARTON_STATE', "A {$carton->status->value} carton cannot be weighed.", ['carton_id' => $carton->id, 'status' => $carton->status->value], 409);
        }

        $measured = number_format((float) $measured, 3, '.', '');
        $expected = $this->expectation->forCarton($carton);
        $pass = bccomp($measured, $expected['lower_g'], 3) >= 0 && bccomp($measured, $expected['upper_g'], 3) <= 0;
        $measurement = WeightMeasurement::query()->create([
            'carton_id' => $carton->id, 'expected_weight_g' => $expected['expected_g'], 'measured_weight_g' => $measured,
            'lower_limit_g' => $expected['lower_g'], 'upper_limit_g' => $expected['upper_g'],
            'difference_g' => bcsub($measured, $expected['expected_g'], 3), 'result' => $pass ? WeightResult::PASS : WeightResult::FAIL,
            'measurement_source' => MeasurementSource::MANUAL, 'calculation_inputs' => $expected,
            'operator_id' => $operator->id, 'idempotency_key' => $key, 'measured_at' => now(),
        ]);
        $carton->update(['expected_weight_g' => $expected['expected_g']]);

        if ($pass) {
            WeightVerification::query()->create([
                'carton_id' => $carton->id, 'weight_measurement_id' => $measurement->id, 'method' => 'MEASURED_PASS',
                'accepted_by' => $operator->id, 'accepted_at' => now(), 'status' => 'ACTIVE',
            ]);
            $carton->update(['verified_weight_g' => $measured]);
            $this->status->apply($carton, CartonStatus::WEIGHT_VERIFIED, $operator, null);
        } elseif ($carton->status === CartonStatus::AWAITING_WEIGHT) {
            $this->status->apply($carton, CartonStatus::WEIGHT_FAILED, $operator, null);
        } else {
            $carton->update(['version' => $carton->version + 1]);
        }

        return ['carton' => $carton->refresh(), 'measurement' => $measurement, 'replayed' => false];
    }

    /** @return array{carton: Carton, measurement: WeightMeasurement, replayed: bool}|null */
    private function replay(int $cartonId, string $key): ?array
    {
        $measurement = WeightMeasurement::query()->where('idempotency_key', $key)->first();
        if (! $measurement) {
            return null;
        }
        if ($measurement->carton_id !== $cartonId) {
            throw new PackagingException('IDEMPOTENCY_KEY_REUSED', 'This idempotency key belongs to a different measurement.', [], 409);
        }

        return ['carton' => Carton::query()->findOrFail($cartonId), 'measurement' => $measurement, 'replayed' => true];
    }

    private function lock(int $cartonId): Carton
    {
        return Carton::query()->whereKey($cartonId)->lockForUpdate()->firstOrFail();
    }
}
