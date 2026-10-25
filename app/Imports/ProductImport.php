<?php

namespace App\Imports;

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\ProductCategory;
use App\Domain\Inventory\Models\Unit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): ?Product
    {
        $unit = Unit::where('symbol', $row['unit'])->first();
        if (! $unit) {
            return null;
        }

        $category = isset($row['category'])
            ? ProductCategory::where('slug', $row['category'])->first()
            : null;

        return new Product([
            'sku' => $row['sku'],
            'name' => $row['name'],
            'unit_id' => $unit->id,
            'product_category_id' => $category?->id,
            'cost_price' => $row['cost_price'] ?? 0,
            'selling_price' => $row['selling_price'] ?? 0,
            'is_active' => true,
            'is_stockable' => true,
        ]);
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'sku' => 'required|string',
            'name' => 'required|string',
            'unit' => 'required|string',
        ];
    }
}
