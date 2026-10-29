<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRemPackProducts extends Command
{
    protected $signature = 'import:rem-pack-products {--source=rem_pack : Source database on the same MySQL server} {--verifier=1 : User id stored in verified_by / approved_by}';

    protected $description = 'Imports categories, products, barcodes and weight specs from the rem_pack database, skipping products whose SKU already exists.';

    public function handle(): int
    {
        $source = $this->option('source');
        $verifier = (int) $this->option('verifier');
        $counts = ['categories' => 0, 'products' => 0, 'skipped_products' => 0, 'barcodes' => 0, 'weight_specs' => 0];

        DB::transaction(function () use ($source, $verifier, &$counts): void {
            // Old UUID => new bigint id.
            $categoryMap = [];
            foreach (DB::table("{$source}.product_categories")->orderBy('created_at')->get() as $category) {
                $existing = DB::table('product_categories')->where('code', $category->code)->value('id');
                $categoryMap[$category->id] = $existing ?? DB::table('product_categories')->insertGetId([
                    'parent_category_id' => null,
                    'code' => $category->code,
                    'name' => $category->name,
                    'status' => $category->status,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ]);
                $counts['categories'] += $existing === null ? 1 : 0;
            }

            $productMap = [];
            foreach (DB::table("{$source}.products")->orderBy('created_at')->get() as $product) {
                if ($product->sku !== null && DB::table('products')->where('sku', $product->sku)->exists()) {
                    $counts['skipped_products']++;
                    // Lets an interrupted import resume: children are matched to the product already holding this SKU.
                    $productMap[$product->id] = DB::table('products')->where('sku', $product->sku)->value('id');

                    continue;
                }
                $productMap[$product->id] = DB::table('products')->insertGetId([
                    'category_id' => $categoryMap[$product->category_id] ?? null,
                    'brand_id' => null,
                    'variant_id' => null,
                    'verified_by' => $product->verified_by ? $verifier : null,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'description' => $product->description,
                    'base_uom' => $product->base_uom,
                    'total_shelf_life' => null,
                    'units_per_carton' => $product->units_per_carton,
                    'image_path' => $product->image_path,
                    'image_updated_at' => $product->image_updated_at,
                    'requires_batch' => $product->requires_batch,
                    'requires_expiry' => $product->requires_expiry,
                    'verification_status' => $product->verification_status,
                    'verified_at' => $product->verified_at,
                    'status' => $product->status,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ]);
                $counts['products']++;
            }

            foreach (DB::table("{$source}.product_barcodes")->orderBy('created_at')->get() as $barcode) {
                if (! isset($productMap[$barcode->product_id]) || DB::table('product_barcodes')->where('barcode', $barcode->barcode)->exists()) {
                    continue;
                }
                $productId = $productMap[$barcode->product_id];
                DB::table('product_barcodes')->insert([
                    'product_id' => $productId,
                    'barcode' => $barcode->barcode,
                    'barcode_type' => $barcode->barcode_type,
                    'quantity_multiplier' => $barcode->quantity_multiplier,
                    'is_primary' => $barcode->is_primary,
                    'status' => $barcode->status,
                    'created_at' => $barcode->created_at,
                    'updated_at' => $barcode->updated_at,
                ]);
                $counts['barcodes']++;
            }

            foreach (DB::table("{$source}.product_weight_specs")->orderBy('created_at')->get() as $spec) {
                if (! isset($productMap[$spec->product_id])) {
                    continue;
                }
                $productId = $productMap[$spec->product_id];
                if (DB::table('product_weight_specs')->where('product_id', $productId)->where('version', $spec->version)->exists()) {
                    continue;
                }
                DB::table('product_weight_specs')->insert([
                    'product_id' => $productId,
                    'version' => $spec->version,
                    'reference_weight_g' => $spec->reference_weight_g,
                    'lower_tolerance_g' => $spec->lower_tolerance_g,
                    'upper_tolerance_g' => $spec->upper_tolerance_g,
                    'measurement_method' => $spec->measurement_method,
                    'verification_status' => $spec->verification_status,
                    'approved_by' => $spec->approved_by ? $verifier : null,
                    'approved_at' => $spec->approved_at,
                    'effective_from' => $spec->effective_from,
                    'effective_to' => $spec->effective_to,
                    'created_at' => $spec->created_at,
                ]);
                $counts['weight_specs']++;
            }
        });

        $this->table(['Imported', 'Count'], collect($counts)->map(fn (int $count, string $name): array => [$name, $count])->values()->all());

        return self::SUCCESS;
    }
}
