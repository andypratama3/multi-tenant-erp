<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Actions\RecordStockMovementData;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockBalance;
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
