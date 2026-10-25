<?php

namespace App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource\Pages;

use App\Domain\Purchasing\Actions\RecordSupplierPaymentAction;
use App\Domain\Purchasing\Actions\RecordSupplierPaymentData;
use App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateSupplierPayment extends CreateRecord
{
    protected static string $resource = SupplierPaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(RecordSupplierPaymentAction::class)->execute(new RecordSupplierPaymentData(
                purchaseInvoiceId: (string) $data['purchase_invoice_id'],
                amount: (string) $data['amount'],
                paidAt: (string) $data['paid_at'],
                paymentMethod: (string) $data['payment_method'],
                reference: isset($data['reference']) ? (string) $data['reference'] : null,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
                postJournal: true,
            ));
        } catch (Throwable $exception) {
            Notification::make()->title('Pembayaran gagal dicatat.')->body($exception->getMessage())->danger()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pembayaran pemasok berhasil dicatat.';
    }
}
