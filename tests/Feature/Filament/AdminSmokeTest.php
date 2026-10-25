<?php

use App\Domain\Accounting\Filament\Resources\JournalResource;
use App\Domain\Inventory\Filament\Resources\ProductResource;
use App\Domain\Inventory\Filament\Resources\StockAdjustmentResource;
use App\Domain\Inventory\Filament\Resources\WarehouseTransferResource;
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
    $this->get('/admin')->assertOk();
});

it('renders key admin resource lists and the sales report', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    'products' => [fn () => ProductResource::getUrl('index')],
    'stock adjustments' => [fn () => StockAdjustmentResource::getUrl('index')],
    'warehouse transfers' => [fn () => WarehouseTransferResource::getUrl('index')],
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
