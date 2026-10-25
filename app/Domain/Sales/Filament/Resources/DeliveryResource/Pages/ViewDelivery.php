<?php

namespace App\Domain\Sales\Filament\Resources\DeliveryResource\Pages;

use App\Domain\Sales\Filament\Resources\DeliveryResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['salesOrder.customer', 'warehouse', 'items.product']);
    }
}
