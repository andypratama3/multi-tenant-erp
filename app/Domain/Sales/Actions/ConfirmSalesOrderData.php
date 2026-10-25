<?php

namespace App\Domain\Sales\Actions;

final class ConfirmSalesOrderData
{
    public function __construct(
        public readonly string $salesOrderId,
        public readonly ?string $confirmedBy,
    ) {}
}
