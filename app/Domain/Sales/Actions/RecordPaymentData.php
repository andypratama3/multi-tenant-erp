<?php

namespace App\Domain\Sales\Actions;

final class RecordPaymentData
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $amount,
        public readonly string $paidAt,
        public readonly string $paymentMethod,
        public readonly ?string $reference,
        public readonly ?string $createdBy,
        public readonly bool $postJournal = false,
        public readonly ?string $notes = null,
    ) {}
}
