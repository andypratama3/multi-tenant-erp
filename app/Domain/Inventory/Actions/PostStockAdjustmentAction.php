<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Exceptions\DocumentAlreadyPostedException;
use App\Domain\Inventory\Models\StockAdjustment;
use App\Domain\Inventory\Models\StockBalance;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostStockAdjustmentAction
{
    public function __construct(private readonly RecordStockMovementAction $stockMovement) {}

    public function execute(string $adjustmentId, ?string $postedBy = null): StockAdjustment
    {
        return DB::transaction(function () use ($adjustmentId, $postedBy): StockAdjustment {
            /** @var StockAdjustment $adjustment */
            $adjustment = StockAdjustment::query()->with('items')->lockForUpdate()->findOrFail($adjustmentId);

            if ($adjustment->status === 'posted') {
                throw new DocumentAlreadyPostedException($adjustment->adjustment_number);
            }

            if ($adjustment->items->isEmpty()) {
                throw new InvalidArgumentException('Stock opname tidak memiliki item.');
            }

            foreach ($adjustment->items as $item) {
                $balance = StockBalance::query()
                    ->where('product_id', $item->product_id)
                    ->where('warehouse_id', $adjustment->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                $systemQuantity = $balance !== null ? $balance->quantity : '0.000';
                $difference = Money::sub($item->counted_quantity, $systemQuantity, 3);

                $item->system_quantity = $systemQuantity;
                $item->difference_quantity = $difference;
                $item->save();

                if (Money::compare($difference, '0') !== 0) {
                    $this->stockMovement->execute(new RecordStockMovementData(
                        productId: $item->product_id,
                        warehouseId: $adjustment->warehouse_id,
                        movementType: 'adjustment',
                        quantity: $difference,
                        unitCost: $item->unit_cost,
                        referenceType: StockAdjustment::class,
                        referenceId: $adjustment->id,
                        notes: "Stock opname {$adjustment->adjustment_number}",
                        createdBy: $postedBy,
                    ));
                }
            }

            $adjustment->status = 'posted';
            $adjustment->posted_by = $postedBy;
            $adjustment->posted_at = now();
            $adjustment->save();

            return $adjustment->load(['warehouse', 'items.product']);
        });
    }
}
