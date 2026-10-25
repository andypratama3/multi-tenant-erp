<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Accounting\Services\GrnJournalService;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Purchasing\Exceptions\GrnAlreadyPostedException;
use App\Domain\Purchasing\Exceptions\GrnQuantityExceedsOrderedException;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderItem;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostGoodsReceiptNoteAction
{
    public function __construct(
        private readonly RecordStockMovementAction $stockMovement,
    ) {}

    public function execute(PostGrnData $data): GoodsReceiptNote
    {
        return DB::transaction(function () use ($data): GoodsReceiptNote {
            /** @var GoodsReceiptNote $grn */
            $grn = GoodsReceiptNote::query()
                ->with(['items.poItem.product', 'purchaseOrder.items'])
                ->lockForUpdate()
                ->findOrFail($data->grnId);

            if ($grn->status === 'posted') {
                throw new GrnAlreadyPostedException($grn->grn_number);
            }

            if ($grn->items->isEmpty()) {
                throw new InvalidArgumentException('GRN tidak memiliki item untuk diposting.');
            }

            if ($grn->warehouse_id === null) {
                throw new InvalidArgumentException('Gudang penerimaan wajib dipilih sebelum posting.');
            }

            foreach ($grn->items as $item) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $item->poItem;
                $remaining = bcsub(Money::decimal($poItem->quantity_ordered, 3), Money::decimal($poItem->quantity_received, 3), 3);

                if (bccomp(Money::decimal($item->quantity_received, 3), $remaining, 3) === 1) {
                    throw new GrnQuantityExceedsOrderedException(
                        $poItem->product->name ?? $poItem->product_id,
                        Money::decimal($item->quantity_received, 3),
                        $remaining,
                    );
                }

                $newQtyReceived = bcadd(
                    Money::decimal($poItem->quantity_received, 3),
                    Money::decimal($item->quantity_received, 3),
                    3,
                );

                $poItem->quantity_received = $newQtyReceived;
                $poItem->save();

                $this->stockMovement->execute(new RecordStockMovementData(
                    productId: $item->product_id,
                    warehouseId: $grn->warehouse_id,
                    movementType: 'in',
                    quantity: Money::decimal($item->quantity_received, 3),
                    unitCost: (string) $item->unit_cost,
                    referenceType: GoodsReceiptNote::class,
                    referenceId: $grn->id,
                    createdBy: $grn->created_by,
                    receivedAt: $grn->received_at->toDateString(),
                    expiryDate: $item->expiry_date?->toDateString(),
                    lotNumber: $item->lot_number,
                ));
            }

            /** @var PurchaseOrder $po */
            $po = $grn->purchaseOrder;
            $po->load('items');
            $allReceived = $po->items->every(
                fn ($poItem) => bccomp(Money::decimal($poItem->quantity_received, 3), Money::decimal($poItem->quantity_ordered, 3), 3) >= 0
            );

            $po->status = $allReceived ? 'received' : 'partial';
            if ($allReceived) {
                $po->received_at = Carbon::parse(now()->toDateString());
            }
            $po->save();

            $grn->status = 'posted';
            $grn->save();

            if ($data->postJournal) {
                $freshGrn = $grn->fresh();
                if ($freshGrn !== null) {
                    app(GrnJournalService::class)->postForGrn($freshGrn);
                }
            }

            return $grn->refresh()->load('items');
        });
    }
}
