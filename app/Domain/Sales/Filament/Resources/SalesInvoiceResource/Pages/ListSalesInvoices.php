<?php

namespace App\Domain\Sales\Filament\Resources\SalesInvoiceResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesInvoiceResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesInvoices extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = SalesInvoiceResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
