<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdatePurchaseOrderAction
{
    public function execute(UpdatePurchaseOrderData $data): PurchaseOrder
    {
        if ($data->items === []) {
            throw new InvalidArgumentException('Pesanan pembelian harus memiliki minimal satu item.');
        }

        return DB::transaction(function () use ($data): PurchaseOrder {
            /** @var PurchaseOrder $po */
            $po = PurchaseOrder::query()->lockForUpdate()->findOrFail($data->purchaseOrderId);

            if ($po->status !== 'draft') {
                throw new InvalidDocumentStatusException(
                    "PO {$po->po_number}",
                    $po->status,
                    'draft',
                );
            }

            $subtotal = '0.00';
            $taxAmount = '0.00';
            $lines = [];

            foreach ($data->items as $item) {
                $lineSubtotal = Money::round(Money::mul($item['quantity_ordered'], $item['unit_price'], 4));
                $lineTax = Money::percentage($lineSubtotal, $item['tax_rate']);
                $subtotal = Money::add($subtotal, $lineSubtotal);
                $taxAmount = Money::add($taxAmount, $lineTax);
                $lines[] = ['item' => $item, 'subtotal' => $lineSubtotal];
            }

            $po->fill([
                'supplier_id' => $data->supplierId,
                'ordered_at' => $data->orderedAt,
                'expected_at' => $data->expectedAt,
                'notes' => $data->notes,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => Money::add($subtotal, $taxAmount),
            ]);
            $po->save();

            $po->items()->delete();

            foreach ($lines as $line) {
                $item = $line['item'];
                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => '0.000',
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            return $po->load('items');
        });
    }
}
