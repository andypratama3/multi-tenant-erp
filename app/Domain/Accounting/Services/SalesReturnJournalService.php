<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Sales\Models\SalesReturn;
use App\Support\Money;

class SalesReturnJournalService
{
    public function __construct(
        private readonly PostJournalAction $journalAction,
        private readonly AccountResolver $accountResolver,
    ) {}

    /**
     * Post journal for a sales return:
     *   Dr. Pendapatan Penjualan
     *   Dr. PPN Keluaran
     *   Cr. Piutang Usaha
     */
    public function postForReturn(SalesReturn $return): Journal
    {
        $revenue = $this->accountResolver->resolve('sales_revenue');
        $ppnOutput = $this->accountResolver->resolve('ppn_output');
        $receivable = $this->accountResolver->resolve('receivable');
        $lines = [
            [
                'account_id' => $revenue->id,
                'type' => 'debit',
                'amount' => Money::round($return->subtotal),
                'description' => "Retur penjualan {$return->return_number}",
            ],
        ];

        $creditToReceivable = Money::sub($return->total, $return->refund_amount);

        if (Money::isPositive($creditToReceivable)) {
            $lines[] = [
                'account_id' => $receivable->id,
                'type' => 'credit',
                'amount' => Money::round($creditToReceivable),
                'description' => "Pengurangan piutang {$return->return_number}",
            ];
        }

        if (Money::isPositive($return->refund_amount)) {
            $refundAccount = $this->accountResolver->resolve(
                $return->refund_method === 'cash' ? 'cash' : 'bank',
            );

            $lines[] = [
                'account_id' => $refundAccount->id,
                'type' => 'credit',
                'amount' => Money::round($return->refund_amount),
                'description' => "Pengembalian dana {$return->return_number}",
            ];
        }

        if (Money::isPositive($return->tax_amount)) {
            $lines[] = [
                'account_id' => $ppnOutput->id,
                'type' => 'debit',
                'amount' => Money::round($return->tax_amount),
                'description' => "Pengurangan PPN keluaran {$return->return_number}",
            ];
        }

        return $this->journalAction->execute(new PostJournalData(
            postedAt: $return->returned_at->format('Y-m-d'),
            description: "Retur penjualan #{$return->return_number}",
            lines: $lines,
            referenceType: SalesReturn::class,
            referenceId: $return->id,
            createdBy: $return->created_by,
        ));
    }
}
