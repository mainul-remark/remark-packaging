<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('shows the brand catalogue page', function (): void {
    Brand::query()->create(['code' => 'ACME', 'name' => 'Acme']);

    $this->get(route('brands.index'))
        ->assertOk()
        ->assertSee('Brand catalogue')
        ->assertSee('brandsDataTable')
        ->assertSee('brandDetailsModal');
});

it('returns searchable server-side brand data and product counts', function (): void {
    $parent = Brand::query()->create(['code' => 'GLOBAL', 'name' => 'Global Brands']);
    $brand = Brand::query()->create(['parent_brand_id' => $parent->id, 'code' => 'ACME', 'name' => 'Acme Foods', 'sap_id' => 101]);
    Product::query()->create(['brand_id' => $brand->id, 'name' => 'Premium Rice', 'sku' => 'RICE-01']);
    Product::query()->create(['brand_id' => $brand->id, 'name' => 'Legacy Rice', 'sku' => 'RICE-02', 'status' => 'INACTIVE']);
    Brand::query()->create(['code' => 'EMPTY', 'name' => 'Empty Brand']);

    $this->getJson(route('brands.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'association' => 'with_products',
        'search' => ['value' => 'Acme', 'regex' => 'false'],
        'columns' => [
            ['data' => 'name', 'name' => 'brands.name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'code', 'name' => 'brands.code', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
        ],
    ]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.name', 'Acme Foods')
        ->assertJsonPath('data.0.parent_name', 'Global Brands')
        ->assertJsonPath('data.0.products_count', 2)
        ->assertJsonPath('data.0.active_products_count', 1);
});

it('returns brand details with linked products for the modal', function (): void {
    $category = ProductCategory::query()->create(['code' => 'FOOD', 'name' => 'Food']);
    $brand = Brand::query()->create(['code' => 'ACME', 'name' => 'Acme Foods']);
    Product::query()->create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => 'Premium Rice',
        'sku' => 'RICE-01',
        'verification_status' => 'VERIFIED',
    ]);

    $this->getJson(route('brands.show', $brand))
        ->assertOk()
        ->assertJsonPath('name', 'Acme Foods')
        ->assertJsonPath('products_count', 1)
        ->assertJsonPath('products.0.name', 'Premium Rice')
        ->assertJsonPath('products.0.category', 'Food');
});
