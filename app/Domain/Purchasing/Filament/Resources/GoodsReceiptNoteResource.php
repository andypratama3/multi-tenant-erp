<?php

namespace App\Domain\Purchasing\Filament\Resources;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Purchasing\Actions\PostGoodsReceiptNoteAction;
use App\Domain\Purchasing\Actions\PostGrnData;
use App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource\Pages;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderItem;
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

class GoodsReceiptNoteResource extends Resource
{
    protected static ?string $model = GoodsReceiptNote::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';

    protected static ?string $modelLabel = 'Penerimaan Barang';

    protected static ?string $pluralModelLabel = 'Penerimaan Barang';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('purchase_order_id')
                ->label('Pesanan Pembelian')
                ->required()
                ->live()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => PurchaseOrder::query()
                    ->whereIn('status', ['sent', 'partial'])
                    ->where('po_number', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('po_number', 'id')
                    ->all())
                ->getOptionLabelUsing(fn (string $value): ?string => PurchaseOrder::query()->whereKey($value)->value('po_number')),
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
            DatePicker::make('received_at')
                ->label('Tanggal Terima')
                ->required()
                ->default(now()->toDateString()),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull(),
            Repeater::make('items')
                ->label('Item Diterima')
                ->schema([
                    Select::make('purchase_order_item_id')
                        ->label('Item Pesanan')
                        ->required()
                        ->live()
                        ->options(function (Get $get): array {
                            $purchaseOrderId = $get('../../purchase_order_id');

                            if (! is_string($purchaseOrderId) || $purchaseOrderId === '') {
                                return [];
                            }

                            return PurchaseOrderItem::query()
                                ->with('product')
                                ->where('purchase_order_id', $purchaseOrderId)
                                ->get()
                                ->filter(fn (PurchaseOrderItem $item): bool => bccomp(
                                    Money::decimal($item->quantity_ordered, 3),
                                    Money::decimal($item->quantity_received, 3),
                                    3,
                                ) === 1)
                                ->mapWithKeys(fn (PurchaseOrderItem $item): array => [
                                    $item->id => ($item->product->name ?? $item->product_id)
                                        .' — sisa '.Money::decimal(bcsub(
                                            Money::decimal($item->quantity_ordered, 3),
                                            Money::decimal($item->quantity_received, 3),
                                            3,
                                        ), 3),
                                ])
                                ->all();
                        })
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === null) {
                                return;
                            }

                            $item = PurchaseOrderItem::query()->find($state);

                            if ($item === null) {
                                return;
                            }

                            $remaining = bcsub(
                                Money::decimal($item->quantity_ordered, 3),
                                Money::decimal($item->quantity_received, 3),
                                3,
                            );

                            $set('quantity_received', $remaining);
                            $set('unit_cost', (string) $item->unit_price);
                        }),
                    TextInput::make('quantity_received')
                        ->label('Qty Diterima')
                        ->numeric()
                        ->required()
                        ->minValue(0.001)
                        ->step('0.001'),
                    TextInput::make('unit_cost')
                        ->label('Harga Satuan')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->step('0.0001')
                        ->prefix('Rp'),
                ])
                ->columns(3)
                ->minItems(1)
                ->defaultItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Penerimaan')
                ->schema([
                    TextEntry::make('grn_number')->label('No. Penerimaan'),
                    TextEntry::make('purchaseOrder.po_number')->label('No. PO'),
                    TextEntry::make('warehouse.name')->label('Gudang')->placeholder('—'),
                    TextEntry::make('received_at')->label('Tanggal Terima')->date('d M Y'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Item Diterima')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('quantity_received')->label('Qty Diterima'),
                            TextEntry::make('unit_cost')->label('Harga Satuan')->money('IDR'),
                        ])
                        ->columns(3),
                ]),
            DocumentTimeline::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('grn_number')->label('No. Penerimaan')->searchable()->sortable(),
            TextColumn::make('purchaseOrder.po_number')->label('No. PO'),
            TextColumn::make('warehouse.name')->label('Gudang')->placeholder('—'),
            TextColumn::make('received_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (string $state): string => $state === 'posted' ? 'Diposting' : 'Draft')
                ->color(fn (string $state): string => $state === 'posted' ? 'success' : 'gray'),
        ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Diposting',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'name')
                    ->searchable(),
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (is_string($data['from'] ?? null)) {
                            $query->whereDate('received_at', '>=', $data['from']);
                        }

                        if (is_string($data['until'] ?? null)) {
                            $query->whereDate('received_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('createInvoice')
                    ->label('Buat Faktur')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->authorize('create', PurchaseInvoice::class)
                    ->visible(fn (GoodsReceiptNote $record): bool => $record->status === 'posted' && $record->purchaseInvoice === null)
                    ->url(fn (GoodsReceiptNote $record): string => PurchaseInvoiceResource::getUrl('create', ['grn_id' => $record->id])),
                Action::make('post')
                    ->label('Posting')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Posting akan menambah stok dan membuat jurnal persediaan. Tindakan ini tidak dapat dibatalkan.')
                    ->authorize('post')
                    ->visible(fn (GoodsReceiptNote $record): bool => $record->status === 'draft')
                    ->action(function (GoodsReceiptNote $record): void {
                        try {
                            app(PostGoodsReceiptNoteAction::class)->execute(new PostGrnData(
                                grnId: $record->id,
                                postJournal: true,
                            ));

                            Notification::make()->title('Penerimaan barang berhasil diposting.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal memposting penerimaan.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                DeleteAction::make()->visible(fn (GoodsReceiptNote $record): bool => $record->status === 'draft'),
            ])
            ->emptyStateHeading('Belum ada penerimaan barang')
            ->emptyStateDescription('Buat penerimaan dari pesanan pembelian yang sudah dikirim.')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['purchaseOrder', 'warehouse']);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof GoodsReceiptNote && $record->status === 'draft' && parent::canDelete($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceiptNotes::route('/'),
            'create' => Pages\CreateGoodsReceiptNote::route('/create'),
            'view' => Pages\ViewGoodsReceiptNote::route('/{record}'),
        ];
    }
}
