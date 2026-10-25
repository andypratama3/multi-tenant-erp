<?php

namespace App\Domain\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Domain\Sales\Filament\Resources\SalesOrderResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrders extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = SalesOrderResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
