<?php

namespace App\Http\Controllers\Api;

use App\Domain\Inventory\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Products
 *
 * APIs for managing products.
 */
class ProductController extends Controller
{
    /**
     * List all products.
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with(['category', 'unit']);
        $search = $request->string('search')->toString();

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return JsonResource::collection(
            $query->paginate(max(1, min(100, $request->integer('per_page', 20))))
        );
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Produk berhasil dibuat.',
            'data' => $product->load(['category', 'unit']),
        ], 201);
    }

    /**
     * Get a single product.
     */
    public function show(Product $product): JsonResource
    {
        $this->authorize('view', $product);

        return JsonResource::make($product->load(['category', 'unit', 'currentStock']));
    }

    public function update(ProductRequest $request, Product $product): JsonResource
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return JsonResource::make($product->load(['category', 'unit']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
