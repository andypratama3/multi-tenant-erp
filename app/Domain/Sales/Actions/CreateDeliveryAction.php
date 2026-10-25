<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesOrderItem;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateDeliveryAction
{
    public function execute(CreateDeliveryData $data): Delivery
    {
        return DB::transaction(function () use ($data): Delivery {
            /** @var SalesOrder $so */
            $so = SalesOrder::query()->with('items')->lockForUpdate()->findOrFail($data->salesOrderId);

            if (! in_array($so->status, ['confirmed', 'partial'], true)) {
                throw new InvalidDocumentStatusException(
                    "SO {$so->so_number}",
                    $so->status,
                    'confirmed/partial',
                );
            }

            $delivery = Delivery::create([
                'delivery_number' => app(DocumentNumberGenerator::class)->next('DO'),
                'sales_order_id' => $so->id,
                'warehouse_id' => $data->warehouseId,
                'status' => 'draft',
                'shipped_at' => $data->shippedAt,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            $requested = $data->items;

            if ($requested === []) {
                foreach ($so->items as $soItem) {
                    $remaining = bcsub(Money::decimal($soItem->quantity_ordered, 3), Money::decimal($soItem->quantity_delivered, 3), 3);

                    if (bccomp($remaining, '0', 3) === 1) {
                        $delivery->items()->create([
                            'sales_order_item_id' => $soItem->id,
                            'product_id' => $soItem->product_id,
                            'quantity_delivered' => $remaining,
                        ]);
                    }
                }
            } else {
                foreach ($requested as $item) {
                    /** @var SalesOrderItem|null $soItem */
                    $soItem = $so->items->firstWhere('id', $item['sales_order_item_id']);

                    if ($soItem === null) {
                        throw new InvalidArgumentException('Item pengiriman tidak berasal dari pesanan penjualan ini.');
                    }

                    $quantity = Money::decimal($item['quantity_delivered'], 3);
                    $remaining = bcsub(Money::decimal($soItem->quantity_ordered, 3), Money::decimal($soItem->quantity_delivered, 3), 3);

                    if (bccomp($quantity, '0', 3) !== 1) {
                        throw new InvalidArgumentException('Jumlah kirim harus lebih besar dari nol.');
                    }

                    if (bccomp($quantity, $remaining, 3) === 1) {
                        throw new InvalidArgumentException('Jumlah kirim melebihi sisa pesanan.');
                    }

                    $delivery->items()->create([
                        'sales_order_item_id' => $soItem->id,
                        'product_id' => $soItem->product_id,
                        'quantity_delivered' => $quantity,
                    ]);
                }
            }

            if ($delivery->items()->count() === 0) {
                throw new InvalidArgumentException('Tidak ada item outstanding untuk dikirim.');
            }

            return $delivery->load('items');
        });
    }
}
