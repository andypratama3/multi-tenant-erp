<?php

namespace App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Domain\Purchasing\Actions\CreatePurchaseInvoiceFromGrnAction;
use App\Domain\Purchasing\Actions\CreatePurchaseInvoiceFromGrnData;
use App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();

        $grnId = request()->query('grn_id');

        if (is_string($grnId) && $grnId !== '') {
            $this->form->fill([
                'grn_id' => $grnId,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
            ]);
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(CreatePurchaseInvoiceFromGrnAction::class)->execute(new CreatePurchaseInvoiceFromGrnData(
                grnId: (string) $data['grn_id'],
                invoiceDate: (string) $data['invoice_date'],
                dueDate: (string) $data['due_date'],
                supplierInvoiceNumber: isset($data['supplier_invoice_number']) ? (string) $data['supplier_invoice_number'] : null,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
            ));
        } catch (Throwable $exception) {
            Notification::make()->title('Faktur pembelian gagal dibuat.')->body($exception->getMessage())->danger()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Faktur pembelian berhasil dibuat.';
    }
}
