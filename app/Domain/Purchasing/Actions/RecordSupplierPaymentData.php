<?php

namespace App\Domain\Purchasing\Actions;

final readonly class RecordSupplierPaymentData
{
    public function __construct(
        public string $purchaseInvoiceId,
        public string $amount,
        public string $paidAt,
        public string $paymentMethod,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?string $createdBy = null,
        public bool $postJournal = true,
    ) {}
}
