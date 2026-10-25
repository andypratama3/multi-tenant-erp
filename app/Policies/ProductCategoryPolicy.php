<?php

declare(strict_types=1);

namespace App\Policies;

final class ProductCategoryPolicy extends DomainPolicy
{
    protected static function subject(): string
    {
        return 'ProductCategory';
    }
}
