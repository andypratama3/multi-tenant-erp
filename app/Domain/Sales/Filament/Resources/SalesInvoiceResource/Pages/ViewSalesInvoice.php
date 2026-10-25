<?php

namespace App\Domain\Sales\Filament\Resources\SalesInvoiceResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesInvoiceResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewSalesInvoice extends ViewRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['customer', 'salesOrder', 'items.product', 'payments', 'returns', 'activities.causer']);
    }
}
