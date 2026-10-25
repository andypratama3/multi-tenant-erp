<?php

namespace App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource\Pages;

use App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPayments extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = SupplierPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
