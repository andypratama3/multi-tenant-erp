<?php

namespace App\Exports;

use App\Domain\Inventory\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport implements FromCollection, WithHeadings
{
    /** @return Collection<int, array{sku: string, name: string, category: string|null, unit: string, cost_price: string, selling_price: string, is_active: string}> */
    public function collection(): Collection
    {
        return Product::with(['category', 'unit'])
            ->get()
            ->map(fn (Product $p) => [
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category?->name,
                'unit' => $p->unit->symbol,
                'cost_price' => $p->cost_price,
                'selling_price' => $p->selling_price,
                'is_active' => $p->is_active ? 'Yes' : 'No',
            ]);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['SKU', 'Name', 'Category', 'Unit', 'Cost Price', 'Selling Price', 'Active'];
    }
}
