<?php

namespace App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource\Pages;

use App\Domain\Purchasing\Actions\CreateGoodsReceiptNoteAction;
use App\Domain\Purchasing\Actions\CreateGoodsReceiptNoteData;
use App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource;
use App\Filament\Support\DocumentFormData;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateGoodsReceiptNote extends CreateRecord
{
    protected static string $resource = GoodsReceiptNoteResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();

        $purchaseOrderId = request()->query('purchase_order_id');

        if (! is_string($purchaseOrderId) || $purchaseOrderId === '') {
            return;
        }

        $this->form->fill([
            'purchase_order_id' => $purchaseOrderId,
            'received_at' => now()->toDateString(),
            'items' => [],
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(CreateGoodsReceiptNoteAction::class)->execute(new CreateGoodsReceiptNoteData(
                purchaseOrderId: (string) $data['purchase_order_id'],
                warehouseId: (string) $data['warehouse_id'],
                receivedAt: (string) $data['received_at'],
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
                items: DocumentFormData::receiptItems($data['items'] ?? []),
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Penerimaan barang gagal dibuat.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Penerimaan barang berhasil dibuat sebagai draft.';
    }
}
