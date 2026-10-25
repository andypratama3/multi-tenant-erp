<?php

namespace App\Domain\Sales\Filament\Resources\PaymentResource\Pages;

use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Actions\RecordPaymentData;
use App\Domain\Sales\Filament\Resources\PaymentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(RecordPaymentAction::class)->execute(new RecordPaymentData(
                invoiceId: (string) $data['sales_invoice_id'],
                amount: (string) $data['amount'],
                paidAt: (string) $data['paid_at'],
                paymentMethod: (string) $data['payment_method'],
                reference: isset($data['reference']) ? (string) $data['reference'] : null,
                createdBy: $createdBy !== null ? (string) $createdBy : null,
                postJournal: true,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Pembayaran gagal dicatat.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pembayaran berhasil dicatat.';
    }
}
