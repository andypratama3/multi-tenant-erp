<?php

namespace App\Domain\Purchasing\Filament\Resources;

use App\Domain\Inventory\Models\Product;
use App\Domain\Purchasing\Actions\CancelPurchaseOrderAction;
use App\Domain\Purchasing\Actions\SendPurchaseOrderAction;
use App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\Supplier;
use App\Filament\Support\DocumentTimeline;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';

    protected static ?string $modelLabel = 'Pesanan Pembelian';

    protected static ?string $pluralModelLabel = 'Pesanan Pembelian';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('supplier_id')
                ->label('Supplier')
                ->required()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Supplier::query()
                    ->where('is_active', true)
                    ->where(fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"))
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn (string $value): ?string => Supplier::query()->whereKey($value)->value('name')),
            DatePicker::make('ordered_at')
                ->label('Tanggal Pesanan')
                ->required()
                ->default(now()->toDateString()),
            DatePicker::make('expected_at')
                ->label('Perkiraan Tiba'),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull(),
            Repeater::make('items')
                ->label('Item Pesanan')
                ->schema([
                    Select::make('product_id')
                        ->label('Produk')
                        ->options(fn (): array => Product::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('quantity_ordered')
                        ->label('Qty')
                        ->numeric()
                        ->required()
                        ->minValue(0.001)
                        ->step('0.001')
                        ->default(1),
                    TextInput::make('unit_price')
                        ->label('Harga Satuan')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->step('0.0001')
                        ->prefix('Rp')
                        ->default(0),
                    TextInput::make('tax_rate')
                        ->label('Pajak')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step('0.01')
                        ->suffix('%')
                        ->default(0),
                ])
                ->columns(4)
                ->minItems(1)
                ->defaultItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pesanan')
                ->schema([
                    TextEntry::make('po_number')->label('No. PO'),
                    TextEntry::make('supplier.name')->label('Supplier'),
                    TextEntry::make('ordered_at')->label('Tanggal Pesanan')->date('d M Y'),
                    TextEntry::make('expected_at')->label('Perkiraan Tiba')->date('d M Y')->placeholder('—'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Item Pesanan')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('quantity_ordered')->label('Qty'),
                            TextEntry::make('quantity_received')->label('Diterima'),
                            TextEntry::make('unit_price')->label('Harga')->money('IDR'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                        ])
                        ->columns(5),
                ]),
            Section::make('Total')
                ->schema([
                    TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                    TextEntry::make('tax_amount')->label('Pajak')->money('IDR'),
                    TextEntry::make('total')->label('Total')->money('IDR')->weight('bold'),
                ])
                ->columns(3),
            DocumentTimeline::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('po_number')->label('No. PO')->searchable()->sortable(),
            TextColumn::make('supplier.name')->label('Supplier')->searchable(),
            TextColumn::make('ordered_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'draft' => 'Draft',
                    'sent' => 'Terkirim',
                    'partial' => 'Sebagian',
                    'received' => 'Diterima',
                    'cancelled' => 'Dibatalkan',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'sent' => 'info',
                    'partial' => 'warning',
                    'received' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('total')->label('Total')->money('IDR'),
        ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'partial' => 'Sebagian',
                        'received' => 'Diterima',
                        'cancelled' => 'Dibatalkan',
                    ]),
                SelectFilter::make('supplier_id')
                    ->label('Pemasok')
                    ->relationship('supplier', 'name')
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
                            $query->whereDate('ordered_at', '>=', $data['from']);
                        }

                        if (is_string($data['until'] ?? null)) {
                            $query->whereDate('ordered_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->authorize('update')->visible(fn (PurchaseOrder $record): bool => $record->status === 'draft'),
                Action::make('send')
                    ->label('Kirim')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->authorize('send')
                    ->visible(fn (PurchaseOrder $record): bool => $record->status === 'draft')
                    ->action(function (PurchaseOrder $record): void {
                        try {
                            app(SendPurchaseOrderAction::class)->execute($record->id);
                            Notification::make()->title('Pesanan pembelian berhasil dikirim.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal mengirim pesanan.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('receive')
                    ->label('Buat Penerimaan')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->authorize('create', GoodsReceiptNote::class)
                    ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, ['sent', 'partial'], true))
                    ->url(fn (PurchaseOrder $record): string => GoodsReceiptNoteResource::getUrl('create', ['purchase_order_id' => $record->id])),
                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('cancel')
                    ->visible(fn (PurchaseOrder $record): bool => in_array($record->status, ['draft', 'sent'], true))
                    ->action(function (PurchaseOrder $record): void {
                        try {
                            app(CancelPurchaseOrderAction::class)->execute($record->id);
                            Notification::make()->title('Pesanan pembelian dibatalkan.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal membatalkan pesanan.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                DeleteAction::make()->visible(fn (PurchaseOrder $record): bool => $record->status === 'draft'),
            ])
            ->emptyStateHeading('Belum ada pesanan pembelian')
            ->emptyStateDescription('Buat pesanan pembelian untuk mulai mengelola pengadaan barang.')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['supplier']);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof PurchaseOrder && $record->status === 'draft' && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof PurchaseOrder && $record->status === 'draft' && parent::canDelete($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
