<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Packaging\CreatePosCartons;
use App\Enums\CartonStatus;
use App\Enums\PackagingMode;
use App\Enums\WeightSpecStatus;
use App\Http\Controllers\Controller;
use App\Models\Carton;
use App\Models\CartonType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackagingMaterial;
use App\Models\ProductWeightSpec;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackageStationController extends Controller
{
    private const COLORS = ['#e95f68', '#4b8db8', '#d9a441', '#9c6cc3', '#2e9e7a', '#d97b3a', '#5a6ad6', '#8a8f98'];

    /** Without ?order= the station lists packable orders; with it, the packing workspace for that order opens. */
    public function index(Request $request): View
    {
        $selected = $request->integer('order');
        $order = $selected ? Order::query()->with(['customer', 'destination', 'items.product.barcodes'])->findOrFail($selected) : null;
        $packable = $this->packableOrders();

        return view('admin.package-station.index', [
            'order' => $order,
            'data' => $order ? $this->workspace($order) : $this->emptyWorkspace(),
            'orders' => $order ? [] : $packable['rows'],
            'orderTotal' => $packable['total'],
        ]);
    }

    /** Opens the open order that matches an invoice or order number, so the station's search box can jump to it. */
    public function find(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        $open = Order::query()->whereIn('status', ['OPEN', 'IN_PROGRESS']);
        $order = (clone $open)->where(fn ($query) => $query->where('order_number', $term)->orWhere('invoice_number', $term))->first()
            ?? (clone $open)->where(fn ($query) => $query->where('order_number', 'like', "%{$term}%")->orWhere('invoice_number', 'like', "%{$term}%"))->orderBy('priority')->first();

        return $order
            ? response()->json(['data' => ['id' => $order->id]])
            : response()->json(['message' => "No open order or invoice found for {$term}."], 404);
    }

    public function store(Request $request, CreatePosCartons $create): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')],
            'idempotency_key' => ['required', 'uuid'],
            'cartons' => ['required', 'array', 'min:1', 'max:100'],
            'cartons.*.draft_reference' => ['nullable', 'string', 'max:16'],
            'cartons.*.packaging_mode' => ['nullable', Rule::enum(PackagingMode::class)],
            'cartons.*.copies' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cartons.*.carton_type_id' => ['required', 'integer', Rule::exists('carton_types', 'id')->where('status', 'ACTIVE')],
            'cartons.*.items' => ['required', 'array', 'min:1'],
            'cartons.*.items.*.order_item_id' => ['required', 'integer'],
            'cartons.*.items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'cartons.*.materials' => ['nullable', 'array'],
            'cartons.*.materials.*.material_id' => ['required', 'integer', Rule::exists('packaging_materials', 'id')],
            'cartons.*.materials.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'cartons.*.machine_weight_g' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3'],
            'cartons.*.manual_weight_g' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3'],
            'cartons.*.weight_flag_acknowledged' => ['nullable', 'boolean'],
        ]);
        if (collect($data['cartons'])->sum(fn (array $carton): int => (int) ($carton['copies'] ?? 1)) > 200) {
            return response()->json(['success' => false, 'code' => 'TOO_MANY_CARTONS', 'message' => 'A packing session can create at most 200 cartons at once.'], 422);
        }

        $result = $create->execute($data['order_id'], $data['idempotency_key'], $data['cartons'], $request->user());

        return response()->json(['data' => [
            'cartons' => array_map(fn (Carton $carton): array => [
                'id' => $carton->id,
                'carton_number' => $carton->carton_number,
                'unit_count' => (float) $carton->unit_count,
                'system_weight_g' => (float) $carton->system_weight_g,
                'weight_tolerance_lower_g' => (float) $carton->weight_tolerance_lower_g,
                'weight_tolerance_upper_g' => (float) $carton->weight_tolerance_upper_g,
                'manual_weight_g' => $carton->manual_weight_g === null ? null : (float) $carton->manual_weight_g,
                'weight_flag' => $carton->weight_flag,
            ], $result['cartons']),
            'replayed' => $result['replayed'],
        ]], $result['replayed'] ? 200 : 201);
    }

    /** @return array{rows: list<array<string, mixed>>, total: int} */
    private function packableOrders(): array
    {
        $open = Order::query()->whereIn('status', ['OPEN', 'IN_PROGRESS']);
        $orders = (clone $open)->with(['customer'])->withCount('items')->withSum('items as required_units', 'required_quantity')
            ->orderBy('priority')->orderBy('order_date')->orderBy('id')->limit(10)->get();

        return [
            'rows' => $orders->map(fn (Order $order): array => [
                'id' => (string) $order->id,
                'number' => $order->order_number,
                'order' => $order->invoice_number ?? $order->customer->name,
                'status' => str_replace('_', ' ', $order->status),
                'lines' => $order->items_count,
                'units' => (float) $order->required_units,
            ])->all(),
            'total' => (clone $open)->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyWorkspace(): array
    {
        return [
            'workOrder' => [
                'id' => '', 'number' => 'No order selected', 'invoice' => '—', 'order' => '—', 'customer' => '—', 'destination' => '—',
                'orderDate' => '—', 'dueDate' => '—', 'priority' => '—', 'status' => '—',
            ],
            'products' => [], 'cartonTypes' => [], 'prepackedTemplates' => [], 'existingCartons' => [], 'packagingItems' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function workspace(Order $order): array
    {
        $packed = OrderItem::packedQuantities($order->items->pluck('id')->all());
        $specs = ProductWeightSpec::query()->whereIn('product_id', $order->items->pluck('product_id'))
            ->where('verification_status', WeightSpecStatus::VERIFIED->value)->whereNull('effective_to')->get()->keyBy('product_id');

        // Ids are strings because the packing script compares them with values read from the DOM.
        $products = $order->items->values()->map(function (OrderItem $item, int $index) use ($packed, $specs): array {
            $barcodes = $item->product->barcodes->filter(fn ($code): bool => $code->status->value === 'ACTIVE');

            return [
                'id' => (string) $item->id,
                'sku' => $item->original_sku,
                'name' => $item->original_description ?: $item->original_sku,
                'barcode' => $barcodes->first()?->barcode ?? $item->original_sku,
                'barcodes' => $barcodes->map(fn ($code): array => ['barcode' => $code->barcode, 'multiplier' => (float) $code->quantity_multiplier])->values(),
                'unitWeight' => (float) ($specs[$item->product_id]->reference_weight_g ?? 0),
                'lowerTolerance' => (float) ($specs[$item->product_id]->lower_tolerance_g ?? 0),
                'upperTolerance' => (float) ($specs[$item->product_id]->upper_tolerance_g ?? 0),
                'unitsPerCarton' => (int) $item->product->units_per_carton,
                'ordered' => (float) $item->required_quantity,
                'packed' => $packed[$item->id] ?? 0.0,
                'color' => self::COLORS[$index % count(self::COLORS)],
            ];
        });

        return [
            'workOrder' => [
                'id' => (string) $order->id,
                'number' => $order->order_number,
                'invoice' => $order->invoice_number ?? '—',
                'order' => $order->order_number,
                'customer' => $order->customer->name,
                'destination' => $order->destination->name,
                'orderDate' => $order->order_date->format('M j, Y'),
                'dueDate' => '—',
                'priority' => $this->priorityLabel($order->priority),
                'status' => str_replace('_', ' ', $order->status),
            ],
            'products' => $products,
            'cartonTypes' => CartonType::query()->where('status', 'ACTIVE')->orderBy('tare_weight_g')->get()->map(fn (CartonType $type): array => [
                'id' => (string) $type->id, 'code' => $type->code, 'name' => $type->name, 'weight' => (float) $type->tare_weight_g,
                'tolerance' => (float) $type->tare_tolerance_g, 'capacity' => 'any quantity',
            ]),
            'prepackedTemplates' => [],
            'packagingItems' => PackagingMaterial::query()->where('status', 'ACTIVE')->orderBy('name')->get()->map(fn (PackagingMaterial $material): array => [
                'id' => (string) $material->id, 'code' => $material->code, 'name' => $material->name,
                'weight' => (float) $material->reference_weight_g, 'tolerance' => (float) $material->tolerance_g,
            ]),
            'existingCartons' => Carton::query()->where('order_id', $order->id)->where('status', '!=', CartonStatus::CANCELLED->value)->orderByDesc('id')->limit(100)->get()->map(fn (Carton $carton): array => [
                'number' => $carton->carton_number, 'units' => (float) $carton->unit_count, 'type' => ucfirst(strtolower($carton->packaging_mode->value)),
                'status' => str_replace('_', ' ', $carton->status->value), 'weight' => (float) ($carton->verified_weight_g ?? $carton->system_weight_g ?? 0),
            ]),
        ];
    }

    private function priorityLabel(int $priority): string
    {
        return $priority <= 1 ? 'High' : ($priority <= 3 ? 'Medium' : 'Normal');
    }
}
