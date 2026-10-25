<?php

namespace App\Domain\Inventory\Filament\Resources\ProductCategoryResource\Pages;

use App\Domain\Inventory\Filament\Resources\ProductCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
