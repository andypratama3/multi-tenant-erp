<?php

namespace App\Domain\Sales\Actions;

final class VoidSalesInvoiceData
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $reason,
        public readonly ?string $createdBy,
    ) {}
}
