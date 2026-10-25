<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\SalesInvoice;
use App\Support\Money;

class PaymentJournalService
{
    public function __construct(
        private readonly PostJournalAction $journalAction,
        private readonly AccountResolver $accountResolver,
    ) {}

    /**
     * Post journal for a customer payment:
     *   Dr. Kas/Bank
     *   Cr. Piutang Usaha
     */
    public function postForPayment(Payment $payment, SalesInvoice $invoice): Journal
    {
        $invoice->loadMissing('customer');

        $accountKey = $payment->payment_method === 'cash' ? 'cash' : 'bank';
        $cashOrBank = $this->accountResolver->resolve($accountKey);
        $receivable = $this->accountResolver->resolve('receivable');
        $amount = Money::round($payment->amount);

        return $this->journalAction->execute(new PostJournalData(
            postedAt: $payment->paid_at->format('Y-m-d'),
            description: "Pembayaran Invoice #{$invoice->invoice_number}",
            lines: [
                [
                    'account_id' => $cashOrBank->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'description' => "Penerimaan pembayaran #{$payment->payment_number}",
                ],
                [
                    'account_id' => $receivable->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'description' => "Pelunasan piutang Invoice #{$invoice->invoice_number}",
                ],
            ],
            referenceType: Payment::class,
            referenceId: $payment->id,
            createdBy: $payment->created_by,
        ));
    }
}
