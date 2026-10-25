<?php

namespace App\Domain\Inventory\Actions;

final class RecordStockMovementData
{
    /**
     * @param  array<int, array{lot_number?: string|null, expiry_date?: string|null, quantity: string, unit_cost?: string|null}>  $lots
     */
    public function __construct(
        public readonly string $productId,
        public readonly string $warehouseId,
        public readonly string $movementType, // 'in'|'out'|'adjustment'|'opening'
        public readonly string $quantity,     // positive decimal string e.g. "10.000"
        public readonly string $unitCost,     // decimal string e.g. "5000.0000"
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId = null,
        public readonly ?string $notes = null,
        public readonly ?string $createdBy = null,
        public readonly ?string $receivedAt = null,
        public readonly ?string $expiryDate = null,
        public readonly ?string $lotNumber = null,
        public readonly bool $allowExpired = false,
        public readonly array $lots = [],
    ) {}
}
