<?php

namespace App\Domain\Sales\Actions;

final class CreateDeliveryData
{
    /**
     * @param  array<int, array{sales_order_item_id: string, quantity_delivered: string}>  $items
     */
    public function __construct(
        public readonly string $salesOrderId,
        public readonly string $warehouseId,
        public readonly ?string $shippedAt,
        public readonly ?string $notes,
        public readonly ?string $createdBy,
        public readonly array $items = [],
    ) {}
}
