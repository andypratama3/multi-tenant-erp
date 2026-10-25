<?php

namespace App\Domain\Sales\Exceptions;

use RuntimeException;

class PaymentExceedsOutstandingException extends RuntimeException
{
    public function __construct(string $invoiceNumber, string $amount, string $outstanding)
    {
        parent::__construct(
            "Pembayaran {$amount} melebihi sisa tagihan invoice {$invoiceNumber} ({$outstanding})."
        );
    }
}
