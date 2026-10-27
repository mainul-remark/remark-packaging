<?php

namespace App\Http\Controllers\Admin\Products;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {
            $query = Brand::query()
                ->leftJoin('brands as parent_brands', 'parent_brands.id', '=', 'brands.parent_brand_id')
                ->select(['brands.*', 'parent_brands.name as parent_name'])
                ->withCount([
                    'products',
                    'products as active_products_count' => fn ($builder) => $builder->where('products.status', RecordStatus::ACTIVE->value),
                ])
                ->when(
                    $request->filled('status'),
                    fn ($builder) => $builder->where('brands.status', $request->string('status')->toString())
                )
                ->when($request->string('association')->toString() === 'with_products', fn ($builder) => $builder->has('products'))
                ->when($request->string('association')->toString() === 'without_products', fn ($builder) => $builder->doesntHave('products'));

            return DataTables::eloquent($query)
                ->editColumn('sap_id', fn (Brand $brand): string|int => $brand->sap_id ?? '—')
                ->editColumn('status', fn (Brand $brand): string => $brand->status->value)
                ->addColumn('show_url', fn (Brand $brand): string => route('brands.show', $brand))
                ->toJson();
        }

        $summary = Brand::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', [RecordStatus::ACTIVE->value])
            ->first();
        $summary->with_products = Brand::query()->has('products')->count();
        $summary->linked_products = Product::query()->whereNotNull('brand_id')->count();

        return view('admin.brands.index', compact('summary'));
    }

    public function show(Brand $brand): JsonResponse
    {
        $brand->load('parent:id,name')->loadCount(['products', 'children']);

        $products = $brand->products()
            ->with(['category:id,name', 'primaryBarcode:id,product_id,barcode'])
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json([
            'id' => $brand->id,
            'name' => $brand->name,
            'code' => $brand->code,
            'sap_id' => $brand->sap_id,
            'parent' => $brand->parent?->name,
            'status' => $brand->status->value,
            'products_count' => $brand->products_count,
            'children_count' => $brand->children_count,
            'created_at' => $brand->created_at?->format('d M Y, h:i A'),
            'updated_at' => $brand->updated_at?->format('d M Y, h:i A'),
            'products_shown' => $products->count(),
            'products' => $products->map(fn (Product $product): array => [
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name,
                'primary_barcode' => $product->primaryBarcode?->barcode,
                'base_uom' => $product->base_uom,
                'verification_status' => $product->verification_status->value,
                'status' => $product->status->value,
                'image_url' => $this->imageUrl($product->image_path),
                'show_url' => route('products.show', $product),
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
