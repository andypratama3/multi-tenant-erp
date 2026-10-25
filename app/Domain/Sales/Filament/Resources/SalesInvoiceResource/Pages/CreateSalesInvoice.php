<?php

namespace App\Domain\Sales\Filament\Resources\SalesInvoiceResource\Pages;

use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceData;
use App\Domain\Sales\Filament\Resources\SalesInvoiceResource;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesOrderItem;
use App\Filament\Support\DocumentFormData;
use App\Support\Money;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class CreateSalesInvoice extends CreateRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function fillForm(): void
    {
        parent::fillForm();

        $salesOrderId = request()->query('sales_order_id');

        if (! is_string($salesOrderId) || $salesOrderId === '') {
            return;
        }

        $salesOrder = SalesOrder::query()
            ->with(['customer', 'items'])
            ->find($salesOrderId);

        if ($salesOrder === null) {
            return;
        }

        $items = $salesOrder->items
            ->filter(fn (SalesOrderItem $item): bool => bccomp(
                Money::decimal($item->quantity_ordered, 3),
                Money::decimal($item->quantity_invoiced, 3),
                3,
            ) === 1)
            ->map(fn (SalesOrderItem $item): array => [
                'product_id' => $item->product_id,
                'quantity' => Money::decimal(bcsub(
                    Money::decimal($item->quantity_ordered, 3),
                    Money::decimal($item->quantity_invoiced, 3),
                    3,
                ), 3),
                'unit_price' => (string) $item->unit_price,
                'discount_rate' => (string) $item->discount_rate,
                'tax_rate' => (string) $item->tax_rate,
            ])
            ->values()
            ->all();

        $customer = $salesOrder->customer;
        $paymentTerms = $customer === null ? 30 : $customer->payment_terms_days;

        $this->form->fill([
            'customer_id' => $salesOrder->customer_id,
            'sales_order_id' => $salesOrder->id,
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays($paymentTerms)->toDateString(),
            'notes' => $salesOrder->notes,
            'items' => $items,
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = auth()->id();

        try {
            return app(IssueSalesInvoiceAction::class)->execute(new IssueSalesInvoiceData(
                salesOrderId: isset($data['sales_order_id']) ? (string) $data['sales_order_id'] : null,
                customerId: (string) $data['customer_id'],
                issuedAt: (string) $data['issued_at'],
                dueAt: (string) $data['due_at'],
                createdBy: $createdBy !== null ? (string) $createdBy : null,
                items: DocumentFormData::invoiceItems($data['items'] ?? []),
                postJournal: true,
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
            ));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Faktur gagal dibuat.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Faktur berhasil diterbitkan.';
    }
}
