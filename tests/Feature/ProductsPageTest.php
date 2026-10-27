<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductCategory;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('shows the products catalogue page', function (): void {
    Product::query()->create(['name' => 'Sample product', 'sku' => 'SAMPLE-001']);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Product catalogue')
        ->assertSee('productsDataTable')
        ->assertSee('productDetailsModal');
});

it('returns server-side product data with filters', function (): void {
    $category = ProductCategory::query()->create(['code' => 'FOOD', 'name' => 'Food']);
    $brand = Brand::query()->create(['code' => 'ACME', 'name' => 'Acme']);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Premium Rice',
        'sku' => 'RICE-01',
        'verification_status' => 'VERIFIED',
    ]);
    ProductBarcode::query()->create([
        'product_id' => $product->id,
        'barcode' => '8901000000097',
        'barcode_type' => 'EAN',
        'quantity_multiplier' => 1,
        'is_primary' => true,
    ]);
    Product::query()->create(['name' => 'Hidden Product', 'sku' => 'HIDDEN-01', 'status' => 'INACTIVE']);
    Product::query()->create(['name' => 'Cooking Oil', 'sku' => 'OIL-01']);

    $this->getJson(route('products.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'status' => 'ACTIVE',
        'search' => ['value' => 'Premium', 'regex' => 'false'],
        'columns' => [
            ['data' => 'name', 'name' => 'products.name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'sku', 'name' => 'products.sku', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
        ],
    ]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.name', 'Premium Rice')
        ->assertJsonPath('data.0.category_name', 'Food')
        ->assertJsonPath('data.0.brand_name', 'Acme')
        ->assertJsonPath('data.0.primary_barcode', '8901000000097');
});

it('returns product details for the bootstrap modal', function (): void {
    $product = Product::query()->create([
        'name' => 'Tracked Product',
        'sku' => 'TRACK-01',
        'description' => 'A fully traceable product.',
        'requires_batch' => true,
    ]);
    ProductBarcode::query()->create([
        'product_id' => $product->id,
        'barcode' => '8901000000080',
        'barcode_type' => 'EAN',
        'quantity_multiplier' => 1,
        'is_primary' => true,
    ]);

    $this->getJson(route('products.show', $product))
        ->assertOk()
        ->assertJsonPath('name', 'Tracked Product')
        ->assertJsonPath('requires_batch', true)
        ->assertJsonPath('barcodes.0.barcode', '8901000000080');
});
