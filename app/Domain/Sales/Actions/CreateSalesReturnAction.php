<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Services\SalesReturnJournalService;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Models\Product;
use App\Domain\Sales\Exceptions\ReturnQuantityExceedsInvoiceException;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesInvoiceItem;
use App\Domain\Sales\Models\SalesReturn;
use App\Domain\Sales\Models\SalesReturnItem;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateSalesReturnAction
{
    public function __construct(
        private readonly RecordStockMovementAction $stockMovement,
    ) {}

    public function execute(CreateSalesReturnData $data): SalesReturn
    {
        return DB::transaction(function () use ($data): SalesReturn {
            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::query()
                ->with('items.product')
                ->lockForUpdate()
                ->findOrFail($data->invoiceId);

            if ($invoice->status === 'cancelled') {
                throw new InvalidArgumentException('Invoice yang dibatalkan tidak dapat diretur.');
            }

            if ($data->items === []) {
                throw new InvalidArgumentException('Retur harus memiliki minimal satu item.');
            }

            $subtotal = '0.00';
            $taxAmount = '0.00';
            $total = '0.00';
            $lines = [];

            foreach ($data->items as $row) {
                /** @var SalesInvoiceItem|null $invoiceItem */
                $invoiceItem = $invoice->items->firstWhere('id', $row['sales_invoice_item_id']);

                if ($invoiceItem === null) {
                    throw new InvalidArgumentException('Item invoice tidak ditemukan pada invoice yang dipilih.');
                }

                $product = $invoiceItem->product;

                if ($product === null) {
                    throw new InvalidArgumentException('Produk pada item invoice tidak ditemukan.');
                }

                $quantity = Money::round($row['quantity'], 3);

                if (! Money::isPositive($quantity)) {
                    throw new InvalidArgumentException('Quantity retur harus lebih besar dari nol.');
                }

                $alreadyReturned = SalesReturnItem::query()
                    ->where('sales_invoice_item_id', $invoiceItem->id)
                    ->sum('quantity');

                $available = Money::sub($invoiceItem->quantity, (string) $alreadyReturned);

                if (Money::compare($quantity, $available) === 1) {
                    throw new ReturnQuantityExceedsInvoiceException(
                        $product->name,
                        $quantity,
                        $available,
                    );
                }

                $lineSubtotal = Money::round(Money::mul($quantity, $invoiceItem->unit_price, 4));
                $lineTax = Money::round(Money::mul($lineSubtotal, Money::div($invoiceItem->tax_rate, '100', 6), 4));
                $lineTotal = Money::add($lineSubtotal, $lineTax);

                $subtotal = Money::add($subtotal, $lineSubtotal);
                $taxAmount = Money::add($taxAmount, $lineTax);
                $total = Money::add($total, $lineTotal);

                $lines[] = [
                    'invoice_item' => $invoiceItem,
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $lineSubtotal,
                    'tax_amount' => $lineTax,
                    'total' => $lineTotal,
                    'restock' => (bool) $row['restock'],
                ];
            }

            $return = SalesReturn::create([
                'return_number' => app(DocumentNumberGenerator::class)->next('SR'),
                'sales_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'warehouse_id' => $data->warehouseId,
                'returned_at' => $data->returnedAt,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'reason' => $data->reason,
                'created_by' => $data->createdBy,
            ]);

            foreach ($lines as $line) {
                /** @var SalesInvoiceItem $invoiceItem */
                $invoiceItem = $line['invoice_item'];
                /** @var Product $product */
                $product = $line['product'];

                SalesReturnItem::create([
                    'sales_return_id' => $return->id,
                    'sales_invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $invoiceItem->unit_price,
                    'tax_rate' => $invoiceItem->tax_rate,
                    'subtotal' => $line['subtotal'],
                    'tax_amount' => $line['tax_amount'],
                    'total' => $line['total'],
                    'restock' => $line['restock'],
                ]);

                if ($line['restock']) {
                    $this->stockMovement->execute(new RecordStockMovementData(
                        productId: $invoiceItem->product_id,
                        warehouseId: $data->warehouseId,
                        movementType: 'in',
                        quantity: $line['quantity'],
                        unitCost: $product->cost_price,
                        referenceType: SalesReturn::class,
                        referenceId: $return->id,
                        notes: "Retur penjualan {$return->return_number}",
                        createdBy: $data->createdBy,
                    ));
                }
            }

            $invoice->returned_amount = Money::add($invoice->returned_amount, $total);
            $invoice->save();

            if ($data->postJournal) {
                app(SalesReturnJournalService::class)->postForReturn($return);
            }

            return $return->load(['customer', 'warehouse', 'items.product', 'invoice']);
        });
    }
}
