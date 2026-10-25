<?php

namespace App\Filament\Widgets;

use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Sales\Models\SalesInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CashFlowChartWidget extends ChartWidget
{
    protected ?string $heading = 'Arus Kas 14 Hari';

    protected ?string $description = 'Penerimaan pelanggan dibanding pembayaran pemasok.';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return (auth()->user()?->can('viewAny', SalesInvoice::class) ?? false)
            || (auth()->user()?->can('viewAny', PurchaseInvoice::class) ?? false);
    }

    protected function getData(): array
    {
        /** @var array{labels: list<string>, datasets: list<array{label: string, data: list<float>}>} $data */
        $data = Cache::remember('dashboard:cash-flow-chart', now()->addMinutes(5), fn (): array => $this->buildData());

        return $data;
    }

    /** @return array{labels: list<string>, datasets: list<array{label: string, data: list<float>}>} */
    private function buildData(): array
    {
        $start = now()->subDays(13)->startOfDay();

        $inflow = DB::table('payments')
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->whereNull('deleted_at')
            ->where('paid_at', '>=', $start->toDateString())
            ->groupBy('day')
            ->pluck('total', 'day');

        $outflow = DB::table('supplier_payments')
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->whereNull('deleted_at')
            ->where('paid_at', '>=', $start->toDateString())
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $inflowValues = [];
        $outflowValues = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->toDateString();
            $labels[] = $date->translatedFormat('d M');
            $inflowValues[] = (float) ($inflow[$key] ?? 0);
            $outflowValues[] = (float) ($outflow[$key] ?? 0);
        }

        return [
            'datasets' => [
                ['label' => 'Kas Masuk', 'data' => $inflowValues],
                ['label' => 'Kas Keluar', 'data' => $outflowValues],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
