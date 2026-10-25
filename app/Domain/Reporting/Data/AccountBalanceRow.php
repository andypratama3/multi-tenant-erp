<?php

namespace App\Domain\Reporting\Data;

final readonly class AccountBalanceRow
{
    public function __construct(
        public string $code,
        public string $name,
        public string $type,
        public string $amount,
    ) {}
}
