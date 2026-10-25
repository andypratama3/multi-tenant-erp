<?php

namespace App\Domain\Tax\Actions;

final class RecordTaxTransactionData
{
    public function __construct(
        public readonly string $taxRateId,
        public readonly string $documentType,
        public readonly string $documentId,
        public readonly string $baseAmount,
        public readonly string $transactionDate,
        public readonly string $direction, // 'in' | 'out'
        public readonly ?string $taxNumber = null,
        public readonly ?string $createdBy = null,
    ) {}
}
