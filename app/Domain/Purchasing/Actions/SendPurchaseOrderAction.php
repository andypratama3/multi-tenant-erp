<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;

class SendPurchaseOrderAction
{
    public function execute(string $purchaseOrderId): PurchaseOrder
    {
        /** @var PurchaseOrder $po */
        $po = PurchaseOrder::query()->findOrFail($purchaseOrderId);

        if ($po->status !== 'draft') {
            throw new InvalidDocumentStatusException(
                "PO {$po->po_number}",
                $po->status,
                'draft',
            );
        }

        $po->status = 'sent';
        $po->save();

        return $po;
    }
}
