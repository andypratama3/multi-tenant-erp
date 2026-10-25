<?php

namespace Database\Seeders;

use App\Domain\Inventory\Models\ProductCategory;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Tax\Models\TaxRate;
use Illuminate\Database\Seeder;

class OperationalDataSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Pieces', 'symbol' => 'pcs', 'is_base' => true],
            ['name' => 'Box', 'symbol' => 'box', 'is_base' => false],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'is_base' => false],
            ['name' => 'Liter', 'symbol' => 'l', 'is_base' => false],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['symbol' => $unit['symbol']], $unit);
        }

        ProductCategory::firstOrCreate(
            ['slug' => 'umum'],
            ['name' => 'Umum', 'description' => 'Kategori produk umum', 'is_active' => true],
        );

        Warehouse::firstOrCreate(
            ['code' => 'GDG-01'],
            [
                'name' => 'Gudang Utama',
                'address' => 'Samarinda, Kalimantan Timur',
                'is_active' => true,
            ],
        );

        TaxRate::firstOrCreate(
            ['code' => 'PPN-11'],
            [
                'name' => 'PPN 11%',
                'type' => 'ppn',
                'rate' => '11.00',
                'is_active' => true,
                'description' => 'Pajak Pertambahan Nilai tarif 11%',
            ],
        );
    }
}
