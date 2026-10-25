<?php

namespace App\Http\Requests\Api;

use App\Domain\Inventory\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : null;

        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'product_category_id' => ['nullable', 'uuid', 'exists:product_categories,id'],
            'unit_id' => ['required', 'uuid', 'exists:units,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'is_stockable' => ['sometimes', 'boolean'],
            'min_stock' => ['sometimes', 'numeric', 'min:0'],
            'reorder_point' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
