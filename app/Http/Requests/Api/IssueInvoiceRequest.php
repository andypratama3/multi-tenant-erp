<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IssueInvoiceRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'sales_order_id' => 'nullable|uuid|exists:sales_orders,id',
            'customer_id' => 'required|uuid|exists:customers,id',
            'issued_at' => 'required|date',
            'due_at' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|string',
            'items.*.unit_price' => 'required|string',
            'items.*.discount_rate' => 'nullable|string',
            'items.*.tax_rate' => 'nullable|string',
        ];
    }
}
