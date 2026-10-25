<?php

declare(strict_types=1);

namespace App\Policies;

final class StockAdjustmentPolicy extends DomainPolicy
{
    protected static function subject(): string
    {
        return 'StockAdjustment';
    }
}
