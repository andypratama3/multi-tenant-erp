<?php

namespace App\Domain\Sales\Exceptions;

use RuntimeException;

class InvoiceAlreadyPaidException extends RuntimeException
{
    public function __construct(string $invoiceNumber)
    {
        parent::__construct("Invoice {$invoiceNumber} has already been paid.");
    }
}
