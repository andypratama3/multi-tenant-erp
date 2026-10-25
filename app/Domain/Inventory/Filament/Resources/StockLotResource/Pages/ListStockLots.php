<?php

namespace App\Domain\Inventory\Filament\Resources\StockLotResource\Pages;

use App\Domain\Inventory\Filament\Resources\StockLotResource;
use Filament\Resources\Pages\ListRecords;

class ListStockLots extends ListRecords
{
    protected static string $resource = StockLotResource::class;
}
