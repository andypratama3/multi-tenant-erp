<?php

namespace App\Http\Controllers\Api;

use App\Domain\Sales\Actions\ConfirmSalesOrderAction;
use App\Domain\Sales\Actions\ConfirmSalesOrderData;
use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderData;
use App\Domain\Sales\Models\SalesOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateSalesOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Sales Orders
 *
 * APIs for managing sales orders.
 */
class SalesOrderController extends Controller
{
    public function index(): ResourceCollection
    {
        $this->authorize('viewAny', SalesOrder::class);

        return JsonResource::collection(
            SalesOrder::with(['customer', 'items.product'])->orderByDesc('created_at')->paginate(20)
        );
    }

    public function store(CreateSalesOrderRequest $request, CreateSalesOrderAction $action): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);

        $so = $action->execute(new CreateSalesOrderData(
            customerId: $request->validated('customer_id'),
            orderedAt: $request->validated('ordered_at'),
            requiredAt: $request->validated('required_at'),
            notes: $request->validated('notes'),
            createdBy: $request->user()?->id,
            items: $request->validated('items'),
        ));

        return response()->json([
            'message' => 'Sales order created.',
            'data' => $so->load(['customer', 'items.product']),
        ], 201);
    }

    public function show(SalesOrder $salesOrder): JsonResource
    {
        $this->authorize('view', $salesOrder);

        return JsonResource::make($salesOrder->load(['customer', 'items.product', 'invoices']));
    }

    /**
     * Confirm a sales order.
     */
    public function confirm(SalesOrder $salesOrder, ConfirmSalesOrderAction $action): JsonResponse
    {
        $this->authorize('confirm', $salesOrder);

        $confirmed = $action->execute(new ConfirmSalesOrderData(
            salesOrderId: $salesOrder->id,
            confirmedBy: request()->user()?->id,
        ));

        return response()->json([
            'message' => 'Sales order dikonfirmasi.',
            'data' => $confirmed,
        ]);
    }
}
