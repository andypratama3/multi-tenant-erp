<?php

namespace App\Domain\Sales\Filament\Resources;

use App\Domain\Inventory\Models\Product;
use App\Domain\Sales\Actions\ConfirmSalesOrderAction;
use App\Domain\Sales\Actions\ConfirmSalesOrderData;
use App\Domain\Sales\Filament\Resources\SalesOrderResource\Pages;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesOrder;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'Pesanan Penjualan';

    protected static ?string $pluralModelLabel = 'Pesanan Penjualan';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')
                ->label('Pelanggan')
                ->required()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => Customer::query()
                    ->where('is_active', true)
                    ->where(fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"))
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn (string $value): ?string => Customer::query()->whereKey($value)->value('name')),
            DatePicker::make('ordered_at')
                ->label('Tanggal Pesanan')
                ->required()
                ->default(now()->toDateString()),
            DatePicker::make('required_at')
                ->label('Diminta Pada'),
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
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === null) {
                                return;
                            }

                            $product = Product::query()->find($state);
                            $set('unit_price', (string) ($product->selling_price ?? '0'));
                        }),
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
                    TextInput::make('discount_rate')
                        ->label('Diskon')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step('0.01')
                        ->suffix('%')
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
                ->columns(5)
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
                    TextEntry::make('so_number')->label('No. SO'),
                    TextEntry::make('customer.name')->label('Pelanggan'),
                    TextEntry::make('ordered_at')->label('Tanggal Pesanan')->date('d M Y'),
                    TextEntry::make('required_at')->label('Diminta Pada')->date('d M Y')->placeholder('—'),
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
                            TextEntry::make('quantity_delivered')->label('Terkirim'),
                            TextEntry::make('quantity_invoiced')->label('Ditagih'),
                            TextEntry::make('unit_price')->label('Harga')->money('IDR'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                        ])
                        ->columns(6),
                ]),
            Section::make('Total')
                ->schema([
                    TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                    TextEntry::make('discount_amount')->label('Diskon')->money('IDR'),
                    TextEntry::make('tax_amount')->label('Pajak')->money('IDR'),
                    TextEntry::make('total')->label('Total')->money('IDR')->weight('bold'),
                ])
                ->columns(4),
            DocumentTimeline::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('so_number')->label('No. SO')->searchable()->sortable(),
            TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
            TextColumn::make('ordered_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'draft' => 'Draft',
                    'confirmed' => 'Dikonfirmasi',
                    'partial' => 'Sebagian',
                    'fulfilled' => 'Terpenuhi',
                    'cancelled' => 'Dibatalkan',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'confirmed' => 'info',
                    'partial' => 'warning',
                    'fulfilled' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('total')->label('Total')->money('IDR')->summarize(Sum::make()->label('Total')->money('IDR')),
        ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'confirmed' => 'Dikonfirmasi',
                        'partial' => 'Sebagian',
                        'fulfilled' => 'Terpenuhi',
                        'cancelled' => 'Dibatalkan',
                    ]),
                SelectFilter::make('customer_id')
                    ->label('Pelanggan')
                    ->relationship('customer', 'name')
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
                EditAction::make()->authorize('update')->visible(fn (SalesOrder $record): bool => $record->status === 'draft'),
                Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('confirm')
                    ->visible(fn (SalesOrder $record): bool => $record->status === 'draft')
                    ->action(function (SalesOrder $record): void {
                        try {
                            app(ConfirmSalesOrderAction::class)->execute(new ConfirmSalesOrderData(
                                salesOrderId: $record->id,
                                confirmedBy: auth()->id() !== null ? (string) auth()->id() : null,
                            ));

                            Notification::make()->title('Pesanan penjualan dikonfirmasi.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal mengonfirmasi pesanan.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('invoice')
                    ->label('Buat Faktur')
                    ->icon('heroicon-o-document-plus')
                    ->color('info')
                    ->authorize('create', SalesInvoice::class)
                    ->visible(fn (SalesOrder $record): bool => in_array($record->status, ['confirmed', 'partial'], true))
                    ->url(fn (SalesOrder $record): string => SalesInvoiceResource::getUrl('create', ['sales_order_id' => $record->id])),
                DeleteAction::make()->visible(fn (SalesOrder $record): bool => $record->status === 'draft'),
            ])
            ->emptyStateHeading('Belum ada pesanan penjualan')
            ->emptyStateDescription('Buat pesanan penjualan pertama untuk mulai memproses transaksi.')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer']);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof SalesOrder && $record->status === 'draft' && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof SalesOrder && $record->status === 'draft' && parent::canDelete($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'view' => Pages\ViewSalesOrder::route('/{record}'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
