<?php

namespace App\Domain\Inventory\Filament\Resources\ProductResource\Pages;

use App\Domain\Inventory\Filament\Resources\ProductResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = ProductResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
