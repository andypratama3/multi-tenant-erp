<?php

namespace App\Domain\Purchasing\Actions;

final class UpdatePurchaseOrderData
{
    /**
     * @param  array<int, array{product_id: string, quantity_ordered: string, unit_price: string, tax_rate: string}>  $items
     */
    public function __construct(
        public readonly string $purchaseOrderId,
        public readonly string $supplierId,
        public readonly string $orderedAt,
        public readonly ?string $expectedAt,
        public readonly ?string $notes,
        public readonly array $items,
    ) {}
}
