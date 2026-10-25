<?php

namespace App\Http\Controllers\Api;

use App\Domain\Inventory\Models\StockBalance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Stock Balances
 *
 * APIs for viewing current stock balances.
 */
class StockBalanceController extends Controller
{
    /**
     * List all stock balances.
     */
    public function index(): ResourceCollection
    {
        return JsonResource::collection(
            StockBalance::with(['product.unit', 'warehouse'])
                ->orderBy('product_id')
                ->paginate(50)
        );
    }

    /**
     * Get stock balance for a specific product.
     */
    public function byProduct(string $productId): JsonResource
    {
        return JsonResource::collection(
            StockBalance::with(['warehouse'])
                ->where('product_id', $productId)
                ->get()
        );
    }
}
