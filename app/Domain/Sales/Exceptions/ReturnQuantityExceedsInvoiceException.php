<?php

namespace App\Domain\Sales\Exceptions;

use RuntimeException;

class ReturnQuantityExceedsInvoiceException extends RuntimeException
{
    public function __construct(string $productName, string $requested, string $available)
    {
        parent::__construct("Retur {$productName} ({$requested}) melebihi sisa yang dapat diretur ({$available}).");
    }
}
