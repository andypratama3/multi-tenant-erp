<?php

namespace App\Domain\Sales\Actions;

final readonly class CreateSalesReturnData
{
    /**
     * @param  array<int, array{sales_invoice_item_id: string, quantity: string, restock: bool}>  $items
     */
    public function __construct(
        public string $invoiceId,
        public string $warehouseId,
        public string $returnedAt,
        public string $reason,
        public array $items,
        public ?string $createdBy = null,
        public bool $postJournal = true,
    ) {}
}
