<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\Supplier;

class GrnJournalService
{
    public function __construct(
        private readonly PostJournalAction $journalAction,
        private readonly AccountResolver $accountResolver,
    ) {}

    /**
     * Post a double-entry journal for a posted GRN:
     *   Dr. Persediaan Barang  (total value of goods received)
     *   Cr. Utang Usaha        (same amount)
     */
    public function postForGrn(GoodsReceiptNote $grn): Journal
    {
        $grn->load(['items', 'purchaseOrder.supplier']);

        $totalValue = 0.0;
        foreach ($grn->items as $item) {
            $totalValue += (float) $item->quantity_received * (float) $item->unit_cost;
        }

        $inventory = $this->accountResolver->resolve('inventory');
        $payable = $this->accountResolver->resolve('payable');

        /** @var PurchaseOrder $po */
        $po = $grn->purchaseOrder;

        /** @var Supplier $supplier */
        $supplier = $po->supplier;

        return $this->journalAction->execute(new PostJournalData(
            postedAt: $grn->received_at->format('Y-m-d'),
            description: "Penerimaan barang GRN #{$grn->grn_number}",
            lines: [
                [
                    'account_id' => $inventory->id,
                    'type' => 'debit',
                    'amount' => number_format($totalValue, 2, '.', ''),
                    'description' => "Persediaan dari GRN #{$grn->grn_number}",
                ],
                [
                    'account_id' => $payable->id,
                    'type' => 'credit',
                    'amount' => number_format($totalValue, 2, '.', ''),
                    'description' => "Utang ke {$supplier->name}",
                ],
            ],
            referenceType: GoodsReceiptNote::class,
            referenceId: $grn->id,
        ));
    }
}
