<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockBalance;
use App\Domain\Inventory\Models\StockLot;
use App\Domain\Inventory\Models\StockLotAllocation;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Tax\Models\TaxRate;
use App\Filament\Pages\PosPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    foreach ([
        ['code' => '1-0001', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '1-1000', 'name' => 'Piutang', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '1-2000', 'name' => 'Persediaan', 'type' => 'asset', 'normal_balance' => 'debit'],
        ['code' => '2-1000', 'name' => 'PPN Keluaran', 'type' => 'liability', 'normal_balance' => 'credit'],
        ['code' => '4-0001', 'name' => 'Pendapatan', 'type' => 'revenue', 'normal_balance' => 'credit'],
        ['code' => '5-0001', 'name' => 'HPP', 'type' => 'expense', 'normal_balance' => 'debit'],
    ] as $account) {
        Account::create($account);
    }

    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'POS-001', 'name' => 'Produk POS', 'unit_id' => $unit->id,
        'cost_price' => '6000.0000', 'selling_price' => '10000.0000',
    ]);
    $this->warehouse = Warehouse::create(['code' => 'WH-POS', 'name' => 'Gudang POS']);
    $this->customer = Customer::create([
        'code' => 'C-POS', 'name' => 'Pelanggan POS', 'payment_terms_days' => 0,
        'credit_limit' => '999999.00', 'is_active' => true,
    ]);
    TaxRate::create(['code' => 'PPN11', 'name' => 'PPN 11%', 'type' => 'ppn', 'rate' => '11.00', 'is_active' => true]);

    (new RecordStockMovementAction)->execute(new RecordStockMovementData(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        movementType: 'in',
        quantity: '10.000',
        unitCost: '6000.0000',
    ));
});

it('processes a pos sale end to end', function () {
    Livewire::test(PosPage::class)
        ->set('customerId', $this->customer->id)
        ->set('warehouseId', $this->warehouse->id)
        ->set('productId', $this->product->id)
        ->set('quantity', '2')
        ->call('addProduct')
        ->assertSet('cart.'.$this->product->id.'.quantity', '2.000')
        ->set('amountPaid', '30000')
        ->call('process')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::query()->with('payments')->firstOrFail();
    $balance = StockBalance::query()->where('warehouse_id', $this->warehouse->id)->firstOrFail();

    expect($invoice->status)->toBe('paid')
        ->and($invoice->total)->toBe('22200.00')
        ->and($invoice->payments)->toHaveCount(1)
        ->and((float) $balance->quantity)->toBe(8.0);
});

it('rejects payment below the cart total', function () {
    Livewire::test(PosPage::class)
        ->set('customerId', $this->customer->id)
        ->set('warehouseId', $this->warehouse->id)
        ->set('productId', $this->product->id)
        ->set('quantity', '2')
        ->call('addProduct')
        ->set('amountPaid', '10000')
        ->call('process')
        ->assertHasErrors(['amountPaid']);

    expect(SalesInvoice::count())->toBe(0);
});

it('allows cashiers but blocks sales role from opening the pos page', function () {
    $this->actingAs(User::factory()->create()->assignRole('kasir'));
    $this->get(PosPage::getUrl())->assertOk();

    $this->actingAs(User::factory()->create()->assignRole('sales'));
    $this->get(PosPage::getUrl())->assertForbidden();
});

it('ignores tampered client-side prices and taxes', function () {
    Livewire::test(PosPage::class)
        ->set('customerId', $this->customer->id)
        ->set('warehouseId', $this->warehouse->id)
        ->set('productId', $this->product->id)
        ->set('quantity', '2')
        ->call('addProduct')
        ->set('cart.'.$this->product->id.'.unit_price', '0.0000')
        ->set('cart.'.$this->product->id.'.tax_rate', '0.00')
        ->set('amountPaid', '22200')
        ->call('process')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::query()->firstOrFail();

    expect($invoice->total)->toBe('22200.00')
        ->and($invoice->tax_amount)->toBe('2200.00');
});

it('blocks adding more quantity than available stock', function () {
    Livewire::test(PosPage::class)
        ->set('customerId', $this->customer->id)
        ->set('warehouseId', $this->warehouse->id)
        ->set('productId', $this->product->id)
        ->set('quantity', '11')
        ->call('addProduct')
        ->assertHasErrors(['quantity']);

    expect(SalesInvoice::count())->toBe(0);
});

it('sells from the earliest expiry batch for a tracked product', function () {
    $tracked = Product::create([
        'sku' => 'POS-EXP', 'name' => 'Produk Kedaluwarsa', 'unit_id' => $this->product->unit_id,
        'cost_price' => '6000.0000', 'selling_price' => '10000.0000',
        'requires_expiry' => true, 'shelf_life_days' => 30, 'expiry_alert_days' => 7,
    ]);
    $expiry = now()->addDays(10)->toDateString();

    (new RecordStockMovementAction)->execute(new RecordStockMovementData(
        productId: $tracked->id,
        warehouseId: $this->warehouse->id,
        movementType: 'in',
        quantity: '5.000',
        unitCost: '6000.0000',
        receivedAt: now()->toDateString(),
        expiryDate: $expiry,
        lotNumber: 'POS-LOT-01',
    ));

    Livewire::test(PosPage::class)
        ->set('customerId', $this->customer->id)
        ->set('warehouseId', $this->warehouse->id)
        ->set('productId', $tracked->id)
        ->set('quantity', '2')
        ->call('addProduct')
        ->assertSet('cart.'.$tracked->id.'.expiry_date', $expiry)
        ->set('amountPaid', '30000')
        ->call('process')
        ->assertHasNoErrors();

    $lot = StockLot::query()->where('product_id', $tracked->id)->firstOrFail();

    expect($lot->quantity_remaining)->toBe('3.000')
        ->and(StockLotAllocation::query()->sum('quantity'))->toEqual(2.0);
});

it('blocks selling a tracked product when only expired stock remains', function () {
    $tracked = Product::create([
        'sku' => 'POS-OLD', 'name' => 'Produk Kedaluwarsa Lama', 'unit_id' => $this->product->unit_id,
        'cost_price' => '6000.0000', 'selling_price' => '10000.0000',
        'requires_expiry' => true, 'shelf_life_days' => 30, 'expiry_alert_days' => 7,
    ]);

    (new RecordStockMovementAction)->execute(new RecordStockMovementData(
        productId: $tracked->id,
        warehouseId: $this->warehouse->id,
        movementType: 'in',
        quantity: '5.000',
        unitCost: '6000.0000',
        receivedAt: now()->subDays(10)->toDateString(),
        expiryDate: now()->subDay()->toDateString(),
        lotNumber: 'POS-LOT-OLD',
    ));

    Livewire::test(PosPage::class)
        ->set('customerId', $this->customer->id)
        ->set('warehouseId', $this->warehouse->id)
        ->set('productId', $tracked->id)
        ->set('quantity', '1')
        ->call('addProduct')
        ->assertHasErrors(['quantity']);

    expect(SalesInvoice::count())->toBe(0);
});
