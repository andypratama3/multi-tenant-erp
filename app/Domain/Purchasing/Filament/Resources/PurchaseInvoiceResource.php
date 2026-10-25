<?php

namespace App\Domain\Purchasing\Filament\Resources;

use App\Domain\Purchasing\Actions\RecordSupplierPaymentAction;
use App\Domain\Purchasing\Actions\RecordSupplierPaymentData;
use App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource\Pages;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';

    protected static ?string $modelLabel = 'Faktur Pembelian';

    protected static ?string $pluralModelLabel = 'Faktur Pembelian';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Faktur Pembelian')
                ->schema([
                    Select::make('grn_id')
                        ->label('Penerimaan Barang (GRN)')
                        ->required()
                        ->live()
                        ->searchable()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === null) {
                                return;
                            }

                            $grn = GoodsReceiptNote::query()->with('purchaseOrder.supplier')->find($state);
                            $terms = 30;

                            if ($grn !== null && $grn->purchaseOrder !== null && $grn->purchaseOrder->supplier !== null) {
                                $terms = $grn->purchaseOrder->supplier->payment_terms_days;
                            }

                            $set('due_date', now()->addDays($terms)->toDateString());
                        })
                        ->options(fn (): array => GoodsReceiptNote::query()
                            ->with('purchaseOrder.supplier')
                            ->where('status', 'posted')
                            ->whereDoesntHave('purchaseInvoice')
                            ->orderByDesc('received_at')
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(fn (GoodsReceiptNote $grn): array => [
                                $grn->id => $grn->grn_number.' — '.($grn->purchaseOrder?->supplier->name ?? '-'),
                            ])
                            ->all()),
                    TextInput::make('supplier_invoice_number')
                        ->label('No. Faktur Pemasok')
                        ->maxLength(100),
                    DatePicker::make('invoice_date')
                        ->label('Tanggal Faktur')
                        ->required()
                        ->default(now()->toDateString()),
                    DatePicker::make('due_date')
                        ->label('Jatuh Tempo')
                        ->required()
                        ->default(now()->addDays(30)->toDateString())
                        ->afterOrEqual('invoice_date'),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Faktur')
                ->schema([
                    TextEntry::make('ap_number')->label('No. Faktur Internal'),
                    TextEntry::make('supplier_invoice_number')->label('No. Faktur Pemasok')->placeholder('—'),
                    TextEntry::make('supplier.name')->label('Pemasok'),
                    TextEntry::make('grn.grn_number')->label('GRN'),
                    TextEntry::make('invoice_date')->label('Tanggal Faktur')->date('d M Y'),
                    TextEntry::make('due_date')->label('Jatuh Tempo')->date('d M Y'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
            Section::make('Rincian Barang')
                ->schema([
                    RepeatableEntry::make('grn.items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('product.name')->label('Produk'),
                            TextEntry::make('quantity_received')->label('Qty'),
                            TextEntry::make('unit_cost')->label('Harga')->money('IDR'),
                        ])
                        ->columns(3),
                ]),
            Section::make('Total')
                ->schema([
                    TextEntry::make('subtotal')->label('Subtotal')->money('IDR'),
                    TextEntry::make('total')->label('Total')->money('IDR')->weight('bold'),
                    TextEntry::make('paid_amount')->label('Dibayar')->money('IDR'),
                    TextEntry::make('outstanding')
                        ->label('Sisa')
                        ->state(fn (PurchaseInvoice $record): string => Money::sub($record->total, $record->paid_amount))
                        ->money('IDR'),
                ])
                ->columns(4),
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('ap_number')->label('No. Faktur')->searchable()->sortable(),
            TextColumn::make('supplier_invoice_number')->label('Faktur Pemasok')->placeholder('—')->searchable(),
            TextColumn::make('supplier.name')->label('Pemasok')->searchable(),
            TextColumn::make('grn.grn_number')->label('GRN'),
            TextColumn::make('invoice_date')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('due_date')->label('Jatuh Tempo')->date('d M Y')->sortable(),
            TextColumn::make('status')->label('Status')->badge(),
            TextColumn::make('total')->label('Total')->money('IDR')->summarize(Sum::make()->label('Total')->money('IDR')),
            TextColumn::make('paid_amount')->label('Dibayar')->money('IDR'),
            TextColumn::make('outstanding')
                ->label('Sisa Utang')
                ->state(fn (PurchaseInvoice $record): string => Money::sub($record->total, $record->paid_amount))
                ->money('IDR')
                ->color(fn (PurchaseInvoice $record): string => $record->status !== 'paid' && $record->due_date->isPast() ? 'danger' : 'gray'),
        ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'posted' => 'Terbuka',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                    ]),
                SelectFilter::make('supplier_id')
                    ->label('Pemasok')
                    ->relationship('supplier', 'name')
                    ->searchable(),
                Filter::make('jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (is_string($data['from'] ?? null)) {
                            $query->whereDate('due_date', '>=', $data['from']);
                        }

                        if (is_string($data['until'] ?? null)) {
                            $query->whereDate('due_date', '<=', $data['until']);
                        }

                        return $query;
                    }),
                Filter::make('overdue')
                    ->label('Lewat Jatuh Tempo')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('due_date', '<', now()->toDateString())
                        ->whereIn('status', ['posted', 'partial'])),
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (is_string($data['from'] ?? null)) {
                            $query->whereDate('invoice_date', '>=', $data['from']);
                        }

                        if (is_string($data['until'] ?? null)) {
                            $query->whereDate('invoice_date', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('recordPayment')
                    ->label('Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->authorize('recordPayment')
                    ->visible(fn (PurchaseInvoice $record): bool => in_array($record->status, ['posted', 'partial'], true))
                    ->schema([
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->default(fn (PurchaseInvoice $record): string => Money::sub($record->total, $record->paid_amount))
                            ->prefix('Rp'),
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
                        TextInput::make('reference')->label('Referensi')->maxLength(100),
                        Textarea::make('notes')->label('Catatan')->rows(2),
                    ])
                    ->action(function (PurchaseInvoice $record, array $data): void {
                        $createdBy = auth()->id();

                        try {
                            app(RecordSupplierPaymentAction::class)->execute(new RecordSupplierPaymentData(
                                purchaseInvoiceId: $record->id,
                                amount: (string) $data['amount'],
                                paidAt: (string) $data['paid_at'],
                                paymentMethod: (string) $data['payment_method'],
                                reference: isset($data['reference']) ? (string) $data['reference'] : null,
                                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                                createdBy: $createdBy !== null ? (string) $createdBy : null,
                                postJournal: true,
                            ));

                            Notification::make()->title('Pembayaran utang berhasil dicatat.')->success()->send();
                        } catch (Throwable $exception) {
                            Notification::make()->title('Pembayaran gagal dicatat.')->body($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Belum ada faktur pembelian')
            ->emptyStateDescription('Buat faktur dari penerimaan barang yang sudah diposting.')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['supplier', 'grn', 'payments']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseInvoices::route('/'),
            'create' => Pages\CreatePurchaseInvoice::route('/create'),
            'view' => Pages\ViewPurchaseInvoice::route('/{record}'),
        ];
    }
}
