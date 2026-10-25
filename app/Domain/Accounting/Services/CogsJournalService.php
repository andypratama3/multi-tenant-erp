<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Sales\Models\Delivery;
use App\Support\Money;

class CogsJournalService
{
    public function __construct(
        private readonly PostJournalAction $journalAction,
        private readonly AccountResolver $accountResolver,
    ) {}

    /**
     * Post COGS for a delivery:
     *   Dr. Harga Pokok Penjualan
     *   Cr. Persediaan Barang
     */
    public function postForDelivery(Delivery $delivery): ?Journal
    {
        if ($delivery->warehouse_id === null) {
            return null;
        }

        $delivery->loadMissing('items');

        $totalCost = '0.00';

        foreach ($delivery->items as $item) {
            $balance = StockBalance::query()
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $delivery->warehouse_id)
                ->first();

            $unitCost = $balance === null ? '0.0000' : $balance->average_cost;
            $totalCost = Money::add($totalCost, Money::mul($item->quantity_delivered, $unitCost, 4));
        }

        $totalCost = Money::round($totalCost);

        if (! Money::isPositive($totalCost)) {
            return null;
        }

        $cogs = $this->accountResolver->resolve('cogs');
        $inventory = $this->accountResolver->resolve('inventory');

        return $this->journalAction->execute(new PostJournalData(
            postedAt: ($delivery->shipped_at ?? now())->format('Y-m-d'),
            description: "Harga pokok pengiriman DO #{$delivery->delivery_number}",
            lines: [
                [
                    'account_id' => $cogs->id,
                    'type' => 'debit',
                    'amount' => $totalCost,
                    'description' => "HPP DO #{$delivery->delivery_number}",
                ],
                [
                    'account_id' => $inventory->id,
                    'type' => 'credit',
                    'amount' => $totalCost,
                    'description' => "Pengurangan persediaan DO #{$delivery->delivery_number}",
                ],
            ],
            referenceType: Delivery::class,
            referenceId: $delivery->id,
        ));
    }
}
