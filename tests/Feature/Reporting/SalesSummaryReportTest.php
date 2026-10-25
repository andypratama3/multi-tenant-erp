<?php

use App\Domain\Reporting\Services\SalesSummaryReport;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportInvoice(Customer $customer, string $number, string $total, string $paid, string $dueAt, string $status = 'issued'): SalesInvoice
{
    return SalesInvoice::create([
        'invoice_number' => $number,
        'customer_id' => $customer->id,
        'status' => $status,
        'issued_at' => '2026-07-01',
        'due_at' => $dueAt,
        'subtotal' => $total,
        'tax_amount' => '0.00',
        'discount_amount' => '0.00',
        'total' => $total,
        'paid_amount' => $paid,
    ]);
}

it('ages receivables into portable buckets and aggregates by customer', function () {
    $customer = Customer::create([
        'code' => 'CUST-AGE',
        'name' => 'Pelanggan Aging',
        'payment_terms_days' => 30,
        'credit_limit' => '10000000.00',
        'is_active' => true,
    ]);

    reportInvoice($customer, 'INV-AGE-1', '100000.00', '0.00', '2026-10-01');
    reportInvoice($customer, 'INV-AGE-2', '200000.00', '50000.00', '2026-08-01');
    reportInvoice($customer, 'INV-AGE-3', '300000.00', '0.00', '2026-10-25');
    reportInvoice($customer, 'INV-AGE-4', '900000.00', '0.00', '2026-09-30', 'paid');

    $row = (new SalesSummaryReport)->arAging('2026-10-25')->firstWhere('code', 'CUST-AGE');

    expect($row)->not->toBeNull()
        ->and($row->outstanding)->toBe('550000.00')
        ->and($row->current_due)->toBe('300000.00')
        ->and($row->overdue_1_30)->toBe('100000.00')
        ->and($row->overdue_31_60)->toBe('0.00')
        ->and($row->overdue_60_plus)->toBe('150000.00');
});

it('sorts customers by outstanding balance descending', function () {
    $small = Customer::create([
        'code' => 'CUST-SMALL', 'name' => 'Kecil',
        'payment_terms_days' => 30, 'credit_limit' => '1000000.00', 'is_active' => true,
    ]);
    $large = Customer::create([
        'code' => 'CUST-LARGE', 'name' => 'Besar',
        'payment_terms_days' => 30, 'credit_limit' => '10000000.00', 'is_active' => true,
    ]);

    reportInvoice($small, 'INV-S', '50000.00', '0.00', '2026-10-25');
    reportInvoice($large, 'INV-L', '750000.00', '0.00', '2026-10-25');

    $rows = (new SalesSummaryReport)->arAging('2026-10-25');

    expect($rows->pluck('code')->all())->toBe(['CUST-LARGE', 'CUST-SMALL']);
});
