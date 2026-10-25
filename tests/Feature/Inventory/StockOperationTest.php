<?php

use App\Domain\Inventory\Actions\PostStockAdjustmentAction;
use App\Domain\Inventory\Actions\PostWarehouseTransferAction;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockAdjustment;
use App\Domain\Inventory\Models\StockAdjustmentItem;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseTransfer;
use App\Domain\Inventory\Models\WarehouseTransferItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'STK-001', 'name' => 'Produk Stok', 'unit_id' => $unit->id,
        'cost_price' => '4000.0000', 'selling_price' => '6000.0000',
    ]);
    $this->from = Warehouse::create(['code' => 'WH-A', 'name' => 'Gudang A']);
    $this->to = Warehouse::create(['code' => 'WH-B', 'name' => 'Gudang B']);

    (new RecordStockMovementAction)->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->from->id,
        movementType: 'in',
        quantity: '10.000',
        unitCost: '4000.0000',
    ));
});

it('posts a stock adjustment with the counted difference', function () {
    $adjustment = StockAdjustment::create([
        'adjustment_number' => 'SA-TEST-001',
        'warehouse_id' => $this->from->id,
        'adjusted_at' => '2026-10-10',
        'status' => 'draft',
        'reason' => 'Stock opname bulanan',
    ]);
    StockAdjustmentItem::create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $this->product->id,
        'counted_quantity' => '7.000',
        'unit_cost' => '4000.0000',
    ]);

    $posted = (new PostStockAdjustmentAction(new RecordStockMovementAction))->execute($adjustment->id);
    $balance = StockBalance::query()->where('warehouse_id', $this->from->id)->firstOrFail();

    expect($posted->status)->toBe('posted')
        ->and((float) $balance->quantity)->toBe(7.0)
        ->and($posted->items->first()->system_quantity)->toBe('10.000')
        ->and($posted->items->first()->difference_quantity)->toBe('-3.000');
});

it('posts a warehouse transfer and moves stock to the destination', function () {
    $transfer = WarehouseTransfer::create([
        'transfer_number' => 'WT-TEST-001',
        'from_warehouse_id' => $this->from->id,
        'to_warehouse_id' => $this->to->id,
        'transferred_at' => '2026-10-11',
        'status' => 'draft',
    ]);
    WarehouseTransferItem::create([
        'warehouse_transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => '4.000',
        'unit_cost' => '4000.0000',
    ]);

    $posted = (new PostWarehouseTransferAction(new RecordStockMovementAction))->execute($transfer->id);
    $source = StockBalance::query()->where('warehouse_id', $this->from->id)->firstOrFail();
    $destination = StockBalance::query()->where('warehouse_id', $this->to->id)->firstOrFail();

    expect($posted->status)->toBe('posted')
        ->and((float) $source->quantity)->toBe(6.0)
        ->and((float) $destination->quantity)->toBe(4.0);
});

it('prevents transferring more stock than available', function () {
    $transfer = WarehouseTransfer::create([
        'transfer_number' => 'WT-TEST-002',
        'from_warehouse_id' => $this->from->id,
        'to_warehouse_id' => $this->to->id,
        'transferred_at' => '2026-10-11',
        'status' => 'draft',
    ]);
    WarehouseTransferItem::create([
        'warehouse_transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => '99.000',
        'unit_cost' => '4000.0000',
    ]);

    expect(fn () => (new PostWarehouseTransferAction(new RecordStockMovementAction))->execute($transfer->id))
        ->toThrow(InsufficientStockException::class);
});
