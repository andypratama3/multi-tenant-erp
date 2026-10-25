<?php

declare(strict_types=1);

namespace App\Policies;

final class PurchaseInvoicePolicy extends DomainPolicy
{
    protected static function subject(): string
    {
        return 'PurchaseInvoice';
    }
}
