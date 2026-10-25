<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Exceptions\PurchaseInvoiceAlreadyExistsException;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreatePurchaseInvoiceFromGrnAction
{
    public function execute(CreatePurchaseInvoiceFromGrnData $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($data): PurchaseInvoice {
            /** @var GoodsReceiptNote $grn */
            $grn = GoodsReceiptNote::query()
                ->with(['items', 'purchaseOrder.supplier'])
                ->lockForUpdate()
                ->findOrFail($data->grnId);

            if ($grn->status !== 'posted') {
                throw new InvalidDocumentStatusException("GRN {$grn->grn_number}", $grn->status, 'posted');
            }

            if (PurchaseInvoice::query()->where('grn_id', $grn->id)->exists()) {
                throw new PurchaseInvoiceAlreadyExistsException($grn->grn_number);
            }

            if ($grn->items->isEmpty()) {
                throw new InvalidArgumentException('GRN tidak memiliki item untuk ditagihkan.');
            }

            $purchaseOrder = $grn->purchaseOrder;

            if ($purchaseOrder === null) {
                throw new InvalidArgumentException('GRN tidak memiliki purchase order.');
            }

            $subtotal = '0.00';

            foreach ($grn->items as $item) {
                $subtotal = Money::add($subtotal, Money::round(Money::mul($item->quantity_received, $item->unit_cost, 4)));
            }

            $invoice = PurchaseInvoice::create([
                'ap_number' => app(DocumentNumberGenerator::class)->next('PI'),
                'supplier_invoice_number' => $data->supplierInvoiceNumber,
                'grn_id' => $grn->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'status' => 'posted',
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'paid_amount' => '0.00',
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            return $invoice->load(['supplier', 'grn']);
        });
    }
}
