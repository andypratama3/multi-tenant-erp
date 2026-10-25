<?php

namespace App\Domain\Purchasing\Exceptions;

use RuntimeException;

class GrnAlreadyPostedException extends RuntimeException
{
    public function __construct(string $grnNumber)
    {
        parent::__construct("GRN {$grnNumber} has already been posted.");
    }
}
