<?php

namespace App\Domain\Purchasing\Exceptions;

use RuntimeException;

class PurchaseInvoiceAlreadyExistsException extends RuntimeException
{
    public function __construct(string $grnNumber)
    {
        parent::__construct("Faktur pembelian untuk GRN {$grnNumber} sudah ada.");
    }
}
