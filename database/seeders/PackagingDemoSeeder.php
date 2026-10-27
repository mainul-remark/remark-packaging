<?php

namespace Database\Seeders;

use App\Enums\WeightMeasurementMethod;
use App\Enums\WeightSpecStatus;
use App\Models\Brand;
use App\Models\CartonType;
use App\Models\Customer;
use App\Models\Destination;
use App\Models\Order;
use App\Models\PackagingMaterial;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductCategory;
use App\Models\ProductWeightSpec;
use Illuminate\Database\Seeder;

/**
 * Demo catalogue and open orders for the package-station prototype.
 * Replace with SAP-synced data once the API response shapes are known.
 */
class PackagingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $category = ProductCategory::query()->firstOrCreate(['code' => 'GROCERY'], ['name' => 'Grocery']);

        $catalogue = [
            ['sku' => 'RICE-1KG', 'name' => 'Basmati Rice 1kg', 'weight' => 1020, 'lower' => 10, 'upper' => 10, 'per_carton' => 12, 'barcode' => '8901000000011'],
            ['sku' => 'OIL-1L', 'name' => 'Sunflower Oil 1L', 'weight' => 930, 'lower' => 8, 'upper' => 8, 'per_carton' => 12, 'barcode' => '8901000000028'],
            ['sku' => 'SUGAR-2KG', 'name' => 'White Sugar 2kg', 'weight' => 2040, 'lower' => 15, 'upper' => 15, 'per_carton' => 6, 'barcode' => '8901000000035'],
            ['sku' => 'TEA-250G', 'name' => 'Black Tea 250g', 'weight' => 270, 'lower' => 4, 'upper' => 4, 'per_carton' => 24, 'barcode' => '8901000000042'],
            ['sku' => 'SALT-500G', 'name' => 'Iodised Salt 500g', 'weight' => 515, 'lower' => 5, 'upper' => 5, 'per_carton' => 20, 'barcode' => '8901000000059'],
        ];

        $brand = Brand::query()->firstOrCreate(['code' => 'REMARK'], ['name' => 'Remark']);

        $products = [];
        foreach ($catalogue as $row) {
            // updateOrCreate so products seeded before a brand existed get one too.
            $product = Product::query()->updateOrCreate(['sku' => $row['sku']], [
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'name' => $row['name'],
                'units_per_carton' => $row['per_carton'],
                'verification_status' => 'VERIFIED',
                'verified_at' => now(),
            ]);
            ProductBarcode::query()->firstOrCreate(['barcode' => $row['barcode']], [
                'product_id' => $product->id, 'barcode_type' => 'EAN', 'quantity_multiplier' => 1, 'is_primary' => true,
            ]);
            ProductWeightSpec::query()->firstOrCreate(['product_id' => $product->id, 'version' => 1], [
                'reference_weight_g' => $row['weight'], 'lower_tolerance_g' => $row['lower'], 'upper_tolerance_g' => $row['upper'],
                'measurement_method' => WeightMeasurementMethod::DECLARED, 'verification_status' => WeightSpecStatus::VERIFIED,
                'approved_at' => now(), 'effective_from' => now()->subDay(),
            ]);
            $products[$row['sku']] = $product;
        }

        foreach ([['SMALL', 'Small Carton', 180, 5, [300, 200, 150]], ['MEDIUM', 'Medium Carton', 320, 8, [400, 300, 250]], ['LARGE', 'Large Carton', 540, 12, [450, 320, 280]]] as [$code, $name, $tare, $tolerance, [$length, $width, $height]]) {
            CartonType::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'tare_weight_g' => $tare, 'tare_tolerance_g' => $tolerance,
                'length_mm' => $length, 'width_mm' => $width, 'height_mm' => $height,
            ]);
        }

        foreach ([['TAPE', 'Packing Tape Roll', 45, 2], ['BUBBLE', 'Bubble Wrap Sheet', 30, 2], ['SILICA', 'Silica Gel Pack', 10, 1]] as [$code, $name, $weight, $tolerance]) {
            PackagingMaterial::query()->firstOrCreate(['code' => $code], ['name' => $name, 'reference_weight_g' => $weight, 'tolerance_g' => $tolerance]);
        }

        $customer = Customer::query()->firstOrCreate(['code' => 'CUST-001'], ['name' => 'Fresh Mart Superstores']);
        $destination = Destination::query()->firstOrCreate(['code' => 'DST-DHK'], ['name' => 'Dhaka Distribution Hub']);

        $orders = [
            ['SO-1001', 'INV-5001', 1, ['RICE-1KG' => 48, 'OIL-1L' => 24, 'TEA-250G' => 48]],
            ['SO-1002', 'INV-5002', 3, ['SUGAR-2KG' => 18, 'SALT-500G' => 40]],
        ];
        foreach ($orders as [$number, $invoice, $priority, $lines]) {
            $order = Order::query()->firstOrCreate(['order_number' => $number], [
                'invoice_number' => $invoice, 'customer_id' => $customer->id, 'destination_id' => $destination->id,
                'order_date' => today(), 'priority' => $priority,
            ]);
            if ($order->wasRecentlyCreated) {
                $line = 10;
                foreach ($lines as $sku => $quantity) {
                    $order->items()->create([
                        'external_line_number' => (string) $line, 'product_id' => $products[$sku]->id, 'original_sku' => $sku,
                        'original_description' => $products[$sku]->name, 'required_quantity' => $quantity, 'uom' => 'PCS',
                    ]);
                    $line += 10;
                }
            }
        }
    }
}
