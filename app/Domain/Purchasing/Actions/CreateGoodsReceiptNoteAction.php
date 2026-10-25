<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Exceptions\GrnQuantityExceedsOrderedException;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderItem;
use App\Domain\Purchasing\Services\PurchaseNumberGenerator;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateGoodsReceiptNoteAction
{
    public function __construct(
        private readonly ?PurchaseNumberGenerator $numberGenerator = null,
    ) {}

    public function execute(CreateGoodsReceiptNoteData $data): GoodsReceiptNote
    {
        if ($data->items === []) {
            throw new InvalidArgumentException('Penerimaan barang harus memiliki minimal satu item.');
        }

        return DB::transaction(function () use ($data): GoodsReceiptNote {
            /** @var PurchaseOrder $po */
            $po = PurchaseOrder::query()->with('items.product')->lockForUpdate()->findOrFail($data->purchaseOrderId);

            if (! in_array($po->status, ['sent', 'partial'], true)) {
                throw new InvalidDocumentStatusException(
                    "PO {$po->po_number}",
                    $po->status,
                    'sent/partial',
                );
            }

            $generator = $this->numberGenerator ?? app(PurchaseNumberGenerator::class);

            $grn = GoodsReceiptNote::create([
                'grn_number' => $generator->nextGrnNumber(),
                'purchase_order_id' => $po->id,
                'received_at' => $data->receivedAt,
                'warehouse_id' => $data->warehouseId,
                'notes' => $data->notes,
                'status' => 'draft',
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->items as $item) {
                /** @var PurchaseOrderItem|null $poItem */
                $poItem = $po->items->firstWhere('id', $item['purchase_order_item_id']);

                if ($poItem === null) {
                    throw new InvalidArgumentException('Item penerimaan tidak berasal dari pesanan pembelian ini.');
                }

                $quantity = Money::decimal($item['quantity_received'], 3);

                if (bccomp($quantity, '0', 3) !== 1) {
                    throw new InvalidArgumentException('Jumlah diterima harus lebih besar dari nol.');
                }

                $remaining = bcsub(Money::decimal($poItem->quantity_ordered, 3), Money::decimal($poItem->quantity_received, 3), 3);

                if (bccomp($quantity, $remaining, 3) === 1) {
                    throw new GrnQuantityExceedsOrderedException(
                        $poItem->product->name ?? $poItem->product_id,
                        $quantity,
                        $remaining,
                    );
                }

                $grn->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'quantity_received' => $quantity,
                    'unit_cost' => $item['unit_cost'] ?? $poItem->unit_price,
                    'lot_number' => $item['lot_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);
            }

            return $grn->load('items');
        });
    }
}
