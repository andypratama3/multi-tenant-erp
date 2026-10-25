<?php

namespace App\Filament\Widgets;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PurchasingSummaryWidget extends BaseWidget
{
    protected ?string $heading = 'Ringkasan Pembelian';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', PurchaseOrder::class) ?? false;
    }

    /** @return list<Stat> */
    protected function getStats(): array
    {
        $openPurchaseOrders = (int) DB::table('purchase_orders')
            ->whereNull('deleted_at')
            ->whereIn('status', ['sent', 'partial'])
            ->count();

        $draftReceipts = (int) DB::table('goods_receipt_notes')
            ->whereNull('deleted_at')
            ->where('status', 'draft')
            ->count();

        $monthValue = (string) DB::table('purchase_orders')
            ->whereNull('deleted_at')
            ->whereIn('status', ['sent', 'partial', 'received'])
            ->whereMonth('ordered_at', now()->month)
            ->whereYear('ordered_at', now()->year)
            ->sum('total');

        $activeSuppliers = (int) DB::table('suppliers')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('PO Menunggu Penerimaan', $openPurchaseOrders)
                ->description('Status terkirim atau sebagian')
                ->color('warning')
                ->icon('heroicon-o-truck'),
            Stat::make('Penerimaan Belum Diposting', $draftReceipts)
                ->description('Perlu diposting gudang')
                ->color('info')
                ->icon('heroicon-o-archive-box-arrow-down'),
            Stat::make('Nilai PO Bulan Ini', Money::formatIdr($monthValue))
                ->description('Pesanan aktif bulan '.now()->translatedFormat('F'))
                ->color('success')
                ->icon('heroicon-o-shopping-cart'),
            Stat::make('Pemasok Aktif', $activeSuppliers)
                ->description('Pemasok siap dipakai')
                ->color('gray')
                ->icon('heroicon-o-building-office'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
