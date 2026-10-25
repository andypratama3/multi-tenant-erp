<?php

namespace App\Filament\Pages;

use App\Domain\Reporting\Services\SalesSummaryReport;
use App\Domain\Sales\Models\SalesInvoice;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class SalesReport extends Page
{
    protected string $view = 'filament.pages.sales-report';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed> */
    public array $reportData = [];

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', SalesInvoice::class) ?? false;
    }

    public function mount(): void
    {
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $report = new SalesSummaryReport;

        $this->reportData = [
            'dailySummary' => $report->dailySummary($this->fromDate ?? '', $this->toDate ?? '')
                ->map(fn (\stdClass $row): array => (array) $row)
                ->all(),
            'topProducts' => $report->topProducts($this->fromDate ?? '', $this->toDate ?? '')
                ->map(fn (\stdClass $row): array => (array) $row)
                ->all(),
            'arAging' => $report->arAging($this->toDate ?? '')
                ->map(fn (\stdClass $row): array => (array) $row)
                ->all(),
        ];
    }

    public function updateReport(): void
    {
        $this->validate([
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ]);

        $this->loadReport();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ];
    }
}
