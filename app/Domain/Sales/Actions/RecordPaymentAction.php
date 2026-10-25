<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Services\PaymentJournalService;
use App\Domain\Sales\Exceptions\InvoiceAlreadyPaidException;
use App\Domain\Sales\Exceptions\PaymentExceedsOutstandingException;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\SalesInvoice;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordPaymentAction
{
    public function execute(RecordPaymentData $data): Payment
    {
        return DB::transaction(function () use ($data): Payment {
            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($data->invoiceId);

            if ($invoice->status === 'paid') {
                throw new InvoiceAlreadyPaidException($invoice->invoice_number);
            }

            if ($invoice->status === 'cancelled') {
                throw new InvalidArgumentException('Invoice yang dibatalkan tidak dapat menerima pembayaran.');
            }

            if (! Money::isPositive($data->amount)) {
                throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari nol.');
            }

            $outstanding = Money::round(Money::sub($invoice->total, $invoice->paid_amount));

            if (Money::compare($data->amount, $outstanding) === 1) {
                throw new PaymentExceedsOutstandingException(
                    $invoice->invoice_number,
                    $data->amount,
                    $outstanding,
                );
            }

            $payment = Payment::create([
                'payment_number' => app(DocumentNumberGenerator::class)->next('PAY'),
                'sales_invoice_id' => $invoice->id,
                'amount' => Money::round($data->amount),
                'paid_at' => $data->paidAt,
                'payment_method' => $data->paymentMethod,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            $newPaidAmount = Money::add($invoice->paid_amount, $data->amount);
            $invoice->paid_amount = $newPaidAmount;
            $invoice->status = Money::compare($newPaidAmount, $invoice->total) >= 0 ? 'paid' : 'partial';
            $invoice->save();

            if ($data->postJournal) {
                app(PaymentJournalService::class)->postForPayment($payment, $invoice);
            }

            return $payment;
        });
    }
}
