<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Reporting\Services\SalesSummaryReport;
use App\Domain\Sales\Actions\CreateSalesReturnAction;
use App\Domain\Sales\Actions\CreateSalesReturnData;
use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceData;
use App\Domain\Sales\Exceptions\ReturnQuantityExceedsInvoiceException;
use App\Domain\Sales\Models\Customer;
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
        'sku' => 'SR-001', 'name' => 'Produk Retur', 'unit_id' => $unit->id,
        'cost_price' => '6000.0000', 'selling_price' => '10000.0000',
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-SR', 'name' => 'Gudang Retur']);
    $customer = Customer::create([
        'code' => 'C-SR', 'name' => 'Pelanggan Retur', 'payment_terms_days' => 30,
        'credit_limit' => '999999.00', 'is_active' => true,
    ]);

    $this->invoice = (new IssueSalesInvoiceAction)->execute(new IssueSalesInvoiceData(
        salesOrderId: null,
        customerId: $customer->id,
        issuedAt: '2026-10-01',
        dueAt: '2026-10-31',
        createdBy: null,
        items: [[
            'product_id' => $this->product->id,
            'quantity' => '2.000',
            'unit_price' => '10000.0000',
            'discount_rate' => '0.00',
            'tax_rate' => '11.00',
        ]],
    ));
});

it('creates a sales return, restocks inventory, and posts the credit journal', function () {
    $invoiceItem = $this->invoice->items->firstOrFail();

    $return = (new CreateSalesReturnAction(new RecordStockMovementAction))
        ->execute(new CreateSalesReturnData(
            invoiceId: $this->invoice->id,
            warehouseId: $this->warehouse->id,
            returnedAt: '2026-10-05',
            reason: 'Barang rusak',
            items: [[
                'sales_invoice_item_id' => $invoiceItem->id,
                'quantity' => '1.000',
                'restock' => true,
            ]],
        ));

    $journal = Journal::query()->with('lines')->latest('created_at')->firstOrFail();
    $balance = StockBalance::query()->where('product_id', $this->product->id)->firstOrFail();

    expect($return->subtotal)->toBe('10000.00')
        ->and($return->tax_amount)->toBe('1100.00')
        ->and($return->total)->toBe('11100.00')
        ->and($this->invoice->fresh()->returned_amount)->toBe('11100.00')
        ->and((float) $balance->quantity)->toBe(1.0)
        ->and($journal->lines)->toHaveCount(3)
        ->and($journal->lines->where('type', 'debit')->sum('amount'))->toEqual(11100.0)
        ->and($journal->lines->where('type', 'credit')->sum('amount'))->toEqual(11100.0);
});

it('prevents returning more than the invoice quantity', function () {
    $invoiceItem = $this->invoice->items->firstOrFail();

    expect(fn () => (new CreateSalesReturnAction(new RecordStockMovementAction))
        ->execute(new CreateSalesReturnData(
            invoiceId: $this->invoice->id,
            warehouseId: $this->warehouse->id,
            returnedAt: '2026-10-05',
            reason: 'Rusak',
            items: [[
                'sales_invoice_item_id' => $invoiceItem->id,
                'quantity' => '3.000',
                'restock' => true,
            ]],
        )))->toThrow(ReturnQuantityExceedsInvoiceException::class);
});

it('reduces the invoice outstanding in the ar aging report', function () {
    $invoiceItem = $this->invoice->items->firstOrFail();

    (new CreateSalesReturnAction(new RecordStockMovementAction))
        ->execute(new CreateSalesReturnData(
            invoiceId: $this->invoice->id,
            warehouseId: $this->warehouse->id,
            returnedAt: '2026-10-05',
            reason: 'Rusak',
            items: [[
                'sales_invoice_item_id' => $invoiceItem->id,
                'quantity' => '1.000',
                'restock' => false,
            ]],
        ));

    $rows = (new SalesSummaryReport)->arAging('2026-10-10');

    expect($rows)->toHaveCount(1)
        ->and((float) $rows->first()->outstanding)->toBe(11100.0);
});

it('rejects cancelled invoices', function () {
    $invoiceItem = $this->invoice->items->firstOrFail();
    SalesInvoice::query()->whereKey($this->invoice->id)->update(['status' => 'cancelled']);

    expect(fn () => (new CreateSalesReturnAction(new RecordStockMovementAction))
        ->execute(new CreateSalesReturnData(
            invoiceId: $this->invoice->id,
            warehouseId: $this->warehouse->id,
            returnedAt: '2026-10-05',
            reason: 'Rusak',
            items: [[
                'sales_invoice_item_id' => $invoiceItem->id,
                'quantity' => '1.000',
                'restock' => false,
            ]],
        )))->toThrow(InvalidArgumentException::class);
});
