<?php

namespace App\Filament\Widgets;

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockBalance;
use App\Support\Money;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StockValuationWidget extends BaseWidget
{
    protected static ?string $heading = 'Nilai Persediaan';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Product::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockBalance::query()
                    ->with(['product.unit', 'warehouse'])
                    ->where('quantity', '>', 0)
                    ->orderByDesc('average_cost')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label('Gudang'),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                TextColumn::make('average_cost')
                    ->label('Avg Cost')
                    ->money('IDR'),
                TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('IDR')
                    ->state(fn (StockBalance $record): string => Money::mul(
                        $record->quantity,
                        $record->average_cost,
                        2,
                    )),
            ]);
    }
}
