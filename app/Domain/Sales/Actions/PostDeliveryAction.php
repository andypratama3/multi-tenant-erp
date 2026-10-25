<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Services\CogsJournalService;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Sales\Exceptions\DeliveryAlreadyPostedException;
use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesOrderItem;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostDeliveryAction
{
    public function __construct(
        private readonly RecordStockMovementAction $stockMovement,
    ) {}

    public function execute(PostDeliveryData $data): Delivery
    {
        return DB::transaction(function () use ($data): Delivery {
            /** @var Delivery $delivery */
            $delivery = Delivery::query()
                ->with(['items.soItem', 'salesOrder.items'])
                ->lockForUpdate()
                ->findOrFail($data->deliveryId);

            if ($delivery->status !== 'draft') {
                if ($delivery->status === 'shipped' || $delivery->status === 'delivered') {
                    throw new DeliveryAlreadyPostedException($delivery->delivery_number);
                }

                throw new InvalidArgumentException('Pengiriman berstatus '.$delivery->status.' tidak dapat diposting.');
            }

            if ($delivery->warehouse_id === null) {
                throw new InvalidArgumentException('Gudang pengiriman wajib dipilih sebelum posting.');
            }

            if ($delivery->items->isEmpty()) {
                throw new InvalidArgumentException('Pengiriman tidak memiliki item.');
            }

            foreach ($delivery->items as $item) {
                /** @var SalesOrderItem $soItem */
                $soItem = $item->soItem;
                $remaining = bcsub(Money::decimal($soItem->quantity_ordered, 3), Money::decimal($soItem->quantity_delivered, 3), 3);

                if (bccomp(Money::decimal($item->quantity_delivered, 3), $remaining, 3) === 1) {
                    throw new InvalidArgumentException('Jumlah kirim melebihi sisa pesanan untuk produk '.$item->product_id.'.');
                }

                $balance = StockBalance::query()
                    ->where('product_id', $item->product_id)
                    ->where('warehouse_id', $delivery->warehouse_id)
                    ->first();

                $unitCost = $balance === null ? '0.0000' : $balance->average_cost;

                $this->stockMovement->execute(new RecordStockMovementData(
                    productId: $item->product_id,
                    warehouseId: $delivery->warehouse_id,
                    movementType: 'out',
                    quantity: Money::decimal($item->quantity_delivered, 3),
                    unitCost: $unitCost,
                    referenceType: Delivery::class,
                    referenceId: $delivery->id,
                    createdBy: $delivery->created_by,
                ));

                $soItem->quantity_delivered = bcadd(
                    Money::decimal($soItem->quantity_delivered, 3),
                    Money::decimal($item->quantity_delivered, 3),
                    3,
                );
                $soItem->save();
            }

            $delivery->status = 'shipped';
            $delivery->shipped_at = $delivery->shipped_at ?? Carbon::parse(now()->toDateString());
            $delivery->save();

            if ($data->postJournal) {
                $freshDelivery = $delivery->fresh('items');
                if ($freshDelivery !== null) {
                    app(CogsJournalService::class)->postForDelivery($freshDelivery);
                }
            }

            /** @var SalesOrder $so */
            $so = SalesOrder::query()->findOrFail($delivery->sales_order_id);
            $so->load('items');
            $allDelivered = $so->items->every(
                fn (SalesOrderItem $soItem) => bccomp(Money::decimal($soItem->quantity_delivered, 3), Money::decimal($soItem->quantity_ordered, 3), 3) >= 0
            );

            $so->status = $allDelivered ? 'fulfilled' : 'partial';
            $so->save();

            $delivery->refresh();

            return $delivery->load('items');
        });
    }
}
