<?php

namespace App\Domain\Sales\Exceptions;

use RuntimeException;

class DeliveryAlreadyPostedException extends RuntimeException
{
    public function __construct(string $deliveryNumber)
    {
        parent::__construct("Pengiriman {$deliveryNumber} sudah diposting.");
    }
}
