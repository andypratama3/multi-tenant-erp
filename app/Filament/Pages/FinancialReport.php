<?php

namespace App\Filament\Pages;

use App\Domain\Accounting\Models\Journal;
use App\Domain\Reporting\Data\AccountBalanceRow;
use App\Domain\Reporting\Data\TrialBalanceRow;
use App\Domain\Reporting\Services\FinancialReportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class FinancialReport extends Page
{
    protected string $view = 'filament.pages.financial-report';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static ?int $navigationSort = 12;

    public string $fromDate = '';

    public string $toDate = '';

    /** @var array<int, array<string, mixed>> */
    public array $trialBalance = [];

    /** @var array<string, mixed> */
    public array $incomeStatement = [];

    /** @var array<string, mixed> */
    public array $balanceSheet = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Journal::class) ?? false;
    }

    public function mount(): void
    {
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->loadReport();
    }

    public function updateReport(): void
    {
        $this->validate([
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ]);

        $this->loadReport();
    }

    public function loadReport(): void
    {
        $service = new FinancialReportService;
        $income = $service->incomeStatement($this->fromDate, $this->toDate);
        $balance = $service->balanceSheet($this->toDate);

        $this->trialBalance = $service->trialBalance($this->fromDate, $this->toDate)
            ->map(fn (TrialBalanceRow $row): array => (array) $row)
            ->all();

        $this->incomeStatement = [
            'rows' => $income['rows']->map(fn (AccountBalanceRow $row): array => (array) $row)->all(),
            'revenue' => $income['revenue'],
            'expense' => $income['expense'],
            'net_profit' => $income['net_profit'],
        ];

        $this->balanceSheet = [
            'assets' => $balance['assets']->map(fn (AccountBalanceRow $row): array => (array) $row)->all(),
            'liabilities' => $balance['liabilities']->map(fn (AccountBalanceRow $row): array => (array) $row)->all(),
            'equity' => $balance['equity']->map(fn (AccountBalanceRow $row): array => (array) $row)->all(),
            'total_assets' => $balance['total_assets'],
            'total_liabilities' => $balance['total_liabilities'],
            'total_equity' => $balance['total_equity'],
            'net_profit' => $balance['net_profit'],
            'total_liabilities_equity' => $balance['total_liabilities_equity'],
            'balanced' => $balance['balanced'],
        ];
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'trialBalance' => $this->trialBalance,
            'incomeStatement' => $this->incomeStatement,
            'balanceSheet' => $this->balanceSheet,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ];
    }
}
