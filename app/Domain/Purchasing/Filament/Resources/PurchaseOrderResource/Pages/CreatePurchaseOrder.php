<?php

namespace App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;

use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderData;
use App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Filament\Support\DocumentFormData;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(CreatePurchaseOrderAction::class)->execute(new CreatePurchaseOrderData(
                supplierId: (string) $data['supplier_id'],
                orderedAt: (string) $data['ordered_at'],
                expectedAt: isset($data['expected_at']) ? (string) $data['expected_at'] : null,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
                items: DocumentFormData::purchaseOrderItems($data['items'] ?? []),
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Pesanan pembelian gagal dibuat.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pesanan pembelian berhasil dibuat.';
    }
}
