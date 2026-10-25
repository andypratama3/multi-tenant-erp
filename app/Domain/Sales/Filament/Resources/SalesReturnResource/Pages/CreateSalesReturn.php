<?php

namespace App\Domain\Sales\Filament\Resources\SalesReturnResource\Pages;

use App\Domain\Sales\Actions\CreateSalesReturnAction;
use App\Domain\Sales\Actions\CreateSalesReturnData;
use App\Domain\Sales\Filament\Resources\SalesReturnResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateSalesReturn extends CreateRecord
{
    protected static string $resource = SalesReturnResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();

        $invoiceId = request()->query('invoice_id');

        if (is_string($invoiceId) && $invoiceId !== '') {
            $this->form->fill([
                'sales_invoice_id' => $invoiceId,
                'returned_at' => now()->toDateString(),
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();
        $items = [];

        foreach ((array) $data['items'] as $row) {
            if (! is_array($row) || ! isset($row['sales_invoice_item_id'])) {
                continue;
            }

            $items[] = [
                'sales_invoice_item_id' => (string) $row['sales_invoice_item_id'],
                'quantity' => (string) ($row['quantity'] ?? '0'),
                'restock' => (bool) ($row['restock'] ?? false),
            ];
        }

        try {
            return app(CreateSalesReturnAction::class)->execute(new CreateSalesReturnData(
                invoiceId: (string) $data['sales_invoice_id'],
                warehouseId: (string) $data['warehouse_id'],
                returnedAt: (string) $data['returned_at'],
                reason: (string) $data['reason'],
                items: $items,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
            ));
        } catch (Throwable $exception) {
            Notification::make()->title('Retur gagal dibuat.')->body($exception->getMessage())->danger()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Retur penjualan berhasil dibuat.';
    }
}
