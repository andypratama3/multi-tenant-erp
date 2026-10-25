<?php

namespace App\Domain\Inventory\Filament\Resources\WarehouseResource\Pages;

use App\Domain\Inventory\Filament\Resources\WarehouseResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouses extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = WarehouseResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
