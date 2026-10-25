<?php

namespace App\Domain\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesOrderResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewSalesOrder extends ViewRecord
{
    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['customer', 'items.product']);
    }

    protected static string $resource = SalesOrderResource::class;
}
