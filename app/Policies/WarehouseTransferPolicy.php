<?php

declare(strict_types=1);

namespace App\Policies;

final class WarehouseTransferPolicy extends DomainPolicy
{
    protected static function subject(): string
    {
        return 'WarehouseTransfer';
    }
}
