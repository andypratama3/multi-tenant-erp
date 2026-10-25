<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Services\InvoiceJournalService;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Tax\Actions\RecordTaxTransactionAction;
use App\Domain\Tax\Actions\RecordTaxTransactionData;
use App\Domain\Tax\Models\TaxRate;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IssueSalesInvoiceAction
{
    public function __construct(
        private readonly RecordTaxTransactionAction $taxAction = new RecordTaxTransactionAction,
    ) {}

    public function execute(IssueSalesInvoiceData $data): SalesInvoice
    {
        return DB::transaction(function () use ($data): SalesInvoice {
            $subtotal = '0.00';
            $taxAmount = '0.00';
            $discountAmount = '0.00';
            $lines = [];

            foreach ($data->items as $item) {
                $lineBase = Money::mul($item['quantity'], $item['unit_price'], 4);
                $lineDiscount = Money::percentage($lineBase, $item['discount_rate']);
                $lineSubtotal = Money::round(Money::sub($lineBase, $lineDiscount));
                $lineTax = Money::percentage($lineSubtotal, $item['tax_rate']);
                $subtotal = Money::add($subtotal, $lineSubtotal);
                $discountAmount = Money::add($discountAmount, $lineDiscount);
                $taxAmount = Money::add($taxAmount, $lineTax);
                $lines[] = ['item' => $item, 'subtotal' => $lineSubtotal];
            }

            $total = Money::add($subtotal, $taxAmount);

            $invoice = SalesInvoice::create([
                'invoice_number' => app(DocumentNumberGenerator::class)->next('INV'),
                'sales_order_id' => $data->salesOrderId,
                'customer_id' => $data->customerId,
                'status' => 'issued',
                'issued_at' => $data->issuedAt,
                'due_at' => $data->dueAt,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'paid_amount' => '0.00',
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'];
                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_rate' => $item['discount_rate'],
                    'tax_rate' => $item['tax_rate'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            if (Money::isPositive($taxAmount)) {
                $this->recordTaxTransaction($invoice, $subtotal, $data->issuedAt, $data->createdBy);
            }

            if ($data->postJournal) {
                app(InvoiceJournalService::class)->postForInvoice($invoice);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Record a tax transaction for the issued invoice.
     * Silently skips if no active PPN tax rate is found (non-blocking).
     */
    private function recordTaxTransaction(SalesInvoice $invoice, string $subtotal, string $issuedAt, ?string $createdBy): void
    {
        try {
            $ppnRate = TaxRate::where('type', 'ppn')->where('is_active', true)->first();

            if ($ppnRate === null) {
                return;
            }

            $this->taxAction->execute(new RecordTaxTransactionData(
                taxRateId: $ppnRate->id,
                documentType: 'sales_invoice',
                documentId: $invoice->id,
                baseAmount: $subtotal,
                transactionDate: $issuedAt,
                direction: 'out',
                taxNumber: $invoice->invoice_number,
                createdBy: $createdBy,
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to record tax transaction for invoice '.$invoice->invoice_number.': '.$e->getMessage());
        }
    }
}
