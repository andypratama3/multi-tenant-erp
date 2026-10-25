<?php

namespace App\Domain\Purchasing\Exceptions;

use RuntimeException;

class GrnQuantityExceedsOrderedException extends RuntimeException
{
    public function __construct(string $productName, string $requested, string $remaining)
    {
        parent::__construct(
            "Jumlah diterima untuk {$productName} ({$requested}) melebihi sisa pesanan ({$remaining})."
        );
    }
}
