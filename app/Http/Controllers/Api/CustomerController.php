<?php

namespace App\Http\Controllers\Api;

use App\Domain\Sales\Models\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Customers
 *
 * APIs for managing customers.
 */
class CustomerController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query();
        $search = $request->string('search')->toString();

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
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

    public function store(CustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return response()->json([
            'message' => 'Pelanggan berhasil dibuat.',
            'data' => $customer,
        ], 201);
    }

    public function show(Customer $customer): JsonResource
    {
        $this->authorize('view', $customer);

        return JsonResource::make($customer->load(['salesOrders']));
    }

    public function update(CustomerRequest $request, Customer $customer): JsonResource
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return JsonResource::make($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json(['message' => 'Pelanggan berhasil dihapus.']);
    }
}
