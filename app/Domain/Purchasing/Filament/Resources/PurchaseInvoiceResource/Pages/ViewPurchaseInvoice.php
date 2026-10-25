<?php

namespace App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource;
use App\Filament\Support\DocumentActions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewPurchaseInvoice extends ViewRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['supplier', 'grn.items.product', 'payments', 'activities.causer']);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocumentActions::purchaseInvoicePayment(),
        ];
    }
}
