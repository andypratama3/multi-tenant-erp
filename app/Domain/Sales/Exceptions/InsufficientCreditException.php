<?php

namespace App\Domain\Sales\Exceptions;

use RuntimeException;

class InsufficientCreditException extends RuntimeException
{
    public function __construct(string $customerId, string $requested, string $available)
    {
        parent::__construct(
            "Customer {$customerId} has insufficient credit. Requested: {$requested}, Available: {$available}"
        );
    }
}
