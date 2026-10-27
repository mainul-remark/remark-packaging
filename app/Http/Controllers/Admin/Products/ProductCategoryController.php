<?php

namespace App\Http\Controllers\Admin\Products;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ProductCategoryController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {
            $query = ProductCategory::query()
                ->leftJoin('product_categories as parent_categories', 'parent_categories.id', '=', 'product_categories.parent_category_id')
                ->select(['product_categories.*', 'parent_categories.name as parent_name'])
                ->withCount([
                    'products',
                    'children',
                    'products as active_products_count' => fn ($builder) => $builder->where('products.status', RecordStatus::ACTIVE->value),
                ])
                ->when(
                    $request->filled('status'),
                    fn ($builder) => $builder->where('product_categories.status', $request->string('status')->toString())
                )
                ->when($request->string('association')->toString() === 'with_products', fn ($builder) => $builder->has('products'))
                ->when($request->string('association')->toString() === 'without_products', fn ($builder) => $builder->doesntHave('products'));

            return DataTables::eloquent($query)
                ->editColumn('sap_id', fn (ProductCategory $category): string|int => $category->sap_id ?? '—')
                ->editColumn('status', fn (ProductCategory $category): string => $category->status->value)
                ->addColumn('show_url', fn (ProductCategory $category): string => route('product-categories.show', $category))
                ->toJson();
        }

        $summary = ProductCategory::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', [RecordStatus::ACTIVE->value])
            ->first();
        $summary->with_products = ProductCategory::query()->has('products')->count();
        $summary->linked_products = Product::query()->whereNotNull('category_id')->count();

        return view('admin.product-categories.index', compact('summary'));
    }

    public function show(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->load('parent:id,name')->loadCount(['products', 'children']);

        $products = $productCategory->products()
            ->with(['brand:id,name', 'primaryBarcode:id,product_id,barcode'])
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json([
            'id' => $productCategory->id,
            'name' => $productCategory->name,
            'code' => $productCategory->code,
            'sap_id' => $productCategory->sap_id,
            'parent' => $productCategory->parent?->name,
            'status' => $productCategory->status->value,
            'products_count' => $productCategory->products_count,
            'children_count' => $productCategory->children_count,
            'created_at' => $productCategory->created_at?->format('d M Y, h:i A'),
            'updated_at' => $productCategory->updated_at?->format('d M Y, h:i A'),
            'products_shown' => $products->count(),
            'products' => $products->map(fn (Product $product): array => [
                'name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand?->name,
                'primary_barcode' => $product->primaryBarcode?->barcode,
                'base_uom' => $product->base_uom,
                'verification_status' => $product->verification_status->value,
                'status' => $product->status->value,
                'image_url' => $this->imageUrl($product->image_path),
            ])->values(),
        ]);
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : Storage::disk('public')->url($path);
    }
}
