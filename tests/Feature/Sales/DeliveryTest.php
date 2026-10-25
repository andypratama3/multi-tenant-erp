<?php

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderData;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\DeliveryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'P-001', 'name' => 'Product A',
        'unit_id' => $unit->id,
        'cost_price' => '5000.0000', 'selling_price' => '10000.0000',
    ]);
    $this->customer = Customer::create([
        'code' => 'CUST-001', 'name' => 'Customer A',
        'payment_terms_days' => 30, 'credit_limit' => '1000000.00', 'is_active' => true,
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-01', 'name' => 'Main']);
});

it('creates a delivery for a sales order', function () {
    $so = (new CreateSalesOrderAction)->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null, notes: null, createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity_ordered' => '5.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $delivery = Delivery::create([
        'delivery_number' => 'DO-20261025-0001',
        'sales_order_id' => $so->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => 'draft',
    ]);

    DeliveryItem::create([
        'delivery_id' => $delivery->id,
        'sales_order_item_id' => $so->items->first()->id,
        'product_id' => $this->product->id,
        'quantity_delivered' => '5.000',
    ]);

    expect(Delivery::count())->toBe(1)
        ->and($delivery->items)->toHaveCount(1)
        ->and($so->deliveries)->toHaveCount(1);
});

it('delivery status can be updated to shipped', function () {
    $so = (new CreateSalesOrderAction)->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null, notes: null, createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity_ordered' => '3.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $delivery = Delivery::create([
        'delivery_number' => 'DO-20261025-0002',
        'sales_order_id' => $so->id,
        'status' => 'draft',
    ]);

    $delivery->status = 'shipped';
    $delivery->shipped_at = '2026-10-26';
    $delivery->save();

    expect($delivery->fresh()->status)->toBe('shipped');
});
