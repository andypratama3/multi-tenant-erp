<?php

namespace App\Domain\Accounting\Filament\Resources\JournalResource\Pages;

use App\Domain\Accounting\Filament\Resources\JournalResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJournals extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = JournalResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
