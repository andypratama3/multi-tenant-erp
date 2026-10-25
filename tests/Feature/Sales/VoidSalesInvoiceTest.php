<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderData;
use App\Domain\Sales\Actions\CreateSalesReturnAction;
use App\Domain\Sales\Actions\CreateSalesReturnData;
use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceData;
use App\Domain\Sales\Actions\VoidSalesInvoiceAction;
use App\Domain\Sales\Actions\VoidSalesInvoiceData;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        ['code' => '1-1000', 'name' => 'Piutang', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '2-1000', 'name' => 'PPN Keluaran', 'type' => 'liability', 'normal_balance' => 'credit'],
        ['code' => '4-0001', 'name' => 'Pendapatan', 'type' => 'revenue', 'normal_balance' => 'credit'],
    ] as $account) {
        Account::create($account);
    }

    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'VOID-001', 'name' => 'Produk Void', 'unit_id' => $unit->id,
        'cost_price' => '5000.0000', 'selling_price' => '10000.0000',
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-VOID', 'name' => 'Gudang Void']);
    $this->customer = Customer::create([
        'code' => 'C-VOID', 'name' => 'Pelanggan Void', 'payment_terms_days' => 30,
        'credit_limit' => '999999.00', 'is_active' => true,
    ]);
    $this->invoice = (new IssueSalesInvoiceAction)->execute(new IssueSalesInvoiceData(
        salesOrderId: null,
        customerId: $this->customer->id,
        issuedAt: '2026-10-01',
        dueAt: '2026-10-31',
        createdBy: null,
        postJournal: true,
        items: [[
            'product_id' => $this->product->id,
            'quantity' => '1.000',
            'unit_price' => '10000.0000',
            'discount_rate' => '0.00',
            'tax_rate' => '11.00',
        ]],
    ));
});

it('cancels an invoice and posts a reversing journal', function () {
    $invoice = (new VoidSalesInvoiceAction)->execute(new VoidSalesInvoiceData(
        invoiceId: $this->invoice->id,
        reason: 'Salah input',
        createdBy: null,
    ));

    $journals = Journal::query()->where('reference_id', $this->invoice->id)->with('lines')->get();
    $reversal = $journals->last();

    expect($invoice->status)->toBe('cancelled')
        ->and($invoice->notes)->toContain('Dibatalkan: Salah input')
        ->and($journals)->toHaveCount(2)
        ->and($reversal?->lines->where('type', 'debit')->sum('amount'))->toEqual(11100.0)
        ->and($reversal?->lines->where('type', 'credit')->sum('amount'))->toEqual(11100.0);
});

it('refuses to cancel an invoice that already has a return', function () {
    $item = $this->invoice->items->firstOrFail();

    (new CreateSalesReturnAction(new RecordStockMovementAction))->execute(new CreateSalesReturnData(
        invoiceId: $this->invoice->id,
        warehouseId: $this->warehouse->id,
        returnedAt: '2026-10-05',
        reason: 'Rusak',
        items: [['sales_invoice_item_id' => $item->id, 'quantity' => '1.000', 'restock' => false]],
    ));

    expect(fn () => (new VoidSalesInvoiceAction)->execute(new VoidSalesInvoiceData(
        invoiceId: $this->invoice->id,
        reason: 'Batal',
        createdBy: null,
    )))->toThrow(InvalidArgumentException::class);

    expect($this->invoice->fresh()?->status)->not->toBe('cancelled');
});

it('refuses to cancel an invoice whose order already has a delivery', function () {
    $order = (new CreateSalesOrderAction)->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-01',
        requiredAt: null,
        notes: null,
        createdBy: null,
        items: [[
            'product_id' => $this->product->id,
            'quantity_ordered' => '1.000',
            'unit_price' => '10000.0000',
            'discount_rate' => '0.00',
            'tax_rate' => '11.00',
        ]],
    ));

    Delivery::create([
        'delivery_number' => 'DO-VOID-001',
        'sales_order_id' => $order->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => 'draft',
    ]);

    SalesInvoice::query()->whereKey($this->invoice->id)->update(['sales_order_id' => $order->id]);

    expect(fn () => (new VoidSalesInvoiceAction)->execute(new VoidSalesInvoiceData(
        invoiceId: $this->invoice->id,
        reason: 'Batal',
        createdBy: null,
    )))->toThrow(InvalidArgumentException::class);
});
