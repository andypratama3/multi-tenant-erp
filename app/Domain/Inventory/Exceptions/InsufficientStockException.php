<?php

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $productId, string $warehouseId, string $requested, string $available)
    {
        parent::__construct(
            "Insufficient stock for product {$productId} in warehouse {$warehouseId}. Requested: {$requested}, Available: {$available}"
        );
    }
}
