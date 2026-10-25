<?php

namespace App\Domain\Inventory\Filament\Resources\ProductCategoryResource\Pages;

use App\Domain\Inventory\Filament\Resources\ProductCategoryResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductCategories extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = ProductCategoryResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
