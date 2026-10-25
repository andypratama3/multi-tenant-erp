<?php

namespace App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource\Pages;

use App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewSupplierPayment extends ViewRecord
{
    protected static string $resource = SupplierPaymentResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['invoice.supplier', 'activities.causer']);
    }
}
