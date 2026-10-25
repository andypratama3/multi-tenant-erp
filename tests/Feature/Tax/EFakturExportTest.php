<?php

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Tax\Services\EFakturExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds an efaktur csv with tax invoices in the period', function () {
    $customer = Customer::create([
        'code' => 'C-EF', 'name' => 'PT Pelanggan', 'tax_id' => '0123456789012345',
        'address' => 'Jl. Contoh 1', 'payment_terms_days' => 30,
        'credit_limit' => '1000000.00', 'is_active' => true,
    ]);

    SalesInvoice::create([
        'invoice_number' => 'INV-EF-001', 'customer_id' => $customer->id,
        'status' => 'issued', 'issued_at' => '2026-10-05', 'due_at' => '2026-11-04',
        'subtotal' => '100000.00', 'discount_amount' => '0.00', 'tax_amount' => '11000.00',
        'total' => '111000.00', 'paid_amount' => '0.00', 'returned_amount' => '0.00',
    ]);

    SalesInvoice::create([
        'invoice_number' => 'INV-EF-002', 'customer_id' => $customer->id,
        'status' => 'issued', 'issued_at' => '2026-10-06', 'due_at' => '2026-11-05',
        'subtotal' => '50000.00', 'discount_amount' => '0.00', 'tax_amount' => '0.00',
        'total' => '50000.00', 'paid_amount' => '0.00', 'returned_amount' => '0.00',
    ]);

    $csv = (new EFakturExportService)->csv('2026-10-01', '2026-10-31');
    $lines = array_values(array_filter(explode("\n", trim($csv))));

    expect($lines)->toHaveCount(2)
        ->and($lines[0])->toContain('NOMOR_FAKTUR')
        ->and($lines[1])->toContain('INV-EF-001')
        ->and($lines[1])->toContain('0123456789012345')
        ->and($lines[1])->toContain('100000.00')
        ->and($lines[1])->toContain('11000.00')
        ->and($csv)->not->toContain('INV-EF-002');
});
