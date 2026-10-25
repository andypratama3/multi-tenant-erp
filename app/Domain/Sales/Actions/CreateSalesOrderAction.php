<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Sales\Models\SalesOrder;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class CreateSalesOrderAction
{
    public function execute(CreateSalesOrderData $data): SalesOrder
    {
        return DB::transaction(function () use ($data): SalesOrder {
            $subtotal = '0.00';
            $taxAmount = '0.00';
            $discountAmount = '0.00';
            $lines = [];

            foreach ($data->items as $item) {
                $lineBase = Money::mul($item['quantity_ordered'], $item['unit_price'], 4);
                $lineDiscount = Money::percentage($lineBase, $item['discount_rate']);
                $lineSubtotal = Money::round(Money::sub($lineBase, $lineDiscount));
                $lineTax = Money::percentage($lineSubtotal, $item['tax_rate']);
                $subtotal = Money::add($subtotal, $lineSubtotal);
                $discountAmount = Money::add($discountAmount, $lineDiscount);
                $taxAmount = Money::add($taxAmount, $lineTax);
                $lines[] = ['item' => $item, 'subtotal' => $lineSubtotal];
            }

            $total = Money::add($subtotal, $taxAmount);

            $so = SalesOrder::create([
                'so_number' => app(DocumentNumberGenerator::class)->next('SO'),
                'customer_id' => $data->customerId,
                'status' => 'draft',
                'ordered_at' => $data->orderedAt,
                'required_at' => $data->requiredAt,
                'notes' => $data->notes,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'created_by' => $data->createdBy,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'];
                $so->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'],
                    'discount_rate' => $item['discount_rate'],
                    'tax_rate' => $item['tax_rate'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            return $so->load('items');
        });
    }
}
