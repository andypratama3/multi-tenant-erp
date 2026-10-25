<?php

namespace App\Filament\Widgets;

use App\Domain\Sales\Models\SalesInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesChartWidget extends ChartWidget
{
    protected ?string $heading = 'Tren Penjualan 14 Hari';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', SalesInvoice::class) ?? false;
    }

    protected function getData(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $totals = DB::table('sales_invoices')
            ->selectRaw('DATE(issued_at) as day, SUM(total) as total')
            ->whereNull('deleted_at')
            ->whereIn('status', ['issued', 'partial', 'paid'])
            ->where('issued_at', '>=', $start->toDateString())
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->toDateString();
            $labels[] = $date->translatedFormat('d M');
            $values[] = (float) ($totals[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $values,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
