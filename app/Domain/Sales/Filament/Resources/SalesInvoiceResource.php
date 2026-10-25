<?php

namespace App\Domain\Sales\Filament\Resources;

use App\Domain\Inventory\Models\Product;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Actions\RecordPaymentData;
use App\Domain\Sales\Actions\VoidSalesInvoiceAction;
use App\Domain\Sales\Actions\VoidSalesInvoiceData;
use App\Domain\Sales\Filament\Resources\SalesInvoiceResource\Pages;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesReturn;
use App\Domain\Sales\Pdfs\SalesInvoicePdf;
use App\Filament\Support\DocumentTimeline;
use App\Support\Money;
use App\Support\Taxes\TaxDefaults;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesInvoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'Faktur Penjualan';

    protected static ?string $pluralModelLabel = 'Faktur Penjualan';

    protected static ?int $navigationSort = 30;

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
                ->getOptionLabelUsing(fn (string $value): ?string => Customer::query()->whereKey($value)->value('name'))
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if ($state === null || $state === '') {
                        return;
                    }

                    $customer = Customer::query()->find($state);

                    if ($customer !== null) {
                        $set('due_at', now()->addDays($customer->payment_terms_days)->toDateString());
                    }
                }),
            Select::make('sales_order_id')
                ->label('Pesanan Penjualan')
                ->searchable()
                ->nullable()
                ->live()
                ->helperText('Pilih pesanan untuk mengisi pelanggan dan item secara otomatis.')
                ->options(fn (): array => SalesOrder::query()
                    ->whereIn('status', ['confirmed', 'partial', 'fulfilled'])
                    ->latest()
                    ->limit(100)
                    ->pluck('so_number', 'id')
                    ->all())
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if ($state === null || $state === '') {
                        return;
                    }

                    $salesOrder = SalesOrder::query()->with(['customer', 'items'])->find($state);

                    if ($salesOrder === null) {
                        return;
                    }

                    $paymentTerms = $salesOrder->customer === null ? 30 : $salesOrder->customer->payment_terms_days;

                    $set('customer_id', $salesOrder->customer_id);
                    $set('due_at', now()->addDays($paymentTerms)->toDateString());
                    $set('items', $salesOrder->items
                        ->filter(fn ($item): bool => bccomp(
                            Money::decimal($item->quantity_ordered, 3),
                            Money::decimal($item->quantity_invoiced, 3),
                            3,
                        ) === 1)
                        ->map(fn ($item): array => [
                            'sales_order_item_id' => $item->id,
                            'product_id' => $item->product_id,
                            'quantity' => Money::decimal(bcsub(
                                Money::decimal($item->quantity_ordered, 3),
                                Money::decimal($item->quantity_invoiced, 3),
                                3,
                            ), 3),
                            'unit_price' => (string) $item->unit_price,
                            'discount_rate' => (string) $item->discount_rate,
                            'tax_rate' => (string) $item->tax_rate,
                        ])
                        ->values()
                        ->all());
                }),
            DatePicker::make('issued_at')
                ->label('Tanggal Faktur')
                ->required()
                ->default(now()->toDateString()),
            DatePicker::make('due_at')
                ->label('Jatuh Tempo')
                ->required()
                ->default(now()->addDays(30)->toDateString()),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull(),
            Repeater::make('items')
                ->label('Item Faktur')
                ->schema([
                    Hidden::make('sales_order_item_id'),
                    Select::make('product_id')
                        ->label('Produk')
                        ->getSearchResultsUsing(fn (string $search): array => Product::query()
                            ->where('is_active', true)
                            ->where(fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%"))
                            ->orderBy('name')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Product $product): array => [$product->id => $product->sku.' — '.$product->name])
                            ->all())
                        ->getOptionLabelUsing(fn (string $value): ?string => Product::query()->whereKey($value)->value('name'))
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
                    TextInput::make('quantity')
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
                        ->default(fn (): string => TaxDefaults::ppnRate()),
                ])
                ->columns(6)
                ->minItems(1)
                ->defaultItems(1)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Faktur')
                ->schema([
                    TextEntry::make('invoice_number')->label('No. Faktur'),
                    TextEntry::make('customer.name')->label('Pelanggan'),
                    TextEntry::make('salesOrder.so_number')->label('No. SO')->placeholder('—'),
                    TextEntry::make('issued_at')->label('Tanggal Faktur')->date('d M Y'),
                    TextEntry::make('due_at')->label('Jatuh Tempo')->date('d M Y'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Item Faktur')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('quantity')->label('Qty'),
                            TextEntry::make('unit_price')->label('Harga')->money('IDR'),
                            TextEntry::make('discount_rate')->label('Diskon (%)'),
                            TextEntry::make('tax_rate')->label('Pajak (%)'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                        ])
                        ->columns(6),
                ]),
            Section::make('Total')
                ->schema([
                    TextEntry::make('subtotal')->label('Subtotal (setelah diskon)')->money('IDR'),
                    TextEntry::make('discount_amount')->label('Total Diskon')->money('IDR'),
                    TextEntry::make('tax_amount')->label('Pajak')->money('IDR'),
                    TextEntry::make('total')->label('Total')->money('IDR')->weight('bold'),
                    TextEntry::make('paid_amount')->label('Dibayar')->money('IDR'),
                    TextEntry::make('returned_amount')->label('Diretur')->money('IDR'),
                    TextEntry::make('outstanding')
                        ->label('Sisa Tagihan')
                        ->state(fn (SalesInvoice $record): string => Money::sub(Money::sub($record->total, $record->returned_amount), $record->paid_amount))
                        ->money('IDR'),
                ])
                ->columns(6),
            Section::make('Riwayat Pembayaran')
                ->schema([
                    RepeatableEntry::make('payments')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('payment_number')->label('No. Pembayaran'),
                            TextEntry::make('paid_at')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('payment_method')->label('Metode'),
                            TextEntry::make('amount')->label('Nominal')->money('IDR'),
                            TextEntry::make('reference')->label('Referensi')->placeholder('—'),
                        ])
                        ->columns(5),
                ]),
            DocumentTimeline::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('invoice_number')->label('No. Faktur')->searchable()->sortable(),
            TextColumn::make('customer.name')->label('Pelanggan')->searchable(),
            TextColumn::make('issued_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('due_at')->label('Jatuh Tempo')->date('d M Y')->sortable(),
            TextColumn::make('total')->label('Total')->money('IDR')->summarize(Sum::make()->label('Total')->money('IDR')),
            TextColumn::make('paid_amount')->label('Dibayar')->money('IDR')->summarize(Sum::make()->label('Dibayar')->money('IDR')),
            TextColumn::make('status')->label('Status')->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'draft' => 'Draft',
                    'issued' => 'Terbit',
                    'partial' => 'Sebagian',
                    'paid' => 'Lunas',
                    'cancelled' => 'Dibatalkan',
                    default => $state,
                })
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'issued' => 'info',
                    'partial' => 'warning',
                    'paid' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Terbit',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
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
                            $query->whereDate('issued_at', '>=', $data['from']);
                        }

                        if (is_string($data['until'] ?? null)) {
                            $query->whereDate('issued_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('createReturn')
                    ->label('Retur')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->authorize('create', SalesReturn::class)
                    ->visible(fn (SalesInvoice $record): bool => $record->status !== 'cancelled')
                    ->url(fn (SalesInvoice $record): string => SalesReturnResource::getUrl('create', ['invoice_id' => $record->id])),
                Action::make('downloadPdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->authorize('downloadPdf')
                    ->action(fn (SalesInvoice $record) => (new SalesInvoicePdf($record))->download()),
                Action::make('recordPayment')
                    ->label('Catat Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->authorize('recordPayment')
                    ->visible(fn (SalesInvoice $record): bool => in_array($record->status, ['issued', 'partial'], true))
                    ->schema([
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->prefix('Rp')
                            ->default(fn (SalesInvoice $record): string => Money::round(Money::sub(Money::sub($record->total, $record->returned_amount), $record->paid_amount))),
                        DatePicker::make('paid_at')
                            ->label('Tanggal Bayar')
                            ->required()
                            ->default(now()->toDateString()),
                        Select::make('payment_method')
                            ->label('Metode')
                            ->options([
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer Bank',
                                'qris' => 'QRIS',
                                'other' => 'Lainnya',
                            ])
                            ->required()
                            ->default('transfer'),
                        TextInput::make('reference')
                            ->label('Referensi'),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2),
                    ])
                    ->action(function (SalesInvoice $record, array $data): void {
                        $createdBy = auth()->id();

                        try {
                            app(RecordPaymentAction::class)->execute(new RecordPaymentData(
                                invoiceId: $record->id,
                                amount: (string) $data['amount'],
                                paidAt: (string) $data['paid_at'],
                                paymentMethod: (string) $data['payment_method'],
                                reference: isset($data['reference']) ? (string) $data['reference'] : null,
                                createdBy: $createdBy !== null ? (string) $createdBy : null,
                                postJournal: true,
                                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                            ));

                            Notification::make()->title('Pembayaran berhasil dicatat.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal mencatat pembayaran.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('void')
                    ->label('Batalkan Faktur')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('void')
                    ->visible(fn (SalesInvoice $record): bool => ! in_array($record->status, ['cancelled', 'paid'], true)
                        && ! Money::isPositive($record->returned_amount)
                        && ($record->returns_count ?? 0) === 0
                        && ! ($record->salesOrder?->deliveries()->exists() ?? false))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (SalesInvoice $record, array $data): void {
                        $createdBy = auth()->id();

                        try {
                            app(VoidSalesInvoiceAction::class)->execute(new VoidSalesInvoiceData(
                                invoiceId: $record->id,
                                reason: (string) $data['reason'],
                                createdBy: $createdBy !== null ? (string) $createdBy : null,
                            ));

                            Notification::make()->title('Faktur berhasil dibatalkan.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Gagal membatalkan faktur.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'salesOrder'])->withCount('returns');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesInvoices::route('/'),
            'create' => Pages\CreateSalesInvoice::route('/create'),
            'view' => Pages\ViewSalesInvoice::route('/{record}'),
        ];
    }
}
