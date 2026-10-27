<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Packaging\ApproveAndSealCarton;
use App\Actions\Packaging\PrintCartonLabel;
use App\Actions\Packaging\WeighCarton;
use App\Enums\CartonStatus;
use App\Http\Controllers\Controller;
use App\Models\Carton;
use App\Models\LabelPrintJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CartonController extends Controller
{
    public function index(Request $request): View
    {
        $counts = Carton::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $cartons = Carton::query()->with(['order', 'cartonType'])->withCount('labelPrints')
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('q')->trim()->toString(), fn ($query, string $search) => $query->where(function ($inner) use ($search): void {
                $inner->where('carton_number', 'like', "%{$search}%")->orWhere('seal_number', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.package-station.cartons', [
            'cartons' => $cartons, 'counts' => $counts, 'activeStatus' => $request->string('status')->toString(), 'labelConfig' => config('packaging.label'),
        ]);
    }

    public function show(Carton $carton): View
    {
        $carton->load([
            'order.customer', 'order.destination', 'cartonType', 'packer:id,name', 'verifier:id,name',
            'items.product', 'materials.material', 'weightMeasurements.operator:id,name', 'weightVerifications.acceptedBy:id,name',
            'statusHistory.actor:id,name', 'labelPrints.printedBy:id,name',
        ]);

        return view('admin.package-station.carton-show', ['carton' => $carton, 'actions' => $this->availableActions($carton->status)]);
    }

    /** One endpoint for the carton's workflow buttons; each action maps to one domain action. */
    public function act(Request $request, Carton $carton, string $action, WeighCarton $weigh, ApproveAndSealCarton $approve): JsonResponse
    {
        abort_unless(in_array($action, $this->availableActions($carton->status), true), 409, "The {$action} action is not available for a {$carton->status->value} carton.");
        $user = $request->user();

        $updated = match ($action) {
            'request-weighing' => $weigh->requestWeighing($carton->id, $user),
            'weigh' => $weigh->measure(
                $carton->id,
                $request->validate(['measured_weight_g' => ['required', 'numeric', 'gt:0', 'decimal:0,3'], 'idempotency_key' => ['nullable', 'uuid']])['measured_weight_g'],
                $request->input('idempotency_key', (string) Str::uuid()),
                $user,
            )['carton'],
            'override' => $weigh->override($carton->id, $this->reason($request), $user),
            'return-to-packing' => $weigh->returnToPacking($carton->id, $this->reason($request), $user),
            'request-approval' => $approve->requestApproval($carton->id, $user),
            'verify' => $approve->verify($carton->id, $user),
            'reject' => $approve->reject($carton->id, $this->reason($request), $user),
            'seal' => $approve->seal($carton->id, $request->validate(['seal_number' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/']])['seal_number'], $user),
            'reopen' => $approve->reopen($carton->id, $this->reason($request), $user),
        };

        return response()->json(['data' => ['status' => $updated->status->value, 'verified_weight_g' => $updated->verified_weight_g]]);
    }

    public function printLabel(Request $request, Carton $carton, PrintCartonLabel $label): JsonResponse
    {
        $job = $label->print($carton->id, $request->input('reason'), $request->user());

        return response()->json(['data' => [
            'copy_number' => $job->copy_number, 'is_reprint' => $job->is_reprint, 'url' => route('cartons.label', $carton), 'label' => $this->labelPayload($carton),
        ]], 201);
    }

    /** First print of a whole packing session's labels; a carton that already has a print is skipped, never reprinted. */
    public function printLabelsFirst(Request $request, PrintCartonLabel $label): JsonResponse
    {
        $ids = $request->validate(['carton_ids' => ['required', 'array', 'min:1', 'max:200'], 'carton_ids.*' => ['integer', 'exists:cartons,id']])['carton_ids'];
        $printed = $alreadyPrinted = [];
        foreach (array_unique($ids) as $id) {
            if (LabelPrintJob::query()->where('carton_id', $id)->exists()) {
                $alreadyPrinted[] = $id;

                continue;
            }
            $printed[] = ['carton_id' => $label->print((int) $id, null, $request->user())->carton_id];
        }

        return response()->json(['data' => ['printed' => $printed, 'already_printed' => $alreadyPrinted]], 201);
    }

    /** The same label the package station prints after saving cartons, built from the saved carton. */
    public function label(Carton $carton): View
    {
        return view('admin.package-station.carton-label', [
            'carton' => $carton,
            'labelConfig' => config('packaging.label'),
            'labelData' => $this->labelPayload($carton),
        ]);
    }

    /**
     * The data the shared carton label (public/pack-pos/carton-label.js) renders.
     *
     * @return array<string, mixed>
     */
    private function labelPayload(Carton $carton): array
    {
        $carton->load(['order.destination', 'items.orderItem', 'items.product.brand']);

        return [
            'number' => $carton->carton_number,
            'boxNumber' => $carton->box_number,
            'invoice' => $carton->order->order_number,
            'type' => ucfirst(strtolower($carton->packaging_mode->value)),
            'destination' => $carton->order->destination->name,
            'weight' => (float) ($carton->manual_weight_g ?? $carton->system_weight_g ?? 0),
            'toleranceLower' => (float) $carton->weight_tolerance_lower_g,
            'toleranceUpper' => (float) $carton->weight_tolerance_upper_g,
            'packedAt' => $carton->created_at?->toIso8601String(),
            'contents' => $carton->items->where('quantity', '>', 0)->values()->map(fn ($item): array => [
                'brand' => $item->product->brand?->name ?? '—',
                'name' => $item->orderItem->original_description ?: $item->orderItem->original_sku,
                'sku' => $item->orderItem->original_sku,
                'quantity' => (float) $item->quantity,
                'netWeight' => (float) $item->quantity * (float) $item->unit_weight_snapshot_g,
            ])->all(),
        ];
    }

    /** @return list<string> */
    private function availableActions(CartonStatus $status): array
    {
        return match ($status) {
            CartonStatus::CONTENTS_VERIFIED => ['request-weighing'],
            CartonStatus::AWAITING_WEIGHT => ['weigh', 'return-to-packing'],
            CartonStatus::WEIGHT_FAILED => ['weigh', 'override', 'return-to-packing'],
            CartonStatus::WEIGHT_VERIFIED => ['request-approval', 'return-to-packing'],
            CartonStatus::AWAITING_APPROVAL => ['verify', 'reject'],
            CartonStatus::VERIFIED => ['seal', 'reject'],
            CartonStatus::SEALED => ['reopen'],
            default => [],
        };
    }

    private function reason(Request $request): string
    {
        return $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']])['reason'];
    }
}
