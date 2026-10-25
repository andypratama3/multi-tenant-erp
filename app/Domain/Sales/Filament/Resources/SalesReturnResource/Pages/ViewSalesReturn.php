<?php

namespace App\Domain\Sales\Filament\Resources\SalesReturnResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesReturnResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewSalesReturn extends ViewRecord
{
    protected static string $resource = SalesReturnResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['invoice.customer', 'customer', 'warehouse', 'items.product', 'activities.causer']);
    }
}
