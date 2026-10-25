<?php

namespace App\Domain\Purchasing\Actions;

final readonly class CreatePurchaseInvoiceFromGrnData
{
    public function __construct(
        public string $grnId,
        public string $invoiceDate,
        public string $dueDate,
        public ?string $supplierInvoiceNumber = null,
        public ?string $notes = null,
        public ?string $createdBy = null,
    ) {}
}
