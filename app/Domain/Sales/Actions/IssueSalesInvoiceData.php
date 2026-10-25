<?php

namespace App\Domain\Sales\Actions;

final class IssueSalesInvoiceData
{
    /**
     * @param  array<int, array{sales_order_item_id?: string|null, product_id: string, quantity: string, unit_price: string, discount_rate: string, tax_rate: string}>  $items
     */
    public function __construct(
        public readonly ?string $salesOrderId,
        public readonly string $customerId,
        public readonly string $issuedAt,
        public readonly string $dueAt,
        public readonly ?string $createdBy,
        public readonly array $items,
        public readonly bool $postJournal = false,
        public readonly ?string $notes = null,
    ) {}
}
