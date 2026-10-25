<?php

namespace App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;

use App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = PurchaseOrderResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
