<?php

namespace App\Domain\Sales\Filament\Resources;

use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Filament\Resources\SalesReturnResource\Pages;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesReturn;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'Retur Penjualan';

    protected static ?string $pluralModelLabel = 'Retur Penjualan';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Retur Penjualan')
                ->schema([
                    Select::make('sales_invoice_id')
                        ->label('Faktur')
                        ->required()
                        ->live()
                        ->searchable()
                        ->options(fn (): array => SalesInvoice::query()
                            ->with('customer')
                            ->whereIn('status', ['issued', 'partial', 'paid'])
                            ->orderByDesc('issued_at')
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(function (SalesInvoice $invoice): array {
                                $customerName = $invoice->customer !== null ? $invoice->customer->name : '-';

                                return [$invoice->id => $invoice->invoice_number.' — '.$customerName];
                            })
                            ->all()),
                    Select::make('warehouse_id')
                        ->label('Gudang Penerima')
                        ->required()
                        ->searchable()
                        ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn (): ?string => Warehouse::query()->orderBy('name')->value('id')),
                    DatePicker::make('returned_at')->label('Tanggal Retur')->required()->default(now()->toDateString()),
                    Select::make('refund_method')
                        ->label('Metode Pengembalian Dana')
                        ->options(['cash' => 'Tunai', 'bank' => 'Transfer Bank'])
                        ->default('cash')
                        ->helperText('Dipakai hanya jika nilai retur melebihi sisa piutang (invoice sudah dibayar).'),
                    Textarea::make('reason')->label('Alasan Retur')->required()->rows(2)->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Item Diretur')
                ->schema([
                    Repeater::make('items')
                        ->label('Item Retur')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('sales_invoice_item_id')
                                ->label('Item Invoice')
                                ->required()
                                ->searchable()
                                ->options(function (Get $get): array {
                                    $invoiceId = $get('../../sales_invoice_id');

                                    if (! is_string($invoiceId) || $invoiceId === '') {
                                        return [];
                                    }

                                    $invoice = SalesInvoice::query()
                                        ->with('items.product')
                                        ->find($invoiceId);

                                    if ($invoice === null) {
                                        return [];
                                    }

                                    return $invoice->items
                                        ->mapWithKeys(function ($item): array {
                                            $productName = $item->product !== null ? $item->product->name : '-';

                                            return [$item->id => $productName.' — '.$item->quantity.' @ Rp '.(float) $item->unit_price];
                                        })
                                        ->all();
                                }),
                            TextInput::make('quantity')
                                ->label('Qty Retur')
                                ->numeric()
                                ->required()
                                ->minValue(0.001)
                                ->default(1),
                            Toggle::make('restock')
                                ->label('Kembalikan ke stok')
                                ->default(true),
                        ])
                        ->columns(3)
                        ->minItems(1)
                        ->addActionLabel('Tambah Item'),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Retur')
                ->schema([
                    TextEntry::make('return_number')->label('No. Retur'),
                    TextEntry::make('invoice.invoice_number')->label('Faktur'),
                    TextEntry::make('customer.name')->label('Pelanggan'),
                    TextEntry::make('warehouse.name')->label('Gudang'),
                    TextEntry::make('returned_at')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('reason')->label('Alasan')->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Rincian Retur')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('quantity')->label('Qty'),
                            TextEntry::make('unit_price')->label('Harga')->money('IDR'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                            TextEntry::make('tax_amount')->label('PPN')->money('IDR'),
                            TextEntry::make('total')->label('Total')->money('IDR'),
                            TextEntry::make('restock')->label('Kembalikan ke Stok')->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak'),
                        ])
                        ->columns(4),
                ]),
            Section::make('Total')
                ->schema([
                    TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                    TextEntry::make('tax_amount')->label('PPN')->money('IDR'),
                    TextEntry::make('refund_amount')->label('Dana Dikembalikan')->money('IDR'),
                    TextEntry::make('refund_method')->label('Metode Refund')->formatStateUsing(fn (string $state): string => $state === 'cash' ? 'Tunai' : 'Transfer Bank'),
                    TextEntry::make('total')->label('Total Retur')->money('IDR')->weight('bold'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('return_number')->label('No. Retur')->searchable()->sortable(),
            TextColumn::make('invoice.invoice_number')->label('Faktur')->searchable(),
            TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
            TextColumn::make('warehouse.name')->label('Gudang'),
            TextColumn::make('returned_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('total')->label('Total')->money('IDR'),
        ])
            ->filters([
                SelectFilter::make('warehouse_id')->label('Gudang')->relationship('warehouse', 'name')->searchable()->preload(),
            ])
            ->actions([ViewAction::make()])
            ->emptyStateHeading('Belum ada retur penjualan')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['invoice', 'customer', 'warehouse', 'items.product']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesReturns::route('/'),
            'create' => Pages\CreateSalesReturn::route('/create'),
            'view' => Pages\ViewSalesReturn::route('/{record}'),
        ];
    }
}
