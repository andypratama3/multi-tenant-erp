<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Services\InvoiceJournalService;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesOrderItem;
use App\Domain\Tax\Actions\RecordTaxTransactionAction;
use App\Domain\Tax\Actions\RecordTaxTransactionData;
use App\Domain\Tax\Models\TaxRate;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class IssueSalesInvoiceAction
{
    public function __construct(
        private readonly RecordTaxTransactionAction $taxAction = new RecordTaxTransactionAction,
    ) {}

    public function execute(IssueSalesInvoiceData $data): SalesInvoice
    {
        return DB::transaction(function () use ($data): SalesInvoice {
            /** @var SalesOrder|null $salesOrder */
            $salesOrder = null;
            /** @var array<string, numeric-string> $remainingByItem */
            $remainingByItem = [];

            if ($data->salesOrderId !== null) {
                $salesOrder = SalesOrder::query()->with('items')->lockForUpdate()->findOrFail($data->salesOrderId);

                if (! in_array($salesOrder->status, ['confirmed', 'partial', 'fulfilled'], true)) {
                    throw new InvalidArgumentException('Pesanan penjualan belum siap difakturkan.');
                }

                if ($salesOrder->customer_id !== $data->customerId) {
                    throw new InvalidArgumentException('Pelanggan faktur tidak sesuai dengan pesanan penjualan.');
                }

                foreach ($salesOrder->items as $soItem) {
                    $remainingByItem[$soItem->id] = Money::decimal(bcsub(
                        Money::decimal($soItem->quantity_ordered, 3),
                        Money::decimal($soItem->quantity_invoiced, 3),
                        3,
                    ), 3);
                }
            }

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
                $soItem = null;

                if ($salesOrder !== null) {
                    $soItem = $this->resolveSalesOrderItem($salesOrder, $remainingByItem, $item);

                    $remainingByItem[$soItem->id] = bcsub(
                        $remainingByItem[$soItem->id],
                        Money::decimal($item['quantity'], 3),
                        3,
                    );
                }

                $lines[] = ['item' => $item, 'subtotal' => $lineSubtotal, 'so_item' => $soItem];
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

                if ($line['so_item'] instanceof SalesOrderItem) {
                    $line['so_item']->quantity_invoiced = Money::add(
                        $line['so_item']->quantity_invoiced,
                        $item['quantity'],
                        3,
                    );
                    $line['so_item']->save();
                }
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
     * @param  array<string, numeric-string>  $remainingByItem
     * @param  array{sales_order_item_id?: string|null, product_id: string, quantity: string, unit_price: string, discount_rate: string, tax_rate: string}  $item
     */
    private function resolveSalesOrderItem(SalesOrder $salesOrder, array $remainingByItem, array $item): SalesOrderItem
    {
        $quantity = Money::decimal($item['quantity'], 3);
        $requestedId = $item['sales_order_item_id'] ?? null;

        if (is_string($requestedId) && $requestedId !== '') {
            /** @var SalesOrderItem|null $soItem */
            $soItem = $salesOrder->items->firstWhere('id', $requestedId);

            if ($soItem === null || $soItem->product_id !== $item['product_id']) {
                throw new InvalidArgumentException('Item pesanan penjualan tidak sesuai dengan produk faktur.');
            }

            if (bccomp($quantity, $remainingByItem[$soItem->id] ?? '0', 3) === 1) {
                throw new InvalidArgumentException('Jumlah faktur melebihi sisa pesanan untuk produk '.$soItem->product_id.'.');
            }

            return $soItem;
        }

        /** @var SalesOrderItem|null $soItem */
        $soItem = $salesOrder->items->first(
            fn (SalesOrderItem $candidate): bool => $candidate->product_id === $item['product_id']
                && bccomp($quantity, $remainingByItem[$candidate->id] ?? '0', 3) <= 0,
        );

        if ($soItem === null) {
            throw new InvalidArgumentException('Tidak ada sisa pesanan yang dapat difakturkan untuk produk '.$item['product_id'].'.');
        }

        return $soItem;
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
