<?php

namespace App\Domain\Inventory\Filament\Resources\ProductResource\Pages;

use App\Domain\Inventory\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
