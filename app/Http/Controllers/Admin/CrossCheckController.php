<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Packaging\CrossCheckCarton;
use App\Http\Controllers\Controller;
use App\Models\Carton;
use App\Models\CartonCrossCheckDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrossCheckController extends Controller
{
    public function index(): View
    {
        $today = CartonCrossCheckDecision::query()->where('decided_at', '>=', now()->startOfDay());

        return view('admin.package-station.cross-check', ['summary' => [
            'checked' => (clone $today)->count(),
            'cleared' => (clone $today)->where('decision', 'CLEARED')->count(),
            'flagged' => (clone $today)->where('decision', 'FLAGGED')->count(),
            'average' => (clone $today)->whereNotNull('difference_g')->avg('difference_g'),
        ]]);
    }

    /** Finds a carton by its label code (the carton number) with everything the checker needs to see. */
    public function lookup(Request $request): JsonResponse
    {
        $code = trim($request->string('code')->toString());
        $carton = Carton::query()->with(['order.customer', 'order.destination', 'cartonType', 'packer:id,name', 'items.product.barcodes'])
            ->where('carton_number', $code)->first();
        if (! $carton) {
            return response()->json(['message' => "No carton found for {$code}."], 404);
        }
        $decision = CartonCrossCheckDecision::query()->where('carton_id', $carton->id)->with('decider:id,name')->latest('decided_at')->first();
        $recorded = $carton->verified_weight_g ?? $carton->manual_weight_g ?? $carton->machine_weight_g;

        return response()->json(['data' => [
            'id' => $carton->id,
            'carton_number' => $carton->carton_number,
            'status' => str_replace('_', ' ', $carton->status->value),
            'carton_type' => $carton->cartonType?->name,
            'seal_number' => $carton->seal_number,
            'unit_count' => (float) $carton->unit_count,
            'expected_weight_g' => (float) ($carton->system_weight_g ?? $carton->expected_weight_g),
            'recorded_weight_g' => $recorded === null ? null : (float) $recorded,
            'tolerance_g' => (float) ($carton->weight_tolerance_upper_g ?? 0),
            'packed_by' => $carton->packer?->name,
            'order' => [
                'number' => $carton->order->order_number, 'invoice' => $carton->order->invoice_number,
                'customer' => $carton->order->customer->name, 'destination' => $carton->order->destination->name,
            ],
            'items' => $carton->items->map(fn ($item): array => [
                'sku' => $item->product->sku, 'name' => $item->product->name, 'barcode' => $item->product->barcodes->first()?->barcode,
                'quantity' => (float) $item->quantity, 'unit_weight_g' => (float) $item->unit_weight_snapshot_g,
            ])->values(),
            'decision' => $decision ? [
                'decision' => $decision->decision, 'note' => $decision->note, 'decided_by' => $decision->decider?->name, 'decided_at' => $decision->decided_at->toDayDateTimeString(),
            ] : null,
        ]]);
    }

    public function store(Request $request, Carton $carton, CrossCheckCarton $crossCheck): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:CLEARED,FLAGGED'],
            'label_matched' => ['required', 'boolean'],
            'seal_intact' => ['required', 'boolean'],
            'condition_acceptable' => ['required', 'boolean'],
            'checker_weight_g' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        if (isset($data['checker_weight_g'])) {
            $crossCheck->recordCheck($carton->id, (string) $data['checker_weight_g'], $data['note'] ?? null, $request->user());
        }
        $decision = $crossCheck->decide($carton->id, $data, $request->user());

        return response()->json(['data' => [
            'decision' => $decision->decision, 'difference_g' => $decision->difference_g === null ? null : (float) $decision->difference_g, 'direction' => $decision->direction,
        ]], 201);
    }
}
