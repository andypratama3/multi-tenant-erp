<?php

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use App\Filament\Pages\SalesReport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('summarizes sales invoices for the report page', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    $customer = Customer::create([
        'code' => 'CUST-RPT',
        'name' => 'Pelanggan Laporan',
        'payment_terms_days' => 30,
        'credit_limit' => '10000000.00',
        'is_active' => true,
    ]);

    $invoices = [
        ['INV-RPT-1', '100000.00', '11000.00', '111000.00', '40000.00', 'partial', '2026-06-01'],
        ['INV-RPT-2', '50000.00', '5500.00', '55500.00', '0.00', 'issued', '2026-07-20'],
    ];

    foreach ($invoices as [$number, $subtotal, $tax, $total, $paid, $status, $dueAt]) {
        SalesInvoice::create([
            'invoice_number' => $number,
            'customer_id' => $customer->id,
            'status' => $status,
            'issued_at' => '2026-07-01',
            'due_at' => $dueAt,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount_amount' => '0.00',
            'total' => $total,
            'paid_amount' => $paid,
        ]);
    }

    $component = Livewire::test(SalesReport::class)
        ->set('fromDate', '2026-07-01')
        ->set('toDate', '2026-07-31')
        ->call('updateReport');

    expect($component->get('summary'))->toMatchArray([
        'invoice_count' => 2,
        'subtotal' => '150000.00',
        'tax' => '16500.00',
        'total' => '166500.00',
        'paid' => '40000.00',
        'outstanding' => '126500.00',
        'overdue' => '126500.00',
    ]);
});
