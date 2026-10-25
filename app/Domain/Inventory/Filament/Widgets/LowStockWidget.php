<?php

namespace App\Domain\Inventory\Filament\Widgets;

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockBalance;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?string $heading = 'Produk Stok Rendah';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Product::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockBalance::query()
                    ->join('products', 'products.id', '=', 'stock_balances.product_id')
                    ->select('stock_balances.*')
                    ->with(['product.unit', 'warehouse'])
                    ->where('products.is_stockable', true)
                    ->where('products.is_active', true)
                    ->whereColumn('stock_balances.quantity', '<=', 'products.reorder_point')
                    ->orderBy('stock_balances.quantity')
            )
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->label('Stok Saat Ini'),
                TextColumn::make('product.reorder_point')
                    ->label('Titik Pesan Ulang'),
            ])
            ->emptyStateHeading('Stok aman')
            ->emptyStateDescription('Tidak ada produk yang menyentuh titik pesan ulang.')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
