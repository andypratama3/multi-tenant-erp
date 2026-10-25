<?php

namespace App\Domain\Inventory\Filament\Resources\WarehouseTransferResource\Pages;

use App\Domain\Inventory\Filament\Resources\WarehouseTransferResource;
use App\Filament\Support\DocumentActions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewWarehouseTransfer extends ViewRecord
{
    protected static string $resource = WarehouseTransferResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['fromWarehouse', 'toWarehouse', 'items.product', 'activities.causer']);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocumentActions::warehouseTransferPost(),
        ];
    }
}
