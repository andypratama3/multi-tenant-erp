<?php

namespace App\Http\Controllers\Api;

use App\Domain\Inventory\Models\Product;
use App\Http\Controllers\Controller;
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
        $query = Product::with(['category', 'unit']);

        if ($request->has('search')) {
            $query->where('sku', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%");
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return JsonResource::collection($query->paginate($request->integer('per_page', 20)));
    }

    /**
     * Get a single product.
     */
    public function show(Product $product): JsonResource
    {
        return JsonResource::make($product->load(['category', 'unit', 'currentStock']));
    }
}
