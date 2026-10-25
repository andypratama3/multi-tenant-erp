<?php

namespace App\Domain\Accounting\Exceptions;

use RuntimeException;

class JournalNotBalancedException extends RuntimeException
{
    public function __construct(float $debitTotal, float $creditTotal)
    {
        parent::__construct(
            sprintf(
                'Journal is not balanced. Debit: %.2f, Credit: %.2f, Difference: %.2f',
                $debitTotal,
                $creditTotal,
                abs($debitTotal - $creditTotal)
            )
        );
    }
}
