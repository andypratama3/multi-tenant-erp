<?php

namespace App\Domain\Purchasing\Exceptions;

use RuntimeException;

class SupplierPaymentExceedsOutstandingException extends RuntimeException
{
    public function __construct(string $amount, string $outstanding)
    {
        parent::__construct("Nominal pembayaran {$amount} melebihi sisa utang {$outstanding}.");
    }
}
