<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateSalesOrderRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|uuid|exists:customers,id',
            'ordered_at' => 'required|date',
            'required_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity_ordered' => 'required|string',
            'items.*.unit_price' => 'required|string',
            'items.*.discount_rate' => 'nullable|string',
            'items.*.tax_rate' => 'nullable|string',
        ];
    }
}
