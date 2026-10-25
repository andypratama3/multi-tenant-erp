<?php

namespace App\Domain\Purchasing\Filament\Resources;

use App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource\Pages;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Purchasing\Models\SupplierPayment;
use App\Support\Money;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = SupplierPayment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Pembelian';

    protected static ?string $modelLabel = 'Pembayaran Pemasok';

    protected static ?string $pluralModelLabel = 'Pembayaran Pemasok';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pembayaran Utang')
                ->schema([
                    Select::make('purchase_invoice_id')
                        ->label('Faktur Pembelian')
                        ->required()
                        ->searchable()
                        ->options(fn (): array => PurchaseInvoice::query()
                            ->with('supplier')
                            ->whereIn('status', ['posted', 'partial'])
                            ->orderBy('due_date')
                            ->limit(100)
                            ->get()
                            ->mapWithKeys(fn (PurchaseInvoice $invoice): array => [
                                $invoice->id => $invoice->ap_number.' — '.($invoice->supplier->name ?? '-').' (sisa '.Money::formatIdr(Money::sub($invoice->total, $invoice->paid_amount)).')',
                            ])
                            ->all()),
                    TextInput::make('amount')
                        ->label('Nominal')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
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
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pembayaran')
                ->schema([
                    TextEntry::make('payment_number')->label('No. Pembayaran'),
                    TextEntry::make('invoice.ap_number')->label('Faktur Pembelian'),
                    TextEntry::make('invoice.supplier.name')->label('Pemasok'),
                    TextEntry::make('paid_at')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('payment_method')->label('Metode'),
                    TextEntry::make('amount')->label('Nominal')->money('IDR'),
                    TextEntry::make('reference')->label('Referensi')->placeholder('—'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('payment_number')->label('No. Pembayaran')->searchable()->sortable(),
            TextColumn::make('invoice.ap_number')->label('Faktur')->searchable(),
            TextColumn::make('invoice.supplier.name')->label('Pemasok')->searchable(),
            TextColumn::make('paid_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('payment_method')->label('Metode')->badge(),
            TextColumn::make('reference')->label('Referensi')->placeholder('—'),
            TextColumn::make('amount')->label('Nominal')->money('IDR')->summarize(Sum::make()->label('Total')->money('IDR')),
        ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Metode')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer Bank',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                    ]),
                SelectFilter::make('purchase_invoice_id')
                    ->label('Faktur')
                    ->relationship('invoice', 'ap_number')
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('Belum ada pembayaran pemasok')
            ->emptyStateDescription('Pembayaran muncul setelah faktur pembelian dibayar.')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['invoice.supplier']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierPayments::route('/'),
            'create' => Pages\CreateSupplierPayment::route('/create'),
            'view' => Pages\ViewSupplierPayment::route('/{record}'),
        ];
    }
}
