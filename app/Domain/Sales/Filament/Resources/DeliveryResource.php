<?php

namespace App\Domain\Sales\Filament\Resources;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Actions\PostDeliveryAction;
use App\Domain\Sales\Actions\PostDeliveryData;
use App\Domain\Sales\Filament\Resources\DeliveryResource\Pages;
use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesOrderItem;
use App\Filament\Support\DocumentTimeline;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'Pengiriman';

    protected static ?string $pluralModelLabel = 'Pengiriman';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_order_id')
                ->label('Pesanan Penjualan')
                ->required()
                ->live()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => SalesOrder::query()
                    ->whereIn('status', ['confirmed', 'partial'])
                    ->where('so_number', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('so_number', 'id')
                    ->all())
                ->getOptionLabelUsing(fn (string $value): ?string => SalesOrder::query()->whereKey($value)->value('so_number')),
            Select::make('warehouse_id')
                ->label('Gudang')
                ->required()
                ->searchable()
                ->options(fn (): array => Warehouse::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all()),
            DatePicker::make('shipped_at')
                ->label('Tanggal Kirim')
                ->default(now()->toDateString()),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull(),
            Repeater::make('items')
                ->label('Item Dikirim')
                ->schema([
                    Select::make('sales_order_item_id')
                        ->label('Item Pesanan')
                        ->required()
                        ->live()
                        ->options(function (Get $get): array {
                            $salesOrderId = $get('../../sales_order_id');

                            if (! is_string($salesOrderId) || $salesOrderId === '') {
                                return [];
                            }

                            return SalesOrderItem::query()
                                ->with('product')
                                ->where('sales_order_id', $salesOrderId)
                                ->get()
                                ->filter(fn (SalesOrderItem $item): bool => bccomp(
                                    Money::decimal($item->quantity_ordered, 3),
                                    Money::decimal($item->quantity_delivered, 3),
                                    3,
                                ) === 1)
                                ->mapWithKeys(fn (SalesOrderItem $item): array => [
                                    $item->id => ($item->product->name ?? $item->product_id)
                                        .' — sisa '.Money::decimal(bcsub(
                                            Money::decimal($item->quantity_ordered, 3),
                                            Money::decimal($item->quantity_delivered, 3),
                                            3,
                                        ), 3),
                                ])
                                ->all();
                        })
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === null) {
                                return;
                            }

                            $item = SalesOrderItem::query()->find($state);

                            if ($item === null) {
                                return;
                            }

                            $set('quantity_delivered', bcsub(
                                Money::decimal($item->quantity_ordered, 3),
                                Money::decimal($item->quantity_delivered, 3),
                                3,
                            ));
                        }),
                    TextInput::make('quantity_delivered')
                        ->label('Qty Kirim')
                        ->numeric()
                        ->required()
                        ->minValue(0.001)
                        ->step('0.001'),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pengiriman')
                ->schema([
                    TextEntry::make('delivery_number')->label('No. Pengiriman'),
                    TextEntry::make('salesOrder.so_number')->label('No. SO'),
                    TextEntry::make('salesOrder.customer.name')->label('Pelanggan'),
                    TextEntry::make('warehouse.name')->label('Gudang')->placeholder('—'),
                    TextEntry::make('shipped_at')->label('Tanggal Kirim')->date('d M Y')->placeholder('—'),
                    TextEntry::make('delivered_at')->label('Tanggal Terima')->date('d M Y')->placeholder('—'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Item Dikirim')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('product.sku')->label('SKU'),
                            TextEntry::make('quantity_delivered')->label('Qty Dikirim'),
                        ])
                        ->columns(3),
                ]),
            DocumentTimeline::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('delivery_number')->label('No. Pengiriman')->searchable()->sortable(),
            TextColumn::make('salesOrder.so_number')->label('No. SO'),
            TextColumn::make('warehouse.name')->label('Gudang')->placeholder('—'),
            TextColumn::make('shipped_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'draft' => 'Draft',
                    'shipped' => 'Dikirim',
                    'delivered' => 'Diterima',
                    'returned' => 'Dikembalikan',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'shipped' => 'info',
                    'delivered' => 'success',
                    'returned' => 'warning',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'shipped' => 'Dikirim',
                        'delivered' => 'Diterima',
                        'returned' => 'Dikembalikan',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (is_string($data['from'] ?? null)) {
                            $query->whereDate('shipped_at', '>=', $data['from']);
                        }

                        if (is_string($data['until'] ?? null)) {
                            $query->whereDate('shipped_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('post')
                    ->label('Posting')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Posting akan mengurangi stok dan membuat jurnal HPP. Tindakan ini tidak dapat dibatalkan.')
                    ->authorize('post')
                    ->visible(fn (Delivery $record): bool => $record->status === 'draft')
                    ->action(function (Delivery $record): void {
                        try {
                            app(PostDeliveryAction::class)->execute(new PostDeliveryData(
                                deliveryId: $record->id,
                                postJournal: true,
                            ));

                            Notification::make()->title('Pengiriman berhasil diposting.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal memposting pengiriman.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                DeleteAction::make()->visible(fn (Delivery $record): bool => $record->status === 'draft'),
            ])
            ->emptyStateHeading('Belum ada pengiriman')
            ->emptyStateDescription('Buat pengiriman dari pesanan penjualan yang sudah dikonfirmasi.')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['salesOrder', 'warehouse']);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Delivery && $record->status === 'draft' && parent::canDelete($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'view' => Pages\ViewDelivery::route('/{record}'),
        ];
    }
}
