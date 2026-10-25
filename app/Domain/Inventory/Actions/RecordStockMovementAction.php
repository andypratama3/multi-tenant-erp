<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\StockLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecordStockMovementAction
{
    public function execute(RecordStockMovementData $data): StockLedger
    {
        return DB::transaction(function () use ($data): StockLedger {
            $balance = StockBalance::firstOrCreate(
                [
                    'product_id' => $data->productId,
                    'warehouse_id' => $data->warehouseId,
                ],
                [
                    'quantity' => '0.000',
                    'average_cost' => '0.0000',
                ]
            );

            $currentQty = (float) $balance->quantity;
            $incomingQty = (float) $data->quantity;
            $currentAvg = (float) $balance->average_cost;
            $unitCost = (float) $data->unitCost;

            // For 'out' movements and negative adjustments: check sufficient stock
            if ($data->movementType === 'out') {
                if ($incomingQty > $currentQty) {
                    throw new InsufficientStockException(
                        $data->productId,
                        $data->warehouseId,
                        $data->quantity,
                        $balance->quantity
                    );
                }
                $newQty = $currentQty - $incomingQty;
                $newAvg = $currentAvg; // avg does not change on out
            } elseif ($data->movementType === 'adjustment') {
                // For adjustment, quantity can represent a delta (negative means subtract)
                $newQty = $currentQty + $incomingQty;
                if ($newQty < 0) {
                    throw new InsufficientStockException(
                        $data->productId,
                        $data->warehouseId,
                        $data->quantity,
                        $balance->quantity
                    );
                }
                $newAvg = $currentAvg;
            } else {
                // 'in' or 'opening' — moving average recalculation (ADR 0005)
                if (($currentQty + $incomingQty) > 0) {
                    $newAvg = (($currentQty * $currentAvg) + ($incomingQty * $unitCost))
                        / ($currentQty + $incomingQty);
                } else {
                    $newAvg = $unitCost;
                }
                $newQty = $currentQty + $incomingQty;
            }

            $newQtyStr = number_format($newQty, 3, '.', '');
            $newAvgStr = number_format($newAvg, 4, '.', '');

            $balance->quantity = $newQtyStr;
            if ($data->movementType === 'in' || $data->movementType === 'opening') {
                $balance->average_cost = $newAvgStr;
            }
            $balance->last_movement_at = Carbon::now();
            $balance->save();

            $ledger = new StockLedger([
                'product_id' => $data->productId,
                'warehouse_id' => $data->warehouseId,
                'movement_type' => $data->movementType,
                'reference_type' => $data->referenceType,
                'reference_id' => $data->referenceId,
                'quantity' => $data->quantity,
                'unit_cost' => $data->unitCost,
                'running_balance' => $newQtyStr,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);
            $ledger->save();

            return $ledger;
        });
    }
}
