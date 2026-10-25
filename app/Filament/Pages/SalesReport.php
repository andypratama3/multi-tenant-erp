<?php

namespace App\Filament\Pages;

use App\Domain\Reporting\Exports\SalesSummaryExport;
use App\Domain\Reporting\Services\SalesSummaryReport;
use App\Domain\Sales\Models\SalesInvoice;
use App\Support\Money;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesReport extends Page
{
    protected string $view = 'filament.pages.sales-report';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Penjualan';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?int $navigationSort = 10;

    /** @var array{dailySummary: array<int, array<string, mixed>>, topProducts: array<int, array<string, mixed>>, arAging: array<int, array<string, mixed>>} */
    public array $reportData = ['dailySummary' => [], 'topProducts' => [], 'arAging' => []];

    /** @var array<string, string|int> */
    public array $summary = [];

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

        $this->summary = $this->summarize();
    }

    /** @return array<string, string|int> */
    private function summarize(): array
    {
        $invoiceCount = 0;
        $subtotal = '0.00';
        $tax = '0.00';
        $total = '0.00';
        $paid = '0.00';
        $outstanding = '0.00';
        $overdue = '0.00';

        foreach ($this->reportData['dailySummary'] as $row) {
            $invoiceCount += (int) $row['invoice_count'];
            $subtotal = Money::add($subtotal, (string) $row['subtotal']);
            $tax = Money::add($tax, (string) $row['tax_amount']);
            $total = Money::add($total, (string) $row['total']);
            $paid = Money::add($paid, (string) $row['paid_amount']);
        }

        foreach ($this->reportData['arAging'] as $row) {
            $outstanding = Money::add($outstanding, (string) $row['outstanding']);
            $overdue = Money::add($overdue, Money::add((string) $row['overdue_1_30'], (string) $row['overdue_31_60']));
            $overdue = Money::add($overdue, (string) $row['overdue_60_plus']);
        }

        return [
            'invoice_count' => $invoiceCount,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'paid' => $paid,
            'outstanding' => $outstanding,
            'overdue' => $overdue,
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

    public function export(): BinaryFileResponse
    {
        $this->validate([
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ]);

        return Excel::download(
            new SalesSummaryExport((string) $this->fromDate, (string) $this->toDate),
            'laporan-penjualan-'.$this->fromDate.'-'.$this->toDate.'.xlsx',
        );
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'summary' => $this->summary,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ];
    }
}
