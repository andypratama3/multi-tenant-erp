<?php

namespace App\Domain\Inventory\Filament\Resources\StockAdjustmentResource\Pages;

use App\Domain\Inventory\Filament\Resources\StockAdjustmentResource;
use App\Support\Documents\DocumentNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['adjustment_number'] = app(DocumentNumberGenerator::class)->next('SA');
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Stock opname berhasil dibuat.';
    }
}
