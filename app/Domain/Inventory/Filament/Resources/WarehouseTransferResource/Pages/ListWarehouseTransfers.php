<?php

namespace App\Domain\Inventory\Filament\Resources\WarehouseTransferResource\Pages;

use App\Domain\Inventory\Filament\Resources\WarehouseTransferResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseTransfers extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = WarehouseTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
