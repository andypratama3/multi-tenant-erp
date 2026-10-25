<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IssueInvoiceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sales_order_id' => ['nullable', 'uuid', 'exists:sales_orders,id'],
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
