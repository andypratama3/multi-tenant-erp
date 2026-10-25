<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateSalesOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'ordered_at' => ['required', 'date'],
            'required_at' => ['nullable', 'date', 'after_or_equal:ordered_at'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
