<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;

beforeEach(function (): void {
    $this->seed(RoleTableSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('administrator'));
});

it('shows the product category catalogue page', function (): void {
    ProductCategory::query()->create(['code' => 'FOOD', 'name' => 'Food']);

    $this->get(route('product-categories.index'))
        ->assertOk()
        ->assertSee('Category catalogue')
        ->assertSee('categoriesDataTable')
        ->assertSee('categoryDetailsModal');
});

it('returns searchable server-side category data and hierarchy counts', function (): void {
    $parent = ProductCategory::query()->create(['code' => 'FOOD', 'name' => 'Food']);
    $category = ProductCategory::query()->create(['parent_category_id' => $parent->id, 'code' => 'RICE', 'name' => 'Premium Rice', 'sap_id' => 201]);
    ProductCategory::query()->create(['parent_category_id' => $category->id, 'code' => 'BASMATI', 'name' => 'Basmati Rice']);
    Product::query()->create(['category_id' => $category->id, 'name' => 'Basmati Rice 1kg', 'sku' => 'RICE-01']);
    Product::query()->create(['category_id' => $category->id, 'name' => 'Legacy Rice', 'sku' => 'RICE-02', 'status' => 'INACTIVE']);
    ProductCategory::query()->create(['code' => 'EMPTY', 'name' => 'Empty Category']);

    $this->getJson(route('product-categories.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'association' => 'with_products',
        'search' => ['value' => 'Premium', 'regex' => 'false'],
        'columns' => [
            ['data' => 'name', 'name' => 'product_categories.name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'code', 'name' => 'product_categories.code', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
        ],
    ]), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.name', 'Premium Rice')
        ->assertJsonPath('data.0.parent_name', 'Food')
        ->assertJsonPath('data.0.children_count', 1)
        ->assertJsonPath('data.0.products_count', 2)
        ->assertJsonPath('data.0.active_products_count', 1);
});

it('returns category details with linked products for the modal', function (): void {
    $brand = Brand::query()->create(['code' => 'ACME', 'name' => 'Acme']);
    $category = ProductCategory::query()->create(['code' => 'FOOD', 'name' => 'Food']);
    Product::query()->create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'name' => 'Premium Rice',
        'sku' => 'RICE-01',
        'verification_status' => 'VERIFIED',
    ]);

    $this->getJson(route('product-categories.show', $category))
        ->assertOk()
        ->assertJsonPath('name', 'Food')
        ->assertJsonPath('products_count', 1)
        ->assertJsonPath('products.0.name', 'Premium Rice')
        ->assertJsonPath('products.0.brand', 'Acme');
});
