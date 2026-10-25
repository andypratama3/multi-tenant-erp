<?php

namespace App\Domain\Accounting\Exceptions;

use RuntimeException;

class PeriodLockedException extends RuntimeException
{
    public function __construct(string $date, string $lockedUntil)
    {
        parent::__construct("Periode {$date} terkunci. Pembukuan dikunci sampai {$lockedUntil}.");
    }
}
