<?php

namespace App\Domain\Reporting\Data;

final readonly class TrialBalanceRow
{
    public function __construct(
        public string $code,
        public string $name,
        public string $type,
        public string $debit,
        public string $credit,
        public string $balance,
    ) {}
}
