<?php

use App\Domain\Accounting\Filament\Resources\JournalResource;
use App\Domain\Inventory\Filament\Resources\ProductResource;
use App\Domain\Inventory\Filament\Resources\StockAdjustmentResource;
use App\Domain\Inventory\Filament\Resources\StockLotResource;
use App\Domain\Inventory\Filament\Resources\WarehouseTransferResource;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\StockLot;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Purchasing\Filament\Resources\GoodsReceiptNoteResource;
use App\Domain\Purchasing\Filament\Resources\PurchaseInvoiceResource;
use App\Domain\Purchasing\Filament\Resources\PurchaseOrderResource;
use App\Domain\Purchasing\Filament\Resources\SupplierPaymentResource;
use App\Domain\Sales\Filament\Resources\DeliveryResource;
use App\Domain\Sales\Filament\Resources\SalesInvoiceResource;
use App\Domain\Sales\Filament\Resources\SalesOrderResource;
use App\Domain\Sales\Filament\Resources\SalesReturnResource;
use App\Filament\Pages\ApAgingReport;
use App\Filament\Pages\CompanySettingsPage;
use App\Filament\Pages\EFakturExport;
use App\Filament\Pages\FinancialReport;
use App\Filament\Pages\PosPage;
use App\Filament\Pages\SalesReport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('renders the admin dashboard with the new widgets', function () {
    $this->get('/dashboard')->assertOk();
});

it('renders key admin resource lists and the sales report', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    'products' => [fn () => ProductResource::getUrl('index')],
    'stock adjustments' => [fn () => StockAdjustmentResource::getUrl('index')],
    'warehouse transfers' => [fn () => WarehouseTransferResource::getUrl('index')],
    'stock lots' => [fn () => StockLotResource::getUrl('index')],
    'purchase orders' => [fn () => PurchaseOrderResource::getUrl('index')],
    'goods receipts' => [fn () => GoodsReceiptNoteResource::getUrl('index')],
    'sales orders' => [fn () => SalesOrderResource::getUrl('index')],
    'invoices' => [fn () => SalesInvoiceResource::getUrl('index')],
    'deliveries' => [fn () => DeliveryResource::getUrl('index')],
    'sales returns' => [fn () => SalesReturnResource::getUrl('index')],
    'journals' => [fn () => JournalResource::getUrl('index')],
    'purchase invoices' => [fn () => PurchaseInvoiceResource::getUrl('index')],
    'supplier payments' => [fn () => SupplierPaymentResource::getUrl('index')],
    'sales report' => [fn () => SalesReport::getUrl()],
    'ap aging report' => [fn () => ApAgingReport::getUrl()],
    'financial report' => [fn () => FinancialReport::getUrl()],
    'company settings' => [fn () => CompanySettingsPage::getUrl()],
    'pos' => [fn () => PosPage::getUrl()],
    'e-faktur export' => [fn () => EFakturExport::getUrl()],
]);

it('renders key admin create forms', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    'product' => [fn () => ProductResource::getUrl('create')],
    'purchase order' => [fn () => PurchaseOrderResource::getUrl('create')],
    'goods receipt' => [fn () => GoodsReceiptNoteResource::getUrl('create')],
    'purchase invoice' => [fn () => PurchaseInvoiceResource::getUrl('create')],
    'sales order' => [fn () => SalesOrderResource::getUrl('create')],
    'sales invoice' => [fn () => SalesInvoiceResource::getUrl('create')],
    'delivery' => [fn () => DeliveryResource::getUrl('create')],
    'sales return' => [fn () => SalesReturnResource::getUrl('create')],
    'stock adjustment' => [fn () => StockAdjustmentResource::getUrl('create')],
    'warehouse transfer' => [fn () => WarehouseTransferResource::getUrl('create')],
]);

it('forbids a role without permission from opening a resource list url directly', function () {
    $sales = User::factory()->create()->assignRole('sales');

    $this->actingAs($sales)
        ->get(PurchaseOrderResource::getUrl('index'))
        ->assertForbidden();
});

it('lets every operational role open the same dashboard', function (string $role) {
    $this->actingAs(User::factory()->create()->assignRole($role))
        ->get('/dashboard')
        ->assertOk();
})->with([
    'super_admin',
    'manajer',
    'sales',
    'kasir',
    'pembelian',
    'gudang',
    'akuntan',
    'auditor',
]);

it('renders a stock lot detail page', function () {
    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $product = Product::create([
        'sku' => 'SMOKE-LOT', 'name' => 'Produk Smoke Lot', 'unit_id' => $unit->id,
        'cost_price' => '1000.0000', 'selling_price' => '2000.0000',
        'requires_expiry' => true, 'shelf_life_days' => 30, 'expiry_alert_days' => 7,
    ]);
    $warehouse = Warehouse::create(['code' => 'WH-SMOKE', 'name' => 'Gudang Smoke']);
    $lot = StockLot::create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'lot_number' => 'SMOKE-LOT-01',
        'received_at' => '2026-10-01',
        'expiry_date' => '2026-10-31',
        'quantity_received' => '5.000',
        'quantity_remaining' => '5.000',
        'unit_cost' => '1000.0000',
    ]);

    $this->get(StockLotResource::getUrl('view', ['record' => $lot]))->assertOk();
});
