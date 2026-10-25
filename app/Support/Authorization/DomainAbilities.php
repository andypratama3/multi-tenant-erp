<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\ProductCategory;
use App\Domain\Inventory\Models\StockAdjustment;
use App\Domain\Inventory\Models\StockLot;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Models\WarehouseTransfer;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseInvoice;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Models\SupplierPayment;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Sales\Models\SalesReturn;
use App\Domain\Tax\Models\TaxRate;
use App\Policies\AccountPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\GoodsReceiptNotePolicy;
use App\Policies\JournalPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseInvoicePolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SalesInvoicePolicy;
use App\Policies\SalesOrderPolicy;
use App\Policies\SalesReturnPolicy;
use App\Policies\StockAdjustmentPolicy;
use App\Policies\StockLotPolicy;
use App\Policies\SupplierPaymentPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TaxRatePolicy;
use App\Policies\UnitPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\WarehouseTransferPolicy;

final class DomainAbilities
{
    public const STANDARD = [
        'ViewAny',
        'View',
        'Create',
        'Update',
        'Delete',
        'DeleteAny',
        'Restore',
        'RestoreAny',
        'ForceDelete',
        'ForceDeleteAny',
        'Replicate',
        'Reorder',
    ];

    /**
     * @var array<class-string, array{subject: string, custom: array<int, string>}>
     */
    public const MODELS = [
        Product::class => ['subject' => 'Product', 'custom' => []],
        ProductCategory::class => ['subject' => 'ProductCategory', 'custom' => []],
        Unit::class => ['subject' => 'Unit', 'custom' => []],
        Warehouse::class => ['subject' => 'Warehouse', 'custom' => []],
        StockAdjustment::class => ['subject' => 'StockAdjustment', 'custom' => ['Post']],
        StockLot::class => ['subject' => 'StockLot', 'custom' => []],
        WarehouseTransfer::class => ['subject' => 'WarehouseTransfer', 'custom' => ['Post']],
        Supplier::class => ['subject' => 'Supplier', 'custom' => []],
        PurchaseOrder::class => ['subject' => 'PurchaseOrder', 'custom' => ['Approve', 'Send', 'Cancel']],
        GoodsReceiptNote::class => ['subject' => 'GoodsReceiptNote', 'custom' => ['Post']],
        PurchaseInvoice::class => ['subject' => 'PurchaseInvoice', 'custom' => ['RecordPayment']],
        SupplierPayment::class => ['subject' => 'SupplierPayment', 'custom' => []],
        Customer::class => ['subject' => 'Customer', 'custom' => []],
        SalesOrder::class => ['subject' => 'SalesOrder', 'custom' => ['Confirm', 'Cancel']],
        SalesReturn::class => ['subject' => 'SalesReturn', 'custom' => []],
        SalesInvoice::class => ['subject' => 'SalesInvoice', 'custom' => ['RecordPayment', 'DownloadPdf', 'Void']],
        Delivery::class => ['subject' => 'Delivery', 'custom' => ['Post']],
        Payment::class => ['subject' => 'Payment', 'custom' => []],
        Account::class => ['subject' => 'Account', 'custom' => []],
        Journal::class => ['subject' => 'Journal', 'custom' => ['Post']],
        TaxRate::class => ['subject' => 'TaxRate', 'custom' => []],
    ];

    /**
     * @return array<class-string, class-string>
     */
    public static function policies(): array
    {
        return [
            Product::class => ProductPolicy::class,
            ProductCategory::class => ProductCategoryPolicy::class,
            Unit::class => UnitPolicy::class,
            Warehouse::class => WarehousePolicy::class,
            StockAdjustment::class => StockAdjustmentPolicy::class,
            StockLot::class => StockLotPolicy::class,
            WarehouseTransfer::class => WarehouseTransferPolicy::class,
            Supplier::class => SupplierPolicy::class,
            PurchaseOrder::class => PurchaseOrderPolicy::class,
            GoodsReceiptNote::class => GoodsReceiptNotePolicy::class,
            PurchaseInvoice::class => PurchaseInvoicePolicy::class,
            SupplierPayment::class => SupplierPaymentPolicy::class,
            Customer::class => CustomerPolicy::class,
            SalesOrder::class => SalesOrderPolicy::class,
            SalesReturn::class => SalesReturnPolicy::class,
            SalesInvoice::class => SalesInvoicePolicy::class,
            Delivery::class => DeliveryPolicy::class,
            Payment::class => PaymentPolicy::class,
            Account::class => AccountPolicy::class,
            Journal::class => JournalPolicy::class,
            TaxRate::class => TaxRatePolicy::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        $permissions = [];

        foreach (self::MODELS as $definition) {
            foreach ([...self::STANDARD, ...$definition['custom']] as $ability) {
                $permissions[] = $ability.':'.$definition['subject'];
            }
        }

        return $permissions;
    }

    /**
     * @return array<int, string>
     */
    public static function readPermissions(): array
    {
        $permissions = [];

        foreach (self::MODELS as $definition) {
            $permissions[] = 'ViewAny:'.$definition['subject'];
            $permissions[] = 'View:'.$definition['subject'];
        }

        return $permissions;
    }

    /**
     * @param  array<int, string>  $abilities
     * @return array<int, string>
     */
    public static function forSubject(string $subject, array $abilities): array
    {
        return array_map(fn (string $ability): string => $ability.':'.$subject, $abilities);
    }
}
