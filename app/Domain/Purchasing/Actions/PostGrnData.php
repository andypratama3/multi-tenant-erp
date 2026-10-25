<?php

namespace App\Domain\Purchasing\Actions;

final class PostGrnData
{
    public function __construct(
        public readonly string $grnId,
        public readonly bool $postJournal = false,
    ) {}
}
