<?php

namespace App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceiptNotes extends ListRecords
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
