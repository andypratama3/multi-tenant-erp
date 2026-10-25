<?php

namespace App\Filament\Widgets;

use App\Domain\Sales\Models\SalesInvoice;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SalesSummaryWidget extends BaseWidget
{
    protected ?string $heading = 'Ringkasan Penjualan';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', SalesInvoice::class) ?? false;
    }

    /** @return list<Stat> */
    protected function getStats(): array
    {
        $today = today()->toDateString();

        $todaySales = (string) (DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->whereIn('status', ['issued', 'partial', 'paid'])
            ->whereDate('issued_at', $today)
            ->sum('total'));

        $monthSales = (string) (DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->whereIn('status', ['issued', 'partial', 'paid'])
            ->whereMonth('issued_at', date('n'))
            ->whereYear('issued_at', date('Y'))
            ->sum('total'));

        $outstandingAR = (string) (DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->whereIn('status', ['issued', 'partial'])
            ->sum(DB::raw('total - paid_amount')));

        $overdueCount = (int) DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->whereIn('status', ['issued', 'partial'])
            ->where('due_at', '<', $today)
            ->whereRaw('total > paid_amount')
            ->count();

        return [
            Stat::make('Penjualan Hari Ini', Money::formatIdr($todaySales))
                ->description('Total invoice hari ini')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Penjualan Bulan Ini', Money::formatIdr($monthSales))
                ->description('Total invoice bulan '.now()->translatedFormat('F'))
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
            Stat::make('Piutang Belum Dibayar', Money::formatIdr($outstandingAR))
                ->description('Belum dibayar')
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Faktur Terlambat', $overdueCount)
                ->description('Jatuh tempo terlewat')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
