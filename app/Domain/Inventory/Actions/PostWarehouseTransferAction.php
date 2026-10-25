<?php

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Exceptions\DocumentAlreadyPostedException;
use App\Domain\Inventory\Models\WarehouseTransfer;
use App\Domain\Inventory\Services\StockLotService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostWarehouseTransferAction
{
    public function __construct(private readonly RecordStockMovementAction $stockMovement) {}

    public function execute(string $transferId, ?string $postedBy = null): WarehouseTransfer
    {
        return DB::transaction(function () use ($transferId, $postedBy): WarehouseTransfer {
            /** @var WarehouseTransfer $transfer */
            $transfer = WarehouseTransfer::query()->with('items')->lockForUpdate()->findOrFail($transferId);

            if ($transfer->status === 'posted') {
                throw new DocumentAlreadyPostedException($transfer->transfer_number);
            }

            if ($transfer->from_warehouse_id === $transfer->to_warehouse_id) {
                throw new InvalidArgumentException('Gudang asal dan tujuan harus berbeda.');
            }

            if ($transfer->items->isEmpty()) {
                throw new InvalidArgumentException('Transfer tidak memiliki item.');
            }

            foreach ($transfer->items as $item) {
                $outboundLedger = $this->stockMovement->execute(new RecordStockMovementData(
                    productId: $item->product_id,
                    warehouseId: $transfer->from_warehouse_id,
                    movementType: 'out',
                    quantity: $item->quantity,
                    unitCost: $item->unit_cost,
                    referenceType: WarehouseTransfer::class,
                    referenceId: $transfer->id,
                    notes: "Transfer keluar {$transfer->transfer_number}",
                    createdBy: $postedBy,
                ));

                $lots = app(StockLotService::class)->snapshotsForLedger($outboundLedger->id);

                $this->stockMovement->execute(new RecordStockMovementData(
                    productId: $item->product_id,
                    warehouseId: $transfer->to_warehouse_id,
                    movementType: 'in',
                    quantity: $item->quantity,
                    unitCost: $item->unit_cost,
                    referenceType: WarehouseTransfer::class,
                    referenceId: $transfer->id,
                    notes: "Transfer masuk {$transfer->transfer_number}",
                    createdBy: $postedBy,
                    lots: $lots,
                ));
            }

            $transfer->status = 'posted';
            $transfer->posted_by = $postedBy;
            $transfer->posted_at = now();
            $transfer->save();

            return $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);
        });
    }
}
