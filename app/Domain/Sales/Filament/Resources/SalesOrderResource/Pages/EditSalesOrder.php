<?php

namespace App\Domain\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Domain\Sales\Actions\UpdateSalesOrderAction;
use App\Domain\Sales\Actions\UpdateSalesOrderData;
use App\Domain\Sales\Filament\Resources\SalesOrderResource;
use App\Domain\Sales\Models\SalesOrder;
use App\Filament\Support\DocumentFormData;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof SalesOrder) {
            return $data;
        }

        $data['items'] = $record->items()->get()->map(fn ($item): array => [
            'product_id' => $item->product_id,
            'quantity_ordered' => (string) $item->quantity_ordered,
            'unit_price' => (string) $item->unit_price,
            'discount_rate' => (string) $item->discount_rate,
            'tax_rate' => (string) $item->tax_rate,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof SalesOrder) {
            return $record;
        }

        try {
            return app(UpdateSalesOrderAction::class)->execute(new UpdateSalesOrderData(
                salesOrderId: $record->id,
                customerId: (string) $data['customer_id'],
                orderedAt: (string) $data['ordered_at'],
                requiredAt: isset($data['required_at']) ? (string) $data['required_at'] : null,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                items: DocumentFormData::salesOrderItems($data['items'] ?? []),
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Pesanan penjualan gagal diperbarui.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn (SalesOrder $record): bool => $record->status === 'draft'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pesanan penjualan berhasil diperbarui.';
    }
}
