<?php

namespace App\Domain\Sales\Filament\Resources\SalesReturnResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesReturnResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesReturns extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = SalesReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
