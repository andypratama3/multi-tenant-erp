<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Accounting\Services\SupplierPaymentJournalService;
use App\Domain\Purchasing\Exceptions\SupplierPaymentExceedsOutstandingException;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Purchasing\Models\SupplierPayment;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordSupplierPaymentAction
{
    public function execute(RecordSupplierPaymentData $data): SupplierPayment
    {
        return DB::transaction(function () use ($data): SupplierPayment {
            /** @var PurchaseInvoice $invoice */
            $invoice = PurchaseInvoice::query()->lockForUpdate()->findOrFail($data->purchaseInvoiceId);

            if (! in_array($invoice->status, ['posted', 'partial'], true)) {
                throw new InvalidDocumentStatusException("Faktur {$invoice->ap_number}", $invoice->status, 'posted/partial');
            }

            $amount = Money::round($data->amount);

            if (! Money::isPositive($amount)) {
                throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari nol.');
            }

            $outstanding = Money::sub($invoice->total, $invoice->paid_amount);

            if (Money::compare($amount, $outstanding) === 1) {
                throw new SupplierPaymentExceedsOutstandingException($amount, $outstanding);
            }

            $payment = SupplierPayment::create([
                'payment_number' => app(DocumentNumberGenerator::class)->next('SP'),
                'purchase_invoice_id' => $invoice->id,
                'paid_at' => $data->paidAt,
                'payment_method' => $data->paymentMethod,
                'amount' => $amount,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            $paidAmount = Money::add($invoice->paid_amount, $amount);
            $invoice->paid_amount = $paidAmount;
            $invoice->status = Money::compare($paidAmount, $invoice->total) >= 0 ? 'paid' : 'partial';
            $invoice->save();

            if ($data->postJournal) {
                app(SupplierPaymentJournalService::class)->postForPayment($payment, $invoice);
            }

            return $payment->load('invoice.supplier');
        });
    }
}
