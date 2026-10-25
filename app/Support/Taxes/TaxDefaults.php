<?php

namespace App\Support\Taxes;

use App\Domain\Tax\Models\TaxRate;
use App\Support\Money;

final class TaxDefaults
{
    public static function ppnRate(): string
    {
        $rate = TaxRate::query()
            ->where('type', 'ppn')
            ->where('is_active', true)
            ->latest()
            ->value('rate');

        return $rate !== null ? Money::decimal((string) $rate, 2) : '11.00';
    }
}
