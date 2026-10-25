<?php

namespace App\Domain\Accounting\Filament\Resources\AccountResource\Pages;

use App\Domain\Accounting\Filament\Resources\AccountResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = AccountResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
