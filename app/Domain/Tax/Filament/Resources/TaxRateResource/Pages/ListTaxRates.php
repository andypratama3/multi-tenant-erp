<?php

namespace App\Domain\Tax\Filament\Resources\TaxRateResource\Pages;

use App\Domain\Tax\Filament\Resources\TaxRateResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxRates extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = TaxRateResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
