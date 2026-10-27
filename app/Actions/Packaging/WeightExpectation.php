<?php

namespace App\Actions\Packaging;

use App\Models\Carton;
use App\Models\CartonItem;
use App\Models\CartonMaterial;
use App\Models\ProductWeightSpec;

/**
 * Expected gross weight = packed product weights (from the spec versions snapshotted on the carton items)
 * + carton tare + packaging materials. Limits come only from governed rules: per-unit product tolerances,
 * the carton type's tare tolerance and each material's tolerance. There is no global tolerance constant.
 * All values are fixed-precision gram strings.
 */
class WeightExpectation
{
    /**
     * @return array{products_g: string, tare_g: string, materials_g: string, expected_g: string, lower_g: string, upper_g: string, tolerance: array<string, string>, lines: list<array<string, string>>}
     */
    public function forCarton(Carton $carton): array
    {
        $carton->loadMissing('cartonType');
        $type = $carton->cartonType ?? throw new PackagingException('CARTON_TYPE_REQUIRED', 'Choose a carton type so its tare weight can be included.', ['carton_id' => $carton->id]);

        $items = CartonItem::query()->where('carton_id', $carton->id)->where('quantity', '>', 0)->with('product:id,sku')->get();
        if ($items->isEmpty()) {
            throw new PackagingException('CARTON_EMPTY', 'An empty carton has no expected weight.', ['carton_id' => $carton->id]);
        }

        $missing = $items->filter(fn (CartonItem $item): bool => $item->weight_spec_id === null || $item->unit_weight_snapshot_g === null)->map(fn ($item) => $item->product->sku)->unique()->values();
        if ($missing->isNotEmpty()) {
            throw new PackagingException('WEIGHT_SPEC_MISSING', 'No approved unit weight for: '.$missing->implode(', ').'.', ['skus' => $missing->all()]);
        }

        $specs = ProductWeightSpec::query()->whereIn('id', $items->pluck('weight_spec_id'))->get()->keyBy('id');
        $products = $lowProducts = $upProducts = '0.000';
        $lines = [];
        foreach ($items as $item) {
            $spec = $specs[$item->weight_spec_id];
            $qty = (string) $item->quantity;
            $weight = bcmul($qty, (string) $item->unit_weight_snapshot_g, 3);
            $products = bcadd($products, $weight, 3);
            $lowProducts = bcadd($lowProducts, bcmul($qty, (string) $spec->lower_tolerance_g, 3), 3);
            $upProducts = bcadd($upProducts, bcmul($qty, (string) $spec->upper_tolerance_g, 3), 3);
            $lines[] = ['sku' => $item->product->sku, 'quantity' => $qty, 'unit_weight_g' => (string) $item->unit_weight_snapshot_g, 'weight_g' => $weight];
        }

        $materials = $materialTolerance = '0.000';
        foreach (CartonMaterial::query()->where('carton_id', $carton->id)->get() as $material) {
            $materials = bcadd($materials, (string) $material->total_weight_g, 3);
            $materialTolerance = bcadd($materialTolerance, bcmul((string) $material->quantity, (string) $material->unit_tolerance_snapshot_g, 3), 3);
        }

        $tare = (string) $type->tare_weight_g;
        $tareTolerance = (string) $type->tare_tolerance_g;
        $expected = bcadd(bcadd($products, $tare, 3), $materials, 3);
        $extra = bcadd($tareTolerance, $materialTolerance, 3);

        return [
            'products_g' => $products,
            'tare_g' => $tare,
            'materials_g' => $materials,
            'expected_g' => $expected,
            'lower_g' => bcsub($expected, bcadd($lowProducts, $extra, 3), 3),
            'upper_g' => bcadd($expected, bcadd($upProducts, $extra, 3), 3),
            'tolerance' => ['products_lower_g' => $lowProducts, 'products_upper_g' => $upProducts, 'tare_g' => $tareTolerance, 'materials_g' => $materialTolerance],
            'lines' => $lines,
        ];
    }
}
