<?php

namespace App\Domain\Inventory\Filament\Widgets;

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockLot;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiryAlertWidget extends BaseWidget
{
    protected static ?string $heading = 'Batch Mendekati Kedaluwarsa';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Product::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockLot::query()
                    ->with(['product.unit', 'warehouse'])
                    ->expiringWithin(30)
                    ->orderBy('expiry_date')
            )
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('lot_number')
                    ->label('Batch')
                    ->placeholder('—'),
                TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->placeholder('—'),
                TextColumn::make('expiry_date')
                    ->label('Kedaluwarsa')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('days_until_expiry')
                    ->label('Sisa Hari')
                    ->state(fn (StockLot $record): ?int => $record->days_until_expiry)
                    ->formatStateUsing(fn (?int $state): string => match (true) {
                        $state === null => '—',
                        $state < 0 => abs($state).' hari lalu',
                        default => $state.' hari',
                    })
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state < 0 => 'danger',
                        $state <= 7 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('quantity_remaining')
                    ->label('Sisa Stok')
                    ->numeric(decimalPlaces: 3),
                TextColumn::make('expiry_status')
                    ->label('Status')
                    ->state(fn (StockLot $record): string => $record->expiry_status)
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        StockLot::STATUS_EXPIRED => 'Kedaluwarsa',
                        StockLot::STATUS_EXPIRING => 'Segera',
                        default => 'Aman',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        StockLot::STATUS_EXPIRED => 'danger',
                        StockLot::STATUS_EXPIRING => 'warning',
                        default => 'success',
                    }),
            ])
            ->emptyStateHeading('Tidak ada batch mendekati kedaluwarsa')
            ->emptyStateDescription('Batch dengan masa simpan 30 hari atau kurang akan muncul di sini.')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
