<?php

namespace App\Domain\Inventory\Filament\Widgets;

use App\Domain\Inventory\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends BaseWidget
{
    protected static ?string $heading = 'Produk Stok Rendah';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Product::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with(['unit'])
                    ->whereHas('currentStock', function (Builder $query): void {
                        $query->whereColumn('quantity', '<=', 'products.reorder_point');
                    })
                    ->where('is_stockable', true)
                    ->where('is_active', true)
            )
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('currentStock.quantity')
                    ->label('Stok Saat Ini')
                    ->placeholder('0'),
                TextColumn::make('reorder_point')
                    ->label('Titik Pesan Ulang'),
            ]);
    }
}
