<?php

namespace App\Filament\Concerns;

trait HasDateRangePresets
{
    public function applyPeriod(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $this->fromDate = $from->format('Y-m-d');
        $this->toDate = $to->format('Y-m-d');

        $this->afterPeriodApplied();
    }

    protected function afterPeriodApplied(): void {}
}
