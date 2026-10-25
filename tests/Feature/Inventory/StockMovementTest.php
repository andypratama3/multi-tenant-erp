<?php

use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'TST-001', 'name' => 'Test Product',
        'unit_id' => $this->unit->id,
        'cost_price' => '10000.0000', 'selling_price' => '15000.0000',
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-01', 'name' => 'Main Warehouse']);
    $this->action = new RecordStockMovementAction;
});

it('creates stock balance on first opening movement', function () {
    $data = new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'opening',
        quantity: '100.000',
        unitCost: '10000.0000',
    );

    $ledger = $this->action->execute($data);

    expect($ledger->running_balance)->toBe('100.000')
        ->and(StockBalance::first()->quantity)->toBe('100.000');
});

it('reduces balance on stock out', function () {
    // opening stock
    $this->action->execute(new RecordStockMovementData(
        $this->product->id, $this->warehouse->id, 'opening', '100.000', '10000.0000'
    ));

    // stock out
    $this->action->execute(new RecordStockMovementData(
        $this->product->id, $this->warehouse->id, 'out', '30.000', '10000.0000'
    ));

    expect(StockBalance::first()->quantity)->toBe('70.000');
});

it('throws InsufficientStockException when out exceeds balance', function () {
    $this->action->execute(new RecordStockMovementData(
        $this->product->id, $this->warehouse->id, 'opening', '10.000', '10000.0000'
    ));

    expect(fn () => $this->action->execute(new RecordStockMovementData(
        $this->product->id, $this->warehouse->id, 'out', '50.000', '10000.0000'
    )))->toThrow(InsufficientStockException::class);
});

it('calculates moving average correctly on second receipt', function () {
    // First receipt: 100 units @ 10000
    $this->action->execute(new RecordStockMovementData(
        $this->product->id, $this->warehouse->id, 'in', '100.000', '10000.0000'
    ));

    // Second receipt: 50 units @ 12000
    // New avg = (100*10000 + 50*12000) / 150 = 1600000/150 = 10666.6667
    $this->action->execute(new RecordStockMovementData(
        $this->product->id, $this->warehouse->id, 'in', '50.000', '12000.0000'
    ));

    $balance = StockBalance::first();
    expect((float) $balance->average_cost)->toBeGreaterThan(10666.0)
        ->and((float) $balance->average_cost)->toBeLessThan(10667.0)
        ->and((float) $balance->quantity)->toBe(150.0);
});
