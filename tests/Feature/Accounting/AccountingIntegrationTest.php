<?php

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Services\AccountResolver;
use App\Domain\Accounting\Services\GrnJournalService;
use App\Domain\Accounting\Services\InvoiceJournalService;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderData;
use App\Domain\Purchasing\Actions\PostGoodsReceiptNoteAction;
use App\Domain\Purchasing\Actions\PostGrnData;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\GoodsReceiptNoteItem;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseNumberGenerator;
use App\Domain\Sales\Actions\ConfirmSalesOrderAction;
use App\Domain\Sales\Actions\ConfirmSalesOrderData;
use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderData;
use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceData;
use App\Domain\Sales\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedAccounts(): void
{
    $accounts = [
        ['code' => '1-0001', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '1-1000', 'name' => 'Piutang Usaha', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '1-2000', 'name' => 'Persediaan Barang', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '1-3000', 'name' => 'PPN Masukan', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '2-0001', 'name' => 'Utang Usaha', 'type' => 'liability', 'normal_balance' => 'credit'],
        ['code' => '2-1000', 'name' => 'Utang Pajak PPN Keluaran', 'type' => 'liability', 'normal_balance' => 'credit'],
        ['code' => '4-0001', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue', 'normal_balance' => 'credit'],
        ['code' => '5-0001', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'normal_balance' => 'debit'],
    ];

    foreach ($accounts as $acct) {
        Account::firstOrCreate(['code' => $acct['code']], $acct);
    }
}

it('posts a GRN journal with correct debit/credit entries', function () {
    seedAccounts();

    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $product = Product::create(['sku' => 'P-001', 'name' => 'Widget', 'unit_id' => $unit->id, 'cost_price' => '5000.0000', 'selling_price' => '9000.0000']);
    $warehouse = Warehouse::create(['code' => 'WH-01', 'name' => 'Main']);
    $supplier = Supplier::create(['code' => 'S-01', 'name' => 'Supplier A', 'is_active' => true]);

    $createPO = new CreatePurchaseOrderAction(new PurchaseNumberGenerator);
    $postGRN = new PostGoodsReceiptNoteAction(new RecordStockMovementAction);
    $resolver = new AccountResolver;
    $grnJournal = new GrnJournalService(new PostJournalAction, $resolver);

    $po = $createPO->execute(new CreatePurchaseOrderData(
        supplierId: $supplier->id,
        orderedAt: '2026-10-25',
        expectedAt: null,
        notes: null,
        createdBy: null,
        items: [['product_id' => $product->id, 'quantity_ordered' => '10.000', 'unit_price' => '5000.0000', 'tax_rate' => '0.00']],
    ));

    $grn = GoodsReceiptNote::create([
        'grn_number' => 'GRN-INT-0001',
        'purchase_order_id' => $po->id,
        'received_at' => '2026-10-25',
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
    ]);

    GoodsReceiptNoteItem::create([
        'goods_receipt_note_id' => $grn->id,
        'purchase_order_item_id' => $po->items->first()->id,
        'product_id' => $product->id,
        'quantity_received' => '10.000',
        'unit_cost' => '5000.0000',
    ]);

    $postGRN->execute(new PostGrnData($grn->id));
    $journal = $grnJournal->postForGrn($grn->fresh());

    expect(Journal::count())->toBe(1)
        ->and($journal->lines)->toHaveCount(2)
        ->and($journal->lines->where('type', 'debit')->first()->amount)->toBe('50000.00')
        ->and($journal->lines->where('type', 'credit')->first()->amount)->toBe('50000.00');
});

it('posts an invoice journal with receivable and revenue entries', function () {
    seedAccounts();

    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $product = Product::create(['sku' => 'P-002', 'name' => 'Product', 'unit_id' => $unit->id, 'cost_price' => '5000.0000', 'selling_price' => '10000.0000']);
    $customer = Customer::create(['code' => 'C-001', 'name' => 'Customer X', 'payment_terms_days' => 30, 'credit_limit' => '999999.00', 'is_active' => true]);

    $so = (new CreateSalesOrderAction)->execute(new CreateSalesOrderData(
        customerId: $customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null,
        notes: null,
        createdBy: null,
        items: [['product_id' => $product->id, 'quantity_ordered' => '5.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '11.00']],
    ));

    (new ConfirmSalesOrderAction)->execute(new ConfirmSalesOrderData(
        salesOrderId: $so->id,
        confirmedBy: null,
    ));

    $invoice = (new IssueSalesInvoiceAction)->execute(new IssueSalesInvoiceData(
        salesOrderId: $so->id,
        customerId: $customer->id,
        issuedAt: '2026-10-25',
        dueAt: '2026-11-24',
        createdBy: null,
        items: [['product_id' => $product->id, 'quantity' => '5.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '11.00']],
    ));

    $resolver = new AccountResolver;
    $invJournal = new InvoiceJournalService(new PostJournalAction, $resolver);
    $journal = $invJournal->postForInvoice($invoice);

    // Total = 50000 + 11% = 55500; subtotal = 50000; ppn = 5500
    expect($journal->lines)->toHaveCount(3) // receivable, revenue, ppn_output
        ->and($journal->lines->where('type', 'debit')->sum('amount'))->toEqual(55500.0)
        ->and($journal->lines->where('type', 'credit')->sum('amount'))->toEqual(55500.0);
});
