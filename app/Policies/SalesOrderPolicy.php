<?php

declare(strict_types=1);

namespace App\Policies;

final class SalesOrderPolicy extends DomainPolicy
{
    protected static function subject(): string
    {
        return 'SalesOrder';
    }
}
