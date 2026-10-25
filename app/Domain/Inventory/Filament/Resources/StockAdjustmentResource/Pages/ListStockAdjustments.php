<?php

namespace App\Domain\Inventory\Filament\Resources\StockAdjustmentResource\Pages;

use App\Domain\Inventory\Filament\Resources\StockAdjustmentResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
