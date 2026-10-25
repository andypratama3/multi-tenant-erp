<?php

namespace App\Domain\Inventory\Filament\Resources\WarehouseResource\Pages;

use App\Domain\Inventory\Filament\Resources\WarehouseResource;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;
}
