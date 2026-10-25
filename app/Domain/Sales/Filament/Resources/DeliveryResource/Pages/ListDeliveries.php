<?php

namespace App\Domain\Sales\Filament\Resources\DeliveryResource\Pages;

use App\Domain\Sales\Filament\Resources\DeliveryResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveries extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = DeliveryResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
