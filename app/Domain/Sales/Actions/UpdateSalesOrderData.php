<?php

namespace App\Domain\Sales\Actions;

final class UpdateSalesOrderData
{
    /**
     * @param  array<int, array{product_id: string, quantity_ordered: string, unit_price: string, discount_rate: string, tax_rate: string}>  $items
     */
    public function __construct(
        public readonly string $salesOrderId,
        public readonly string $customerId,
        public readonly string $orderedAt,
        public readonly ?string $requiredAt,
        public readonly ?string $notes,
        public readonly array $items,
    ) {}
}
