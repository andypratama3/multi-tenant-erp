<?php

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

class DocumentAlreadyPostedException extends RuntimeException
{
    public function __construct(string $document)
    {
        parent::__construct("{$document} sudah diposting.");
    }
}
