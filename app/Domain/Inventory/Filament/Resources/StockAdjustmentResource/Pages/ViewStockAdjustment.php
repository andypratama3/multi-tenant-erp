<?php

namespace App\Domain\Inventory\Filament\Resources\StockAdjustmentResource\Pages;

use App\Domain\Inventory\Filament\Resources\StockAdjustmentResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewStockAdjustment extends ViewRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['warehouse', 'items.product', 'activities.causer']);
    }
}
