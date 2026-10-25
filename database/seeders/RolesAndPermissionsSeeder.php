<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Authorization\DomainAbilities;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolePermissions = $this->rolePermissions();

        $permissions = collect($rolePermissions)
            ->flatten()
            ->merge(DomainAbilities::permissions())
            ->merge(DomainAbilities::readPermissions())
            ->merge(['ViewAny:Role', 'View:Role', 'Create:Role', 'Update:Role', 'Delete:Role'])
            ->unique()
            ->values();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach ($rolePermissions as $roleName => $abilities) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($abilities);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(Permission::all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissions(): array
    {
        $readAll = DomainAbilities::readPermissions();

        return [
            'manajer' => [
                ...$readAll,
                'Approve:PurchaseOrder',
                'DownloadPdf:SalesInvoice',
            ],
            'sales' => [
                ...DomainAbilities::forSubject('Customer', ['ViewAny', 'View', 'Create', 'Update']),
                ...DomainAbilities::forSubject('SalesOrder', ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Confirm', 'Cancel']),
                ...DomainAbilities::forSubject('SalesReturn', ['ViewAny', 'View', 'Create']),
                ...DomainAbilities::forSubject('SalesInvoice', ['ViewAny', 'View', 'Create', 'Update', 'RecordPayment', 'DownloadPdf']),
                ...DomainAbilities::forSubject('Delivery', ['ViewAny', 'View', 'Create']),
                ...DomainAbilities::forSubject('Payment', ['ViewAny', 'View', 'Create']),
                ...$this->productReads(),
            ],
            'kasir' => [
                ...DomainAbilities::forSubject('Customer', ['ViewAny', 'View']),
                ...DomainAbilities::forSubject('SalesOrder', ['ViewAny', 'View', 'Confirm']),
                ...DomainAbilities::forSubject('SalesReturn', ['ViewAny', 'View', 'Create']),
                ...DomainAbilities::forSubject('SalesInvoice', ['ViewAny', 'View', 'Create', 'RecordPayment', 'DownloadPdf']),
                ...DomainAbilities::forSubject('Payment', ['ViewAny', 'View', 'Create']),
                ...DomainAbilities::forSubject('Delivery', ['ViewAny', 'View', 'Create', 'Post']),
                ...$this->productReads(),
            ],
            'pembelian' => [
                ...DomainAbilities::forSubject('Supplier', ['ViewAny', 'View', 'Create', 'Update', 'Delete']),
                ...DomainAbilities::forSubject('PurchaseOrder', ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Send', 'Cancel']),
                ...DomainAbilities::forSubject('GoodsReceiptNote', ['ViewAny', 'View', 'Create', 'Update', 'Post']),
                ...DomainAbilities::forSubject('PurchaseInvoice', ['ViewAny', 'View', 'Create']),
                ...DomainAbilities::forSubject('SupplierPayment', ['ViewAny', 'View']),
                ...$this->productReads(),
            ],
            'gudang' => [
                ...DomainAbilities::forSubject('GoodsReceiptNote', ['ViewAny', 'View', 'Create', 'Update', 'Post']),
                ...DomainAbilities::forSubject('Delivery', ['ViewAny', 'View', 'Create', 'Post']),
                ...DomainAbilities::forSubject('PurchaseOrder', ['ViewAny', 'View']),
                ...DomainAbilities::forSubject('Customer', ['ViewAny', 'View']),
                ...$this->productReads(),
            ],
            'akuntan' => [
                ...DomainAbilities::forSubject('Account', ['ViewAny', 'View', 'Create', 'Update', 'Delete']),
                ...DomainAbilities::forSubject('Journal', ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Post']),
                ...DomainAbilities::forSubject('TaxRate', ['ViewAny', 'View', 'Create', 'Update', 'Delete']),
                ...DomainAbilities::forSubject('SalesInvoice', ['ViewAny', 'View', 'DownloadPdf']),
                ...DomainAbilities::forSubject('Payment', ['ViewAny', 'View']),
                ...DomainAbilities::forSubject('PurchaseOrder', ['ViewAny', 'View']),
                ...DomainAbilities::forSubject('GoodsReceiptNote', ['ViewAny', 'View']),
                ...DomainAbilities::forSubject('PurchaseInvoice', ['ViewAny', 'View', 'RecordPayment']),
                ...DomainAbilities::forSubject('SupplierPayment', ['ViewAny', 'View', 'Create']),
                ...DomainAbilities::forSubject('Delivery', ['ViewAny', 'View']),
                ...DomainAbilities::forSubject('Supplier', ['ViewAny', 'View']),
                ...DomainAbilities::forSubject('Customer', ['ViewAny', 'View']),
                ...$this->productReads(),
            ],
            'auditor' => [
                ...$readAll,
                'DownloadPdf:SalesInvoice',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function productReads(): array
    {
        return [
            ...DomainAbilities::forSubject('Product', ['ViewAny', 'View']),
            ...DomainAbilities::forSubject('ProductCategory', ['ViewAny', 'View']),
            ...DomainAbilities::forSubject('Unit', ['ViewAny', 'View']),
            ...DomainAbilities::forSubject('Warehouse', ['ViewAny', 'View']),
            ...DomainAbilities::forSubject('TaxRate', ['ViewAny', 'View']),
        ];
    }
}
