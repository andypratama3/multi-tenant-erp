<?php

namespace App\Domain\Purchasing\Exceptions;

use RuntimeException;

class PurchaseOrderAlreadyReceivedException extends RuntimeException
{
    public function __construct(string $poNumber)
    {
        parent::__construct("Purchase Order {$poNumber} has already been received.");
    }
}
