<?php

namespace App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource\Pages;

use App\Domain\Purchasing\Actions\UpdatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\UpdatePurchaseOrderData;
use App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Filament\Support\DocumentFormData;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof PurchaseOrder) {
            return $data;
        }

        $data['items'] = $record->items()->get()->map(fn ($item): array => [
            'product_id' => $item->product_id,
            'quantity_ordered' => (string) $item->quantity_ordered,
            'unit_price' => (string) $item->unit_price,
            'tax_rate' => (string) $item->tax_rate,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof PurchaseOrder) {
            return $record;
        }

        try {
            return app(UpdatePurchaseOrderAction::class)->execute(new UpdatePurchaseOrderData(
                purchaseOrderId: $record->id,
                supplierId: (string) $data['supplier_id'],
                orderedAt: (string) $data['ordered_at'],
                expectedAt: isset($data['expected_at']) ? (string) $data['expected_at'] : null,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                items: DocumentFormData::purchaseOrderItems($data['items'] ?? []),
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Pesanan pembelian gagal diperbarui.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn (PurchaseOrder $record): bool => $record->status === 'draft'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pesanan pembelian berhasil diperbarui.';
    }
}
