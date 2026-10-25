<?php

namespace App\Domain\Purchasing\Actions;

final class CreateGoodsReceiptNoteData
{
    /**
     * @param  array<int, array{purchase_order_item_id: string, quantity_received: string, unit_cost?: string|null}>  $items
     */
    public function __construct(
        public readonly string $purchaseOrderId,
        public readonly string $warehouseId,
        public readonly string $receivedAt,
        public readonly ?string $notes,
        public readonly ?string $createdBy,
        public readonly array $items,
    ) {}
}
