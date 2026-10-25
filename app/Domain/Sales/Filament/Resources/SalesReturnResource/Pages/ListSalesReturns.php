<?php

namespace App\Domain\Sales\Filament\Resources\SalesReturnResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesReturnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesReturns extends ListRecords
{
    protected static string $resource = SalesReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
