<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Services\PurchaseNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrderAction
{
    public function __construct(
        private readonly ?PurchaseNumberGenerator $numberGenerator = null,
    ) {}

    public function execute(CreatePurchaseOrderData $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data): PurchaseOrder {
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

            $total = Money::add($subtotal, $taxAmount);

            $generator = $this->numberGenerator ?? app(PurchaseNumberGenerator::class);

            $po = PurchaseOrder::create([
                'po_number' => $generator->nextPoNumber(),
                'supplier_id' => $data->supplierId,
                'status' => 'draft',
                'ordered_at' => $data->orderedAt,
                'expected_at' => $data->expectedAt,
                'notes' => $data->notes,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'created_by' => $data->createdBy,
            ]);

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
