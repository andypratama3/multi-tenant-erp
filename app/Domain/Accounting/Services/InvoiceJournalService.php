<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Sales\Models\SalesInvoice;

class InvoiceJournalService
{
    public function __construct(
        private readonly PostJournalAction $journalAction,
        private readonly AccountResolver $accountResolver,
    ) {}

    /**
     * Post journal for an issued sales invoice:
     *   Dr. Piutang Usaha                    (total inc. tax)
     *   Cr. Pendapatan Penjualan             (subtotal net)
     *   Cr. Utang Pajak PPN Keluaran         (tax_amount, if > 0)
     */
    public function postForInvoice(SalesInvoice $invoice): Journal
    {
        $invoice->load('customer');

        $receivable = $this->accountResolver->resolve('receivable');
        $revenue = $this->accountResolver->resolve('sales_revenue');
        $ppnOutput = $this->accountResolver->resolve('ppn_output');

        $total = (float) $invoice->total;
        $subtotal = (float) $invoice->subtotal;
        $taxAmount = (float) $invoice->tax_amount;

        $lines = [
            [
                'account_id' => $receivable->id,
                'type' => 'debit',
                'amount' => number_format($total, 2, '.', ''),
                'description' => "Piutang Invoice #{$invoice->invoice_number}",
            ],
            [
                'account_id' => $revenue->id,
                'type' => 'credit',
                'amount' => number_format($subtotal, 2, '.', ''),
                'description' => "Penjualan Invoice #{$invoice->invoice_number}",
            ],
        ];

        if ($taxAmount > 0.001) {
            $lines[] = [
                'account_id' => $ppnOutput->id,
                'type' => 'credit',
                'amount' => number_format($taxAmount, 2, '.', ''),
                'description' => "PPN Keluaran Invoice #{$invoice->invoice_number}",
            ];
        }

        return $this->journalAction->execute(new PostJournalData(
            postedAt: $invoice->issued_at->format('Y-m-d'),
            description: "Penjualan Invoice #{$invoice->invoice_number}",
            lines: $lines,
            referenceType: SalesInvoice::class,
            referenceId: $invoice->id,
        ));
    }
}
