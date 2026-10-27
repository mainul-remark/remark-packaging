<?php

namespace App\Actions\Packaging;

use App\Enums\CartonStatus;
use App\Models\Carton;
use App\Models\CartonItem;
use App\Models\LabelPrintJob;
use App\Models\User;
use App\Models\WeightVerification;
use Illuminate\Support\Facades\DB;

/**
 * Builds the label for a carton and records each print. The code on the label identifies the carton;
 * the database stays authoritative. The carton number is internal, not an SSCC, and no GS1 value is invented.
 */
class PrintCartonLabel
{
    /** Statuses a label may be printed in: contents verified onwards, never cancelled or reopened. */
    public const PRINTABLE = [
        CartonStatus::CONTENTS_VERIFIED, CartonStatus::AWAITING_WEIGHT, CartonStatus::WEIGHT_FAILED, CartonStatus::WEIGHT_VERIFIED,
        CartonStatus::AWAITING_APPROVAL, CartonStatus::VERIFIED, CartonStatus::SEALED,
    ];

    /** @return array<string, mixed> */
    public function labelData(Carton $carton): array
    {
        $carton->loadMissing(['order', 'packer:id,name', 'verifier:id,name', 'cartonType:id,code,name']);
        $items = CartonItem::query()->where('carton_id', $carton->id)->where('quantity', '>', 0)->get();
        $accepted = WeightVerification::query()->where('carton_id', $carton->id)->where('status', 'ACTIVE')->with('measurement')->first();
        $inputs = $accepted?->measurement?->calculation_inputs ?? [];
        $active = Carton::query()->where('order_id', $carton->order_id)->where('status', '!=', CartonStatus::CANCELLED->value);
        $sequence = (clone $active)->where('id', '<=', $carton->id)->count();

        return [
            'company' => config('app.name'),
            'order_number' => $carton->order->order_number,
            'invoice_number' => $carton->order->invoice_number,
            'carton_number' => $carton->carton_number,
            'box_number' => $carton->box_number,
            'carton_sequence' => $sequence.' of '.$active->count(),
            'carton_type' => $carton->cartonType?->name,
            'sku_count' => $items->pluck('product_id')->unique()->count(),
            'total_quantity' => number_format((float) $items->sum('quantity'), 3, '.', ''),
            'system_weight_g' => $carton->system_weight_g,
            'product_weight_g' => $inputs['products_g'] ?? null,
            'carton_tare_g' => $inputs['tare_g'] ?? null,
            'packaging_weight_g' => $inputs['materials_g'] ?? null,
            'verified_gross_weight_g' => $carton->verified_weight_g,
            'packed_by' => $carton->packer?->name,
            'verified_by' => $carton->verifier?->name,
            'seal_number' => $carton->seal_number,
            'sealed_at' => $carton->sealed_at?->toIso8601String(),
            'qr_payload' => $carton->carton_number,
        ];
    }

    /** The first print is free; every repeat needs a reason and is recorded as a reprint. */
    public function print(int $cartonId, ?string $reason, User $user): LabelPrintJob
    {
        return DB::transaction(function () use ($cartonId, $reason, $user): LabelPrintJob {
            $carton = Carton::query()->whereKey($cartonId)->lockForUpdate()->firstOrFail();
            if (! in_array($carton->status, self::PRINTABLE, true)) {
                throw new PackagingException('INVALID_CARTON_STATE', 'Labels are printed once the carton contents are verified, and not for cancelled or reopened cartons.', ['carton_id' => $carton->id, 'status' => $carton->status->value], 409);
            }

            $existing = LabelPrintJob::query()->where('carton_id', $carton->id)->count();
            $isReprint = $existing > 0;
            if ($isReprint && ($reason === null || trim($reason) === '')) {
                throw new PackagingException('REPRINT_REASON_REQUIRED', 'A reason is required to reprint a label.', []);
            }

            $job = LabelPrintJob::query()->create([
                'carton_id' => $carton->id, 'copy_number' => $existing + 1, 'format' => 'QR',
                'is_reprint' => $isReprint, 'reprint_reason' => $isReprint ? $reason : null, 'label_snapshot' => $this->labelData($carton),
                'printed_by' => $user->id, 'printed_at' => now(),
            ]);
            activity()->performedOn($carton)->causedBy($user)->log('Label '.($isReprint ? 'reprinted' : 'printed').' for '.$carton->carton_number);

            return $job;
        });
    }
}
