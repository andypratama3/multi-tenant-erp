<?php

namespace App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseInvoices extends ListRecords
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
