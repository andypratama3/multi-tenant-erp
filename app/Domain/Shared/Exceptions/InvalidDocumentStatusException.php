<?php

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

class InvalidDocumentStatusException extends RuntimeException
{
    public function __construct(string $document, string $current, string $required)
    {
        parent::__construct("Dokumen {$document} berstatus {$current}; aksi ini memerlukan status {$required}.");
    }
}
