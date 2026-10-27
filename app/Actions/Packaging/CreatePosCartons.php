<?php

namespace App\Actions\Packaging;

use App\Enums\CartonStatus;
use App\Enums\PackagingMode;
use App\Enums\ProductVerificationStatus;
use App\Enums\WeightSpecStatus;
use App\Models\Carton;
use App\Models\CartonItem;
use App\Models\CartonItemEvent;
use App\Models\CartonMaterial;
use App\Models\CartonStatusHistory;
use App\Models\CartonType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackagingMaterial;
use App\Models\Product;
use App\Models\ProductWeightSpec;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Creates every carton built in one packing POS session in a single transaction. Carton numbers, system weight,
 * tolerance and limits are computed here from governed data (carton types, materials, product weight specs);
 * the page only supplies what the operator chose or measured (contents, machine and manual weight).
 * The whole batch is idempotent on its key: replaying it returns the cartons it already created.
 */
class CreatePosCartons
{
    public function __construct(private readonly ChangeCartonStatus $status, private readonly WeightExpectation $expectation) {}

    /**
     * @param  list<array<string, mixed>>  $specs  validated carton specs; a spec with copies > 1 expands to identical cartons
     * @return array{cartons: list<Carton>, replayed: bool}
     */
    public function execute(int $orderId, string $batchKey, array $specs, User $operator): array
    {
        try {
            return DB::transaction(fn () => $this->create($orderId, $batchKey, $specs, $operator));
        } catch (UniqueConstraintViolationException) {
            return $this->replay($batchKey)
                ?? throw new PackagingException('DUPLICATE_BATCH', 'These cartons were already submitted.', [], 409);
        }
    }

    private function create(int $orderId, string $batchKey, array $specs, User $operator): array
    {
        if ($replay = $this->replay($batchKey)) {
            return $replay;
        }

        $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
        if (! in_array($order->status, ['OPEN', 'IN_PROGRESS'], true)) {
            throw new PackagingException('INVALID_ORDER_STATE', "Cartons can only be created for open orders; this one is {$order->status}.", [
                'order_id' => $order->id, 'status' => $order->status,
            ], 409);
        }

        $lines = OrderItem::query()->where('order_id', $order->id)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $remaining = [];
        foreach ($lines as $line) {
            $remaining[$line->id] = bcsub((string) $line->required_quantity, $this->packed($line->id), 3);
        }

        $plans = [];
        foreach ($specs as $spec) {
            $copies = (int) ($spec['copies'] ?? 1);
            $type = CartonType::query()->where('status', 'ACTIVE')->findOrFail($spec['carton_type_id']);
            $mode = PackagingMode::from($spec['packaging_mode'] ?? PackagingMode::ASSORTED->value);
            // A sorted carton holds a single product; several products make it assorted.
            if ($mode === PackagingMode::SORTED && count(array_unique(array_column($spec['items'], 'order_item_id'))) !== 1) {
                throw new PackagingException('SORTED_SINGLE_PRODUCT', 'A sorted carton holds one product. Use an assorted carton for several products.', ['draft' => $spec['draft_reference'] ?? null]);
            }
            if ($mode === PackagingMode::SORTED) {
                $sortedLine = $lines[$spec['items'][0]['order_item_id']] ?? throw new PackagingException('WRONG_SKU', 'A product is not part of this order.', ['order_item_id' => $spec['items'][0]['order_item_id']]);
                $packSize = number_format((float) $sortedLine->product->units_per_carton, 3, '.', '');
                if (bccomp($packSize, '0', 3) <= 0 || bccomp(number_format((float) $spec['items'][0]['quantity'], 3, '.', ''), $packSize, 3) !== 0) {
                    throw new PackagingException('SORTED_PACK_SIZE', "A sorted carton of {$sortedLine->original_sku} must hold its pack size of {$packSize} units.", ['sku' => $sortedLine->original_sku, 'pack_size' => $packSize]);
                }
            }

            for ($copy = 0; $copy < $copies; $copy++) {
                $items = [];
                foreach ($spec['items'] as $item) {
                    $line = $lines[$item['order_item_id']] ?? throw new PackagingException('WRONG_SKU', 'A product is not part of this order.', ['order_item_id' => $item['order_item_id']]);
                    $quantity = number_format((float) $item['quantity'], 3, '.', '');
                    if (bccomp($remaining[$line->id], $quantity, 3) < 0) {
                        throw new PackagingException('ORDER_QUANTITY_EXCEEDED', sprintf(
                            'Not enough of %s remains on this order for carton %s (needs %s).', $line->original_sku, $spec['draft_reference'] ?? '', $quantity,
                        ), ['sku' => $line->original_sku, 'draft' => $spec['draft_reference'] ?? null]);
                    }
                    $remaining[$line->id] = bcsub($remaining[$line->id], $quantity, 3);
                    $items[] = ['line' => $line, 'quantity' => $quantity];
                }
                // carton_items is unique per carton and order line, so a product can appear only once in a carton.
                if (count(array_unique(array_map(fn (array $item): int => $item['line']->id, $items))) !== count($items)) {
                    throw new PackagingException('DUPLICATE_PRODUCT_IN_CARTON', 'A product can appear only once in a carton.', ['draft' => $spec['draft_reference'] ?? null]);
                }
                $plans[] = ['spec' => $spec, 'type' => $type, 'mode' => $mode, 'items' => $items];
            }
        }

        if ($order->status === 'OPEN') {
            $order->update(['status' => 'IN_PROGRESS']);
        }

        $specCache = $this->weightSpecs(collect($plans)->flatMap(fn (array $plan): array => array_map(fn (array $item): int => $item['line']->product_id, $plan['items']))->unique()->all());
        $sequence = $this->lastSequence($order);
        $cartons = [];
        foreach ($plans as $position => $plan) {
            $cartons[] = $this->persist($order, $plan, ++$sequence, $batchKey, $position + 1, $specCache, $operator);
        }

        if (collect($remaining)->every(fn (string $left): bool => bccomp($left, '0', 3) <= 0)) {
            $order->update(['status' => 'PACKED']);
        }

        return ['cartons' => $cartons, 'replayed' => false];
    }

