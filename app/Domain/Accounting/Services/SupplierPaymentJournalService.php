<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Purchasing\Models\SupplierPayment;
use App\Support\Money;

class SupplierPaymentJournalService
{
    public function __construct(
        private readonly PostJournalAction $journalAction,
        private readonly AccountResolver $accountResolver,
    ) {}

    /**
     * Post journal for a supplier payment:
     *   Dr. Utang Usaha
     *   Cr. Kas/Bank
     */
    public function postForPayment(SupplierPayment $payment, PurchaseInvoice $invoice): Journal
    {
        $invoice->loadMissing('supplier');
        $supplier = $invoice->supplier;
        $supplierName = $supplier !== null ? $supplier->name : '-';

        $accountKey = $payment->payment_method === 'cash' ? 'cash' : 'bank';
        $cashOrBank = $this->accountResolver->resolve($accountKey);
        $payable = $this->accountResolver->resolve('payable');
        $amount = Money::round($payment->amount);

        return $this->journalAction->execute(new PostJournalData(
            postedAt: $payment->paid_at->format('Y-m-d'),
            description: "Pembayaran Faktur Pembelian #{$invoice->ap_number}",
            lines: [
                [
                    'account_id' => $payable->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'description' => 'Pelunasan utang '.$supplierName,
                ],
                [
                    'account_id' => $cashOrBank->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'description' => "Pembayaran supplier #{$payment->payment_number}",
                ],
            ],
            referenceType: SupplierPayment::class,
            referenceId: $payment->id,
            createdBy: $payment->created_by,
        ));
    }
}
