<?php

namespace App\Domain\Sales\Filament\Resources;

use App\Domain\Sales\Filament\Resources\PaymentResource\Pages;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\SalesInvoice;
use App\Filament\Support\DocumentTimeline;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_invoice_id')
                ->label('Faktur')
                ->required()
                ->searchable()
                ->options(fn (): array => SalesInvoice::query()
                    ->whereIn('status', ['issued', 'partial'])
                    ->latest()
                    ->limit(50)
                    ->pluck('invoice_number', 'id')
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
            TextInput::make('reference')
                ->label('Referensi'),
            Textarea::make('notes')
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pembayaran')
                ->schema([
                    TextEntry::make('payment_number')->label('No. Pembayaran'),
                    TextEntry::make('invoice.invoice_number')->label('No. Faktur'),
                    TextEntry::make('invoice.customer.name')->label('Pelanggan'),
                    TextEntry::make('paid_at')->label('Tanggal Bayar')->date('d M Y'),
                    TextEntry::make('payment_method')->label('Metode')->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer Bank',
                        'qris' => 'QRIS',
                        default => 'Lainnya',
                    }),
                    TextEntry::make('amount')->label('Nominal')->money('IDR'),
                    TextEntry::make('reference')->label('Referensi')->placeholder('—'),
                    TextEntry::make('notes')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(3),
            DocumentTimeline::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('payment_number')->label('No. Pembayaran')->searchable()->sortable(),
            TextColumn::make('invoice.invoice_number')->label('No. Faktur'),
            TextColumn::make('invoice.customer.name')->label('Pelanggan'),
            TextColumn::make('paid_at')->label('Tanggal')->date('d M Y')->sortable(),
            TextColumn::make('payment_method')->label('Metode')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'cash' => 'Tunai',
                    'transfer' => 'Transfer Bank',
                    'qris' => 'QRIS',
                    default => 'Lainnya',
                }),
            TextColumn::make('amount')->label('Nominal')->money('IDR'),
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
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (is_string($data['from'] ?? null)) {
                            $query->whereDate('paid_at', '>=', $data['from']);
                        }

                        if (is_string($data['until'] ?? null)) {
                            $query->whereDate('paid_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('Belum ada pembayaran')
            ->emptyStateDescription('Pembayaran faktur akan muncul di sini.')
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['invoice.customer']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
