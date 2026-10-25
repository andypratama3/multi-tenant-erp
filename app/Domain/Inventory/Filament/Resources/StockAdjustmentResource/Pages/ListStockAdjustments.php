<?php

namespace App\Domain\Inventory\Filament\Resources\StockAdjustmentResource\Pages;

use App\Domain\Inventory\Filament\Resources\StockAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
