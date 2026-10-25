<?php

namespace App\Domain\Accounting\Actions;

final class PostJournalData
{
    /**
     * @param  array<int, array{account_id: string, type: string, amount: string, description?: string|null}>  $lines
     */
    public function __construct(
        public readonly string $postedAt,
        public readonly string $description,
        public readonly array $lines,
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId = null,
        public readonly ?string $createdBy = null,
    ) {}
}
