<?php

namespace App\Domain\Inventory\Filament\Resources\UnitResource\Pages;

use App\Domain\Inventory\Filament\Resources\UnitResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnits extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = UnitResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
