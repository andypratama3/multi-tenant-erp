<?php

namespace App\Filament\Widgets;

use App\Domain\Sales\Models\SalesInvoice;
use App\Support\Money;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueInvoiceWidget extends BaseWidget
{
    protected static ?string $heading = 'Faktur Jatuh Tempo';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', SalesInvoice::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SalesInvoice::query()
                    ->with('customer')
                    ->whereIn('status', ['issued', 'partial'])
                    ->whereDate('due_at', '<', today())
                    ->whereColumn('total', '>', 'paid_amount')
                    ->orderBy('due_at')
            )
            ->columns([
                TextColumn::make('invoice_number')->label('No. Faktur')->searchable(),
                TextColumn::make('customer.name')->label('Pelanggan'),
                TextColumn::make('due_at')->label('Jatuh Tempo')->date('d M Y'),
                TextColumn::make('total')->label('Total')->money('IDR'),
                TextColumn::make('paid_amount')->label('Dibayar')->money('IDR'),
                TextColumn::make('outstanding')
                    ->label('Sisa')
                    ->state(fn (SalesInvoice $record): string => Money::sub($record->total, $record->paid_amount))
                    ->money('IDR'),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10])
            ->emptyStateHeading('Tidak ada faktur jatuh tempo')
            ->emptyStateDescription('Semua faktur masih dalam masa jatuh tempo.')
            ->defaultSort('due_at');
    }
}
