<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelPurchaseOrderAction
{
    public function execute(string $purchaseOrderId): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrderId): PurchaseOrder {
            /** @var PurchaseOrder $po */
            $po = PurchaseOrder::query()->with('items')->lockForUpdate()->findOrFail($purchaseOrderId);

            if (! in_array($po->status, ['draft', 'sent'], true)) {
                throw new InvalidDocumentStatusException(
                    "PO {$po->po_number}",
                    $po->status,
                    'draft/sent',
                );
            }

            $hasReceipts = $po->items->contains(
                fn ($item) => bccomp(Money::decimal($item->quantity_received, 3), '0', 3) === 1
            );

            if ($hasReceipts) {
                throw new InvalidArgumentException('PO yang sudah memiliki penerimaan tidak dapat dibatalkan.');
            }

            $po->status = 'cancelled';
            $po->save();

            return $po;
        });
    }
}
