<?php

namespace App\Domain\Inventory\Filament\Resources\UnitResource\Pages;

use App\Domain\Inventory\Filament\Resources\UnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;
}
