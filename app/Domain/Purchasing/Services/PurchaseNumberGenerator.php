<?php

namespace App\Domain\Purchasing\Services;

use App\Support\Documents\DocumentNumberGenerator;

class PurchaseNumberGenerator
{
    public function __construct(
        private readonly ?DocumentNumberGenerator $numbers = null,
    ) {}

    public function nextPoNumber(): string
    {
        return $this->generator()->next('PO');
    }

    public function nextGrnNumber(): string
    {
        return $this->generator()->next('GRN');
    }

    private function generator(): DocumentNumberGenerator
    {
        return $this->numbers ?? app(DocumentNumberGenerator::class);
    }
}
