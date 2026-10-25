<?php

use App\Domain\Inventory\Actions\PostWarehouseTransferAction;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockLot;
use App\Domain\Inventory\Models\StockLotAllocation;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseTransfer;
use App\Domain\Inventory\Models\WarehouseTransferItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'LOT-001', 'name' => 'Produk Kedaluwarsa', 'unit_id' => $unit->id,
        'cost_price' => '5000.0000', 'selling_price' => '8000.0000',
        'requires_expiry' => true, 'shelf_life_days' => 30, 'expiry_alert_days' => 7,
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-LOT', 'name' => 'Gudang Lot']);
    $this->destination = Warehouse::create(['code' => 'WH-LOT-2', 'name' => 'Gudang Tujuan']);
    $this->action = new RecordStockMovementAction;
    $this->receiveLot = function (Product $product, Warehouse $warehouse, string $quantity, string $expiry, string $lotNumber = 'LOT-A', string $receivedAt = '2026-10-01'): void {
        (new RecordStockMovementAction)->execute(new RecordStockMovementData(
            productId: $product->id,
            warehouseId: $warehouse->id,
            movementType: 'in',
            quantity: $quantity,
            unitCost: '5000.0000',
            receivedAt: $receivedAt,
            expiryDate: $expiry,
            lotNumber: $lotNumber,
        ));
    };
});

it('consumes the earliest expiry batch first', function () {
    ($this->receiveLot)($this->product, $this->warehouse, '10.000', '2026-11-05', 'LOT-A');
    ($this->receiveLot)($this->product, $this->warehouse, '10.000', '2026-12-05', 'LOT-B');

    $this->action->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'out',
        quantity: '12.000',
        unitCost: '5000.0000',
    ));

    $lots = StockLot::query()->orderBy('expiry_date')->get();

    expect($lots)->toHaveCount(2)
        ->and($lots[0]->lot_number)->toBe('LOT-A')
        ->and($lots[0]->quantity_remaining)->toBe('0.000')
        ->and($lots[1]->lot_number)->toBe('LOT-B')
        ->and($lots[1]->quantity_remaining)->toBe('8.000')
        ->and(StockLotAllocation::query()->sum('quantity'))->toEqual(12.0);
});

it('blocks stock out when the only available batch is expired', function () {
    ($this->receiveLot)($this->product, $this->warehouse, '10.000', now()->subDay()->toDateString(), 'LOT-OLD');

    expect(fn () => $this->action->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'out',
        quantity: '1.000',
        unitCost: '5000.0000',
    )))->toThrow(InsufficientStockException::class);

    expect(StockLot::query()->firstOrFail()->quantity_remaining)->toBe('10.000');
});

it('allows negative adjustments to consume expired batches', function () {
    ($this->receiveLot)($this->product, $this->warehouse, '10.000', now()->subDay()->toDateString(), 'LOT-OLD');

    $this->action->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'adjustment',
        quantity: '-4.000',
        unitCost: '5000.0000',
        allowExpired: true,
    ));

    expect(StockLot::query()->firstOrFail()->quantity_remaining)->toBe('6.000')
        ->and(StockLotAllocation::query()->sum('quantity'))->toEqual(4.0);
});

it('creates a batch for a positive stock adjustment', function () {
    $this->action->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'adjustment',
        quantity: '2.000',
        unitCost: '5000.0000',
    ));

    $lot = StockLot::query()->firstOrFail();

    expect($lot->quantity_remaining)->toBe('2.000')
        ->and($lot->expiry_date?->toDateString())->toBe(today()->addDays(30)->toDateString());
});

it('derives expiry from shelf life when no expiry date is provided', function () {
    (new RecordStockMovementAction)->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'in',
        quantity: '5.000',
        unitCost: '5000.0000',
        receivedAt: '2026-10-01',
    ));

    expect(StockLot::query()->firstOrFail()->expiry_date?->toDateString())->toBe('2026-10-31');
});

it('requires an expiry date when the product has no shelf life', function () {
    $this->product->update(['shelf_life_days' => null]);

    expect(fn () => (new RecordStockMovementAction)->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'in',
        quantity: '5.000',
        unitCost: '5000.0000',
        receivedAt: '2026-10-01',
    )))->toThrow(InvalidArgumentException::class);
});

it('creates multiple lots from a single receipt', function () {
    $this->action->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'in',
        quantity: '10.000',
        unitCost: '5000.0000',
        receivedAt: '2026-10-01',
        lots: [
            ['lot_number' => 'LOT-A', 'expiry_date' => '2026-11-01', 'quantity' => '6.000', 'unit_cost' => '5000.0000'],
            ['lot_number' => 'LOT-B', 'expiry_date' => '2026-12-01', 'quantity' => '4.000', 'unit_cost' => '5000.0000'],
        ],
    ));

    expect(StockLot::query()->orderBy('lot_number')->pluck('quantity_remaining', 'lot_number')->all())
        ->toBe(['LOT-A' => '6.000', 'LOT-B' => '4.000']);
});

it('preserves batch details when transferring stock between warehouses', function () {
    ($this->receiveLot)($this->product, $this->warehouse, '10.000', '2026-11-15', 'LOT-TRANSFER');

    $transfer = WarehouseTransfer::create([
        'transfer_number' => 'WT-LOT-001',
        'from_warehouse_id' => $this->warehouse->id,
        'to_warehouse_id' => $this->destination->id,
        'transferred_at' => '2026-10-20',
        'status' => 'draft',
    ]);

    WarehouseTransferItem::create([
        'warehouse_transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => '4.000',
        'unit_cost' => '5000.0000',
    ]);

    (new PostWarehouseTransferAction(new RecordStockMovementAction))->execute($transfer->id);

    $source = StockLot::query()->where('warehouse_id', $this->warehouse->id)->firstOrFail();
    $destination = StockLot::query()->where('warehouse_id', $this->destination->id)->firstOrFail();

    expect($source->quantity_remaining)->toBe('6.000')
        ->and($destination->quantity_remaining)->toBe('4.000')
        ->and($destination->lot_number)->toBe('LOT-TRANSFER')
        ->and($destination->expiry_date?->toDateString())->toBe('2026-11-15');
});
