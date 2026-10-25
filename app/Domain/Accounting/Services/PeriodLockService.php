<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Exceptions\PeriodLockedException;
use App\Support\Settings\CompanySettings;
use Carbon\Carbon;

class PeriodLockService
{
    public function __construct(private readonly CompanySettings $settings) {}

    public function ensureUnlocked(string $date): void
    {
        $lockedUntil = $this->settings->get('period_locked_until');

        if ($lockedUntil === null || $lockedUntil === '') {
            return;
        }

        if (Carbon::parse($date)->startOfDay()->lessThanOrEqualTo(Carbon::parse($lockedUntil)->startOfDay())) {
            throw new PeriodLockedException($date, $lockedUntil);
        }
    }
}
