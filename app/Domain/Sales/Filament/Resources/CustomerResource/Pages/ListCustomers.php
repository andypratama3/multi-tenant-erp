<?php

namespace App\Domain\Sales\Filament\Resources\CustomerResource\Pages;

use App\Domain\Sales\Filament\Resources\CustomerResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = CustomerResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
