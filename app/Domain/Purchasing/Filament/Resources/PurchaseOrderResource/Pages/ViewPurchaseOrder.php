<?php

namespace App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;

use App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewPurchaseOrder extends ViewRecord
{
    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['supplier', 'items.product', 'activities.causer']);
    }

    protected static string $resource = PurchaseOrderResource::class;
}
