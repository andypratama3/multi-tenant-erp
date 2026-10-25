<?php

namespace App\Domain\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesOrderResource;
use App\Filament\Support\DocumentActions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewSalesOrder extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['customer', 'items.product', 'activities.causer']);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocumentActions::salesOrderConfirm(),
            DocumentActions::salesOrderInvoice(),
            DocumentActions::salesOrderDelivery(),
        ];
    }
}
