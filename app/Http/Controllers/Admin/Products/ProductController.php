<?php

namespace App\Http\Controllers\Admin\Products;

use App\Enums\ProductVerificationStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {
            $query = Product::query()
                ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->select([
                    'products.*',
                    'product_categories.name as category_name',
                    'brands.name as brand_name',
                ])
                ->with(['primaryBarcode:id,product_id,barcode'])
                ->withCount('barcodes')
                ->when(
                    $request->filled('status'),
                    fn ($builder) => $builder->where('products.status', $request->string('status')->toString())
                )
                ->when(
                    $request->filled('verification_status'),
                    fn ($builder) => $builder->where('products.verification_status', $request->string('verification_status')->toString())
                );

            return DataTables::eloquent($query)
                ->editColumn('sku', fn (Product $product): string => $product->sku ?: '—')
                ->addColumn('primary_barcode', fn (Product $product): string => $product->primaryBarcode?->barcode ?? '—')
                ->addColumn('image_url', fn (Product $product): ?string => $this->imageUrl($product->image_path))
                ->editColumn('verification_status', fn (Product $product): string => $product->verification_status->value)
                ->editColumn('status', fn (Product $product): string => $product->status->value)
                ->addColumn('show_url', fn (Product $product): string => route('products.show', $product))
                ->toJson();
        }

        $summary = Product::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', [RecordStatus::ACTIVE->value])
            ->selectRaw('SUM(CASE WHEN verification_status = ? THEN 1 ELSE 0 END) as verified', [ProductVerificationStatus::VERIFIED->value])
            ->selectRaw('SUM(CASE WHEN verification_status IN (?, ?, ?) THEN 1 ELSE 0 END) as needs_review', [
                ProductVerificationStatus::PROVISIONAL->value,
                ProductVerificationStatus::PENDING->value,
                ProductVerificationStatus::REJECTED->value,
            ])
            ->first();

        return view('admin.products.index', compact('summary'));
    }

    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category:id,name',
            'brand:id,name',
            'variant:id,name',
            'verifier:id,name',
            'barcodes' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('barcode'),
            'weightSpecs' => fn ($query) => $query->limit(5),
            'weightSpecs.approver:id,name',
        ]);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'image_url' => $this->imageUrl($product->image_path),
            'category' => $product->category?->name,
            'brand' => $product->brand?->name,
            'variant' => $product->variant?->name,
            'base_uom' => $product->base_uom,
            'units_per_carton' => $product->units_per_carton,
            'total_shelf_life' => $product->total_shelf_life,
            'requires_batch' => $product->requires_batch,
            'requires_expiry' => $product->requires_expiry,
            'verification_status' => $product->verification_status->value,
            'status' => $product->status->value,
            'verified_by' => $product->verifier?->name,
            'verified_at' => $product->verified_at?->format('d M Y, h:i A'),
            'created_at' => $product->created_at?->format('d M Y, h:i A'),
            'updated_at' => $product->updated_at?->format('d M Y, h:i A'),
            'barcodes' => $product->barcodes->map(fn ($barcode): array => [
                'barcode' => $barcode->barcode,
                'type' => $barcode->barcode_type->value,
                'quantity_multiplier' => $barcode->quantity_multiplier,
                'is_primary' => $barcode->is_primary,
                'status' => $barcode->status->value,
            ])->values(),
            'weight_specs' => $product->weightSpecs->map(fn ($spec): array => [
                'version' => $spec->version,
                'reference_weight_g' => $spec->reference_weight_g,
                'lower_tolerance_g' => $spec->lower_tolerance_g,
                'upper_tolerance_g' => $spec->upper_tolerance_g,
                'measurement_method' => $spec->measurement_method->value,
                'verification_status' => $spec->verification_status->value,
                'approved_by' => $spec->approver?->name,
                'effective_from' => $spec->effective_from?->format('d M Y, h:i A'),
                'effective_to' => $spec->effective_to?->format('d M Y, h:i A'),
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
