<?php

namespace App\Domain\Sales\Filament\Resources\SalesOrderResource\Pages;

use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderData;
use App\Domain\Sales\Filament\Resources\SalesOrderResource;
use App\Filament\Support\DocumentFormData;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(CreateSalesOrderAction::class)->execute(new CreateSalesOrderData(
                customerId: (string) $data['customer_id'],
                orderedAt: (string) $data['ordered_at'],
                requiredAt: isset($data['required_at']) ? (string) $data['required_at'] : null,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
                items: DocumentFormData::salesOrderItems($data['items'] ?? []),
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Pesanan penjualan gagal dibuat.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pesanan penjualan berhasil dibuat.';
    }
}
