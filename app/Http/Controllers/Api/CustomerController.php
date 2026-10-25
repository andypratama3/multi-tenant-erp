<?php

namespace App\Http\Controllers\Api;

use App\Domain\Sales\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Customers
 *
 * APIs for managing customers.
 */
class CustomerController extends Controller
{
    public function index(): ResourceCollection
    {
        return JsonResource::collection(Customer::where('is_active', true)->paginate(20));
    }

    public function show(Customer $customer): JsonResource
    {
        return JsonResource::make($customer->load(['salesOrders']));
    }
}
