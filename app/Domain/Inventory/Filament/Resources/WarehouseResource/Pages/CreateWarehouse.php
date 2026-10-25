<?php

namespace App\Domain\Inventory\Filament\Resources\WarehouseResource\Pages;

use App\Domain\Inventory\Filament\Resources\WarehouseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;
}
