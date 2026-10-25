<?php

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Inventory\Models\Product;
use App\Domain\Purchasing\Models\GoodsReceiptNote;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Sales\Models\Delivery;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesOrder;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('grants super admin every ability through the gate before hook', function () {
    $user = userWithRole('super_admin');

    expect(Gate::forUser($user)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('approve', new PurchaseOrder))->toBeTrue()
        ->and(Gate::forUser($user)->allows('post', new Journal))->toBeTrue();
});

it('allows sales to manage sales documents but not purchasing or accounting', function () {
    $user = userWithRole('sales');

    expect(Gate::forUser($user)->allows('create', SalesInvoice::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('confirm', new SalesOrder))->toBeTrue()
        ->and(Gate::forUser($user)->allows('post', new GoodsReceiptNote))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Account::class))->toBeFalse();
});

it('allows warehouse staff to post receipts and deliveries but not invoices', function () {
    $user = userWithRole('gudang');

    expect(Gate::forUser($user)->allows('post', new GoodsReceiptNote))->toBeTrue()
        ->and(Gate::forUser($user)->allows('post', new Delivery))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', SalesInvoice::class))->toBeFalse();
});

it('allows managers to approve purchase orders without posting journals', function () {
    $user = userWithRole('manajer');

    expect(Gate::forUser($user)->allows('approve', new PurchaseOrder))->toBeTrue()
        ->and(Gate::forUser($user)->allows('post', new Journal))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', SalesInvoice::class))->toBeFalse();
});

it('keeps auditors read only', function () {
    $user = userWithRole('auditor');

    expect(Gate::forUser($user)->allows('viewAny', SalesInvoice::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', SalesInvoice::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', new Product))->toBeFalse();
});

it('denies panel access to users without an operational role', function () {
    $user = User::factory()->create();
    $panel = Panel::make()->id('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();

    $user->assignRole('akuntan');

    expect($user->canAccessPanel($panel))->toBeTrue();
});
