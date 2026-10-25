<?php

namespace App\Domain\Sales\Filament\Resources\PaymentResource\Pages;

use App\Domain\Sales\Filament\Resources\PaymentResource;
use App\Filament\Concerns\AuthorizesResourceAccess;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    use AuthorizesResourceAccess;

    protected static string $resource = PaymentResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