    /** @param array<int, ProductWeightSpec> $specCache */
    private function persist(Order $order, array $plan, int $sequence, string $batchKey, int $position, array $specCache, User $operator): Carton
    {
        $spec = $plan['spec'];
        $units = '0.000';
        foreach ($plan['items'] as $item) {
            $units = bcadd($units, $item['quantity'], 3);
        }

        $carton = Carton::query()->create([
            'carton_number' => sprintf('%s-C%03d', $order->order_number, $sequence),
            'order_id' => $order->id,
            'carton_type_id' => $plan['type']->id,
            'packaging_mode' => $plan['mode'],
            'creation_channel' => 'PACKING_POS',
            'status' => CartonStatus::CREATED,
            'unit_count' => $units,
            'machine_weight_g' => $this->grams($spec['machine_weight_g'] ?? null),
            'manual_weight_g' => $this->grams($spec['manual_weight_g'] ?? null),
            'draft_reference' => $spec['draft_reference'] ?? null,
            'batch_key' => $batchKey,
            'batch_sequence' => $position,
            'packed_by' => $operator->id,
            'version' => 1,
        ]);
        CartonStatusHistory::query()->create([
            'carton_id' => $carton->id, 'from_status' => null, 'to_status' => CartonStatus::CREATED->value,
            'actor_id' => $operator->id, 'reason' => 'Created in packing POS', 'occurred_at' => now(),
        ]);

        foreach ($plan['items'] as $index => $item) {
            $weightSpec = $specCache[$item['line']->product_id];
            $row = CartonItem::query()->create([
                'carton_id' => $carton->id, 'order_item_id' => $item['line']->id, 'product_id' => $item['line']->product_id, 'weight_spec_id' => $weightSpec->id,
                'quantity' => $item['quantity'], 'unit_weight_snapshot_g' => $weightSpec->reference_weight_g,
            ]);
            CartonItemEvent::query()->create([
                'carton_id' => $carton->id, 'carton_item_id' => $row->id, 'product_id' => $item['line']->product_id, 'order_item_id' => $item['line']->id,
                'scanned_barcode' => null, 'quantity_delta' => $item['quantity'], 'event_type' => 'POS_PACKED', 'operator_id' => $operator->id,
                'idempotency_key' => Uuid::uuid5($batchKey, "carton-{$position}-item-{$index}")->toString(), 'occurred_at' => now(),
            ]);
        }

        $materials = [];
        if (count(array_unique(array_column($spec['materials'] ?? [], 'material_id'))) !== count($spec['materials'] ?? [])) {
            throw new PackagingException('DUPLICATE_MATERIAL', 'A packaging material can appear only once in a carton.', ['draft' => $spec['draft_reference'] ?? null]);
        }
        foreach ($spec['materials'] ?? [] as $entry) {
            $material = PackagingMaterial::query()->where('status', 'ACTIVE')->findOrFail($entry['material_id']);
            $quantity = number_format((float) $entry['quantity'], 3, '.', '');
            CartonMaterial::query()->create([
                'carton_id' => $carton->id, 'material_id' => $material->id, 'quantity' => $quantity,
                'unit_weight_snapshot_g' => $material->reference_weight_g, 'unit_tolerance_snapshot_g' => $material->tolerance_g,
                'total_weight_g' => bcmul($quantity, (string) $material->reference_weight_g, 3),
            ]);
            $materials[] = ['code' => $material->code, 'quantity' => $quantity, 'unit_weight_g' => (string) $material->reference_weight_g, 'unit_tolerance_g' => (string) $material->tolerance_g];
        }

        $calc = $this->expectation->forCarton($carton->refresh());
        $weights = [
            'expected' => $calc['expected_g'], 'lower' => $calc['lower_g'], 'upper' => $calc['upper_g'],
            'tolerance_lower' => bcsub($calc['expected_g'], $calc['lower_g'], 3), 'tolerance_upper' => bcsub($calc['upper_g'], $calc['expected_g'], 3),
            'breakdown' => ['source' => 'CALCULATED', 'products_g' => $calc['products_g'], 'tare_g' => $calc['tare_g'], 'materials_g' => $calc['materials_g'], 'tolerance' => $calc['tolerance'], 'lines' => $calc['lines'], 'materials' => $materials],
        ];

        // Compare what the operator measured (manual entry first, else the machine) with the allowed range. A carton outside
        // it is only saved when the operator confirmed it, and is then flagged.
        $entered = $carton->manual_weight_g ?? $carton->machine_weight_g;
        $flag = null;
        if ($entered !== null) {
            $flag = bccomp((string) $entered, $weights['upper'], 3) > 0 ? 'OVERWEIGHT' : (bccomp((string) $entered, $weights['lower'], 3) < 0 ? 'UNDERWEIGHT' : null);
            if ($flag !== null && ! ($spec['weight_flag_acknowledged'] ?? false)) {
                throw new PackagingException('WEIGHT_OUT_OF_RANGE', sprintf(
                    'The entered weight %s g is outside the allowed range %s - %s g; confirm it to add the carton as %s.',
                    (string) $entered, $weights['lower'], $weights['upper'], strtolower($flag),
                ), ['draft' => $spec['draft_reference'] ?? null, 'weight_flag' => $flag, 'entered_g' => (string) $entered, 'lower_g' => $weights['lower'], 'upper_g' => $weights['upper']]);
            }
        }

        $carton->update([
            'weight_flag' => $flag, 'weight_variance_g' => $entered === null ? null : bcsub((string) $entered, $weights['expected'], 3),
            'weight_flag_acknowledged_by' => $flag ? $operator->id : null, 'weight_flag_acknowledged_at' => $flag ? now() : null,
            'expected_weight_g' => $weights['expected'], 'system_weight_g' => $weights['expected'],
            'weight_tolerance_lower_g' => $weights['tolerance_lower'], 'weight_tolerance_upper_g' => $weights['tolerance_upper'],
            'weight_lower_limit_g' => $weights['lower'], 'weight_upper_limit_g' => $weights['upper'],
            'weight_breakdown' => $weights['breakdown'],
        ]);

        $this->status->apply($carton, CartonStatus::CONTENTS_VERIFIED, $operator, 'Created in packing POS');

        return $carton->refresh();
    }

