<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Purchasing\Actions\CreatePurchaseInvoiceFromGrnAction;
use App\Domain\Purchasing\Actions\CreatePurchaseInvoiceFromGrnData;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderData;
use App\Domain\Purchasing\Actions\PostGoodsReceiptNoteAction;
use App\Domain\Purchasing\Actions\PostGrnData;
use App\Domain\Purchasing\Actions\RecordSupplierPaymentAction;
use App\Domain\Purchasing\Actions\RecordSupplierPaymentData;
use App\Domain\Purchasing\Exceptions\PurchaseInvoiceAlreadyExistsException;
use App\Domain\Purchasing\Exceptions\SupplierPaymentExceedsOutstandingException;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\GoodsReceiptNoteItem;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseNumberGenerator;
use App\Domain\Reporting\Services\ApAgingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        ['code' => '1-0001', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '1-0002', 'name' => 'Bank', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '1-2000', 'name' => 'Persediaan', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '2-0001', 'name' => 'Utang Usaha', 'type' => 'liability', 'normal_balance' => 'credit'],
    ] as $account) {
        Account::create($account);
    }

    $this->supplier = Supplier::create(['code' => 'SUP-AP', 'name' => 'Pemasok AP', 'is_active' => true]);
    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'AP-001', 'name' => 'Produk AP', 'unit_id' => $unit->id,
        'cost_price' => '5000.0000', 'selling_price' => '8000.0000',
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-AP', 'name' => 'Gudang AP']);

    $po = (new CreatePurchaseOrderAction(new PurchaseNumberGenerator))->execute(new CreatePurchaseOrderData(
        supplierId: $this->supplier->id,
        orderedAt: '2026-10-01',
        expectedAt: null,
        notes: null,
        createdBy: null,
        items: [[
            'product_id' => $this->product->id,
            'quantity_ordered' => '10.000',
            'unit_price' => '5000.0000',
            'tax_rate' => '0.00',
        ]],
    ));

    $this->grn = GoodsReceiptNote::create([
        'grn_number' => 'GRN-AP-0001',
        'purchase_order_id' => $po->id,
        'received_at' => '2026-10-02',
        'warehouse_id' => $this->warehouse->id,
        'status' => 'draft',
    ]);

    GoodsReceiptNoteItem::create([
        'goods_receipt_note_id' => $this->grn->id,
        'purchase_order_item_id' => $po->items->first()->id,
        'product_id' => $this->product->id,
        'quantity_received' => '10.000',
        'unit_cost' => '5000.0000',
    ]);

    (new PostGoodsReceiptNoteAction(new RecordStockMovementAction))->execute(new PostGrnData($this->grn->id));
});

it('creates a purchase invoice from a posted grn', function () {
    $invoice = (new CreatePurchaseInvoiceFromGrnAction)->execute(new CreatePurchaseInvoiceFromGrnData(
        grnId: $this->grn->id,
        invoiceDate: '2026-10-03',
        dueDate: '2026-11-02',
        supplierInvoiceNumber: 'INV-SUP-001',
    ));

    expect($invoice->status)->toBe('posted')
        ->and($invoice->total)->toBe('50000.00')
        ->and($invoice->supplier_id)->toBe($this->supplier->id)
        ->and($invoice->ap_number)->toStartWith('PI-');
});

it('prevents duplicate invoices for the same grn', function () {
    $action = new CreatePurchaseInvoiceFromGrnAction;
    $data = new CreatePurchaseInvoiceFromGrnData($this->grn->id, '2026-10-03', '2026-11-02');

    $action->execute($data);

    expect(fn () => $action->execute($data))->toThrow(PurchaseInvoiceAlreadyExistsException::class);
});

it('records a partial supplier payment and posts the payable journal', function () {
    $invoice = (new CreatePurchaseInvoiceFromGrnAction)->execute(new CreatePurchaseInvoiceFromGrnData(
        grnId: $this->grn->id,
        invoiceDate: '2026-10-03',
        dueDate: '2026-11-02',
    ));

    $payment = (new RecordSupplierPaymentAction)->execute(new RecordSupplierPaymentData(
        purchaseInvoiceId: $invoice->id,
        amount: '20000.00',
        paidAt: '2026-10-10',
        paymentMethod: 'transfer',
        reference: 'TRF-001',
    ));

    $journal = Journal::query()->with('lines')->latest('created_at')->firstOrFail();

    expect($payment->amount)->toBe('20000.00')
        ->and($invoice->fresh()->status)->toBe('partial')
        ->and($invoice->fresh()->paid_amount)->toBe('20000.00')
        ->and($journal->lines)->toHaveCount(2)
        ->and($journal->lines->where('type', 'debit')->first()->amount)->toBe('20000.00')
        ->and($journal->lines->where('type', 'credit')->first()->amount)->toBe('20000.00');
});

it('prevents payment above the outstanding balance', function () {
    $invoice = (new CreatePurchaseInvoiceFromGrnAction)->execute(new CreatePurchaseInvoiceFromGrnData(
        grnId: $this->grn->id,
        invoiceDate: '2026-10-03',
        dueDate: '2026-11-02',
    ));

    expect(fn () => (new RecordSupplierPaymentAction)->execute(new RecordSupplierPaymentData(
        purchaseInvoiceId: $invoice->id,
        amount: '60000.00',
        paidAt: '2026-10-10',
        paymentMethod: 'cash',
    )))->toThrow(SupplierPaymentExceedsOutstandingException::class);
});

it('builds ap aging buckets by due date', function () {
    $invoice = (new CreatePurchaseInvoiceFromGrnAction)->execute(new CreatePurchaseInvoiceFromGrnData(
        grnId: $this->grn->id,
        invoiceDate: '2026-01-01',
        dueDate: '2026-01-15',
    ));

    $invoice->update(['paid_amount' => '10000.00', 'status' => 'partial']);

    $rows = (new ApAgingService)->aging('2026-03-20');

    expect($rows)->toHaveCount(1)
        ->and((float) $rows->first()->outstanding)->toBe(40000.0)
        ->and((float) $rows->first()->overdue_60_plus)->toBe(40000.0)
        ->and((float) $rows->first()->current_due)->toBe(0.0);
});

it('shows a current invoice as not yet due', function () {
    Carbon::setTestNow('2026-01-10');

    (new CreatePurchaseInvoiceFromGrnAction)->execute(new CreatePurchaseInvoiceFromGrnData(
        grnId: $this->grn->id,
        invoiceDate: '2026-01-01',
        dueDate: '2026-01-15',
    ));

    $rows = (new ApAgingService)->aging('2026-01-10');

    expect((float) $rows->first()->current_due)->toBe(50000.0)
        ->and((float) $rows->first()->overdue_60_plus)->toBe(0.0);

    Carbon::setTestNow();
})->after(function () {
    Carbon::setTestNow();
});
