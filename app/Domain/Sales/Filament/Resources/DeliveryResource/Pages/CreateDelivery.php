<?php

namespace App\Domain\Sales\Filament\Resources\DeliveryResource\Pages;

use App\Domain\Sales\Actions\CreateDeliveryAction;
use App\Domain\Sales\Actions\CreateDeliveryData;
use App\Domain\Sales\Filament\Resources\DeliveryResource;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesOrderItem;
use App\Filament\Support\DocumentFormData;
use App\Support\Money;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateDelivery extends CreateRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();

        $salesOrderId = request()->query('sales_order_id');

        if (! is_string($salesOrderId) || $salesOrderId === '') {
            return;
        }

        $salesOrder = SalesOrder::query()
            ->with('items')
            ->find($salesOrderId);

        if ($salesOrder === null) {
            return;
        }

        $items = $salesOrder->items
            ->filter(fn (SalesOrderItem $item): bool => bccomp(
                Money::decimal($item->quantity_ordered, 3),
                Money::decimal($item->quantity_delivered, 3),
                3,
            ) === 1)
            ->map(fn (SalesOrderItem $item): array => [
                'sales_order_item_id' => $item->id,
                'quantity_delivered' => Money::decimal(bcsub(
                    Money::decimal($item->quantity_ordered, 3),
                    Money::decimal($item->quantity_delivered, 3),
                    3,
                ), 3),
            ])
            ->values()
            ->all();

        $this->form->fill([
            'sales_order_id' => $salesOrder->id,
            'shipped_at' => now()->toDateString(),
            'notes' => $salesOrder->notes,
            'items' => $items,
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(CreateDeliveryAction::class)->execute(new CreateDeliveryData(
                salesOrderId: (string) $data['sales_order_id'],
                warehouseId: (string) $data['warehouse_id'],
                shippedAt: isset($data['shipped_at']) ? (string) $data['shipped_at'] : null,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
                items: DocumentFormData::deliveryItems($data['items'] ?? []),
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Pengiriman gagal dibuat.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengiriman berhasil dibuat sebagai draft.';
    }
}
