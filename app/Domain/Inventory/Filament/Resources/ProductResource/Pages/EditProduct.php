<?php

namespace App\Domain\Inventory\Filament\Resources\ProductResource\Pages;

use App\Domain\Inventory\Filament\Resources\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
}
