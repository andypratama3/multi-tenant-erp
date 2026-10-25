<?php

use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderAction;
use App\Domain\Purchasing\Actions\CreatePurchaseOrderData;
use App\Domain\Purchasing\Actions\PostGoodsReceiptNoteAction;
use App\Domain\Purchasing\Actions\PostGrnData;
use App\Domain\Purchasing\Exceptions\GrnAlreadyPostedException;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\GoodsReceiptNoteItem;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->supplier = Supplier::create([
        'code' => 'SUP-001', 'name' => 'Test Supplier', 'is_active' => true,
    ]);
    $this->unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'P-001', 'name' => 'Product A',
        'unit_id' => $this->unit->id,
        'cost_price' => '5000.0000', 'selling_price' => '8000.0000',
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-01', 'name' => 'Main']);
    $this->createAction = new CreatePurchaseOrderAction(new PurchaseNumberGenerator);
    $this->postAction = new PostGoodsReceiptNoteAction(new RecordStockMovementAction);
});

it('creates purchase order with correct totals', function () {
    $po = $this->createAction->execute(new CreatePurchaseOrderData(
        supplierId: $this->supplier->id,
        orderedAt: '2026-10-25',
        expectedAt: null,
        notes: null,
        createdBy: null,
        items: [[
            'product_id' => $this->product->id,
            'quantity_ordered' => '10.000',
            'unit_price' => '5000.0000',
            'tax_rate' => '11.00',
        ]],
    ));

    expect($po->items)->toHaveCount(1)
        ->and((float) $po->subtotal)->toBe(50000.0)
        ->and((float) $po->tax_amount)->toEqualWithDelta(5500.0, 0.01)
        ->and((float) $po->total)->toEqualWithDelta(55500.0, 0.01);
});

it('throws GrnAlreadyPostedException when posting twice', function () {
    $po = $this->createAction->execute(new CreatePurchaseOrderData(
        supplierId: $this->supplier->id,
        orderedAt: '2026-10-25',
        expectedAt: null, notes: null, createdBy: null,
        items: [[
            'product_id' => $this->product->id,
            'quantity_ordered' => '5.000',
            'unit_price' => '5000.0000',
            'tax_rate' => '0.00',
        ]],
    ));

    $grn = GoodsReceiptNote::create([
        'grn_number' => 'GRN-TEST-0001',
        'purchase_order_id' => $po->id,
        'received_at' => '2026-10-25',
        'warehouse_id' => $this->warehouse->id,
        'status' => 'draft',
    ]);
    GoodsReceiptNoteItem::create([
        'goods_receipt_note_id' => $grn->id,
        'purchase_order_item_id' => $po->items->first()->id,
        'product_id' => $this->product->id,
        'quantity_received' => '5.000',
        'unit_cost' => '5000.0000',
    ]);

    $this->postAction->execute(new PostGrnData($grn->id));

    expect(fn () => $this->postAction->execute(new PostGrnData($grn->id)))
        ->toThrow(GrnAlreadyPostedException::class);
});

it('posting grn updates po item quantity_received and po status', function () {
    $po = $this->createAction->execute(new CreatePurchaseOrderData(
        supplierId: $this->supplier->id,
        orderedAt: '2026-10-25',
        expectedAt: null, notes: null, createdBy: null,
        items: [[
            'product_id' => $this->product->id,
            'quantity_ordered' => '10.000',
            'unit_price' => '5000.0000',
            'tax_rate' => '0.00',
        ]],
    ));

    $grn = GoodsReceiptNote::create([
        'grn_number' => 'GRN-TEST-0002',
        'purchase_order_id' => $po->id,
        'received_at' => '2026-10-25',
        'warehouse_id' => $this->warehouse->id,
        'status' => 'draft',
    ]);
    GoodsReceiptNoteItem::create([
        'goods_receipt_note_id' => $grn->id,
        'purchase_order_item_id' => $po->items->first()->id,
        'product_id' => $this->product->id,
        'quantity_received' => '7.000',
        'unit_cost' => '5000.0000',
    ]);

    $this->postAction->execute(new PostGrnData($grn->id));

    expect((float) $po->items->first()->fresh()->quantity_received)->toBe(7.0)
        ->and($po->fresh()->status)->toBe('partial');
});
