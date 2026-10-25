<?php

namespace App\Domain\Purchasing\Filament\Resources\SupplierResource\Pages;

use App\Domain\Purchasing\Filament\Resources\SupplierResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = SupplierResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
