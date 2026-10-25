<?php

declare(strict_types=1);

namespace App\Policies;

final class SalesInvoicePolicy extends DomainPolicy
{
    protected static function subject(): string
    {
        return 'SalesInvoice';
    }
}
