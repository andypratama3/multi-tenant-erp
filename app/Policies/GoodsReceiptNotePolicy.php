<?php

declare(strict_types=1);

namespace App\Policies;

final class GoodsReceiptNotePolicy extends DomainPolicy
{
    protected static function subject(): string
    {
        return 'GoodsReceiptNote';
    }
}
