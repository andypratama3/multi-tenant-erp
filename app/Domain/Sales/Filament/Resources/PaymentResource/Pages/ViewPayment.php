<?php

namespace App\Domain\Sales\Filament\Resources\PaymentResource\Pages;

use App\Domain\Sales\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['invoice.customer']);
    }
}
