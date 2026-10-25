<?php

namespace App\Domain\Inventory\Filament\Resources\WarehouseTransferResource\Pages;

use App\Domain\Inventory\Filament\Resources\WarehouseTransferResource;
use App\Support\Documents\DocumentNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouseTransfer extends CreateRecord
{
    protected static string $resource = WarehouseTransferResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['transfer_number'] = app(DocumentNumberGenerator::class)->next('WT');
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Transfer gudang berhasil dibuat.';
    }
}