    /**
     * Every product must be verified and have an approved unit weight; checked once per product for the batch.
     *
     * @param  list<int>  $productIds
     * @return array<int, ProductWeightSpec>
     */
    private function weightSpecs(array $productIds): array
    {
        $cache = [];
        foreach (Product::query()->whereIn('id', $productIds)->get() as $product) {
            if ($product->verification_status !== ProductVerificationStatus::VERIFIED) {
                throw new PackagingException('PRODUCT_NOT_VERIFIED', "{$product->sku} is {$product->verification_status->value} and cannot be packed until verified.", ['sku' => $product->sku]);
            }
            $cache[$product->id] = ProductWeightSpec::query()->where('product_id', $product->id)->where('verification_status', WeightSpecStatus::VERIFIED->value)->whereNull('effective_to')->first()
                ?? throw new PackagingException('WEIGHT_SPEC_MISSING', "No approved unit weight for {$product->sku}; the carton weight needs one.", ['skus' => [$product->sku]]);
        }

        return $cache;
    }

    private function lastSequence(Order $order): int
    {
        $prefix = "{$order->order_number}-C";
        $highest = 0;
        foreach (Carton::query()->where('order_id', $order->id)->pluck('carton_number') as $number) {
            if (! str_starts_with($number, $prefix)) {
                continue;
            }
            $tail = substr($number, strlen($prefix));
            if (ctype_digit($tail)) {
                $highest = max($highest, (int) $tail);
            }
        }

        return $highest;
    }

    private function grams(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : number_format((float) $value, 3, '.', '');
    }

    private function packed(int $orderItemId): string
    {
        $sum = CartonItem::query()->join('cartons', 'cartons.id', '=', 'carton_items.carton_id')
            ->where('cartons.status', '!=', CartonStatus::CANCELLED->value)
            ->where('carton_items.order_item_id', $orderItemId)->sum('carton_items.quantity');

        return number_format((float) $sum, 3, '.', '');
    }

    /** @return array{cartons: list<Carton>, replayed: bool}|null */
    private function replay(string $batchKey): ?array
    {
        /** @var Collection<int, Carton> $cartons */
        $cartons = Carton::query()->where('batch_key', $batchKey)->orderBy('batch_sequence')->get();

        return $cartons->isEmpty() ? null : ['cartons' => $cartons->all(), 'replayed' => true];
    }
}
