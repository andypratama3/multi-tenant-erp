<?php

namespace App\Filament\Pages;

use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Reporting\Services\ApAgingService;
use App\Support\Money;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ApAgingReport extends Page
{
    protected string $view = 'filament.pages.ap-aging-report';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Utang (AP)';

    protected static ?string $navigationLabel = 'Laporan Utang (AP)';

    protected static ?int $navigationSort = 11;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, string> */
    public array $totals = [];

    public ?string $asOfDate = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', PurchaseInvoice::class) ?? false;
    }

    public function mount(): void
    {
        $this->asOfDate = Carbon::now()->format('Y-m-d');
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $this->rows = (new ApAgingService)->aging($this->asOfDate ?? '')
            ->map(fn (\stdClass $row): array => (array) $row)
            ->all();

        $keys = ['outstanding', 'current_due', 'overdue_1_30', 'overdue_31_60', 'overdue_60_plus'];
        $totals = array_fill_keys($keys, '0.00');

        foreach ($this->rows as $row) {
            foreach ($keys as $key) {
                $totals[$key] = Money::add($totals[$key], (string) $row[$key]);
            }
        }

        $totals['overdue'] = Money::add($totals['overdue_1_30'], $totals['overdue_31_60']);
        $totals['overdue'] = Money::add($totals['overdue'], $totals['overdue_60_plus']);

        $this->totals = $totals;
    }

    public function updateReport(): void
    {
        $this->validate([
            'asOfDate' => ['required', 'date'],
        ]);

        $this->loadReport();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'rows' => $this->rows,
            'totals' => $this->totals,
            'asOfDate' => $this->asOfDate,
        ];
    }
}
