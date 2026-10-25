<?php

namespace App\Domain\Inventory\Filament\Resources;

use App\Domain\Inventory\Filament\Resources\StockLotResource\Pages;
use App\Domain\Inventory\Models\StockLot;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Filament\Resources\Resource<StockLot>
 */
class StockLotResource extends Resource
{
    protected static ?string $model = StockLot::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventaris';

    protected static ?string $modelLabel = 'Batch Stok';

    protected static ?string $pluralModelLabel = 'Batch & Kedaluwarsa';

    protected static ?string $navigationLabel = 'Batch & Kedaluwarsa';

    protected static ?int $navigationSort = 45;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Batch')
                ->schema([
                    TextEntry::make('product.sku')->label('SKU'),
                    TextEntry::make('product.name')->label('Produk'),
                    TextEntry::make('warehouse.name')->label('Gudang')->placeholder('—'),
                    TextEntry::make('lot_number')->label('No. Batch')->placeholder('—'),
                    TextEntry::make('received_at')->label('Tanggal Terima')->date('d M Y'),
                    TextEntry::make('expiry_date')->label('Kedaluwarsa')->date('d M Y')->placeholder('Tidak ada'),
                    TextEntry::make('quantity_received')->label('Qty Diterima'),
                    TextEntry::make('quantity_remaining')->label('Qty Tersisa'),
                    TextEntry::make('unit_cost')->label('Harga Pokok')->money('IDR'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lot_number')
                    ->label('No. Batch')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('warehouse.name')
                    ->label('Gudang')
                    ->placeholder('—'),
                TextColumn::make('received_at')
                    ->label('Diterima')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->label('Kedaluwarsa')
                    ->date('d M Y')
                    ->placeholder('—')
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
                TextColumn::make('expiry_status')
                    ->label('Status')
                    ->state(fn (StockLot $record): string => $record->expiry_status)
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        StockLot::STATUS_EXPIRED => 'Kedaluwarsa',
                        StockLot::STATUS_EXPIRING => 'Segera Kedaluwarsa',
                        default => 'Aman',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        StockLot::STATUS_EXPIRED => 'danger',
                        StockLot::STATUS_EXPIRING => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('quantity_remaining')
                    ->label('Sisa Stok')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('expired')
                    ->label('Sudah kedaluwarsa')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '<', today()->toDateString())),
                Filter::make('expiring')
                    ->label('Kedaluwarsa ≤ 7 hari')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>=', today()->toDateString())
                        ->whereDate('expiry_date', '<=', today()->addDays(7)->toDateString())),
                Filter::make('remaining')
                    ->label('Masih ada stok')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereRaw('quantity_remaining > 0')),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('Belum ada batch stok')
            ->emptyStateDescription('Batch akan muncul otomatis saat penerimaan barang untuk produk yang dilacak kedaluwarsanya.')
            ->emptyStateIcon('heroicon-o-clock')
            ->defaultSort('expiry_date');
    }

    /** @return Builder<StockLot> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'warehouse']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockLots::route('/'),
            'view' => Pages\ViewStockLot::route('/{record}'),
        ];
    }
}
