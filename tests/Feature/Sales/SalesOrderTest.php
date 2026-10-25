<?php

use App\Domain\Inventory\Models\Product;
use App\Domain\Inventory\Models\Unit;
use App\Domain\Sales\Actions\ConfirmSalesOrderAction;
use App\Domain\Sales\Actions\ConfirmSalesOrderData;
use App\Domain\Sales\Actions\CreateSalesOrderAction;
use App\Domain\Sales\Actions\CreateSalesOrderData;
use App\Domain\Sales\Actions\IssueSalesInvoiceAction;
use App\Domain\Sales\Actions\IssueSalesInvoiceData;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Actions\RecordPaymentData;
use App\Domain\Sales\Exceptions\InvoiceAlreadyPaidException;
use App\Domain\Sales\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs']);
    $this->product = Product::create([
        'sku' => 'P-001', 'name' => 'Product A',
        'unit_id' => $unit->id,
        'cost_price' => '5000.0000', 'selling_price' => '10000.0000',
    ]);
    $this->customer = Customer::create([
        'code' => 'CUST-001', 'name' => 'Customer A',
        'payment_terms_days' => 30, 'credit_limit' => '1000000.00', 'is_active' => true,
    ]);
    $this->createSO = new CreateSalesOrderAction;
    $this->confirmSO = new ConfirmSalesOrderAction;
    $this->issueInvoice = new IssueSalesInvoiceAction;
    $this->recordPayment = new RecordPaymentAction;
});

it('creates sales order with correct totals', function () {
    $so = $this->createSO->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null,
        notes: null,
        createdBy: null,
        items: [[
            'product_id' => $this->product->id,
            'quantity_ordered' => '10.000',
            'unit_price' => '10000.0000',
            'discount_rate' => '0.00',
            'tax_rate' => '11.00',
        ]],
    ));

    expect($so->items)->toHaveCount(1)
        ->and((float) $so->subtotal)->toBe(100000.0)
        ->and((float) $so->tax_amount)->toEqualWithDelta(11000.0, 0.01)
        ->and((float) $so->total)->toEqualWithDelta(111000.0, 0.01);
});

it('issuing invoice sets status to issued', function () {
    $so = $this->createSO->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null, notes: null, createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity_ordered' => '5.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $this->confirmSO->execute(new ConfirmSalesOrderData(
        salesOrderId: $so->id,
        confirmedBy: null,
    ));

    $invoice = $this->issueInvoice->execute(new IssueSalesInvoiceData(
        salesOrderId: $so->id,
        customerId: $this->customer->id,
        issuedAt: '2026-10-25',
        dueAt: '2026-11-24',
        createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity' => '5.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    expect($invoice->status)->toBe('issued')
        ->and((float) $invoice->total)->toBe(50000.0);
});

it('full payment changes invoice status to paid', function () {
    $so = $this->createSO->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null, notes: null, createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity_ordered' => '2.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $this->confirmSO->execute(new ConfirmSalesOrderData(
        salesOrderId: $so->id,
        confirmedBy: null,
    ));

    $invoice = $this->issueInvoice->execute(new IssueSalesInvoiceData(
        salesOrderId: $so->id,
        customerId: $this->customer->id,
        issuedAt: '2026-10-25',
        dueAt: '2026-11-24',
        createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity' => '2.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $this->recordPayment->execute(new RecordPaymentData(
        invoiceId: $invoice->id,
        amount: (string) $invoice->total,
        paidAt: '2026-10-26',
        paymentMethod: 'transfer',
        reference: 'TRF-001',
        createdBy: null,
    ));

    expect($invoice->fresh()->status)->toBe('paid');
});

it('partial payment changes invoice status to partial', function () {
    $so = $this->createSO->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null, notes: null, createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity_ordered' => '10.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $this->confirmSO->execute(new ConfirmSalesOrderData(
        salesOrderId: $so->id,
        confirmedBy: null,
    ));

    $invoice = $this->issueInvoice->execute(new IssueSalesInvoiceData(
        salesOrderId: $so->id,
        customerId: $this->customer->id,
        issuedAt: '2026-10-25',
        dueAt: '2026-11-24',
        createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity' => '10.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $this->recordPayment->execute(new RecordPaymentData(
        invoiceId: $invoice->id,
        amount: '50000.00',
        paidAt: '2026-10-26',
        paymentMethod: 'cash',
        reference: null,
        createdBy: null,
    ));

    expect($invoice->fresh()->status)->toBe('partial');
});

it('throws InvoiceAlreadyPaidException when paying a fully paid invoice', function () {
    $so = $this->createSO->execute(new CreateSalesOrderData(
        customerId: $this->customer->id,
        orderedAt: '2026-10-25',
        requiredAt: null, notes: null, createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity_ordered' => '1.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $this->confirmSO->execute(new ConfirmSalesOrderData(
        salesOrderId: $so->id,
        confirmedBy: null,
    ));

    $invoice = $this->issueInvoice->execute(new IssueSalesInvoiceData(
        salesOrderId: $so->id,
        customerId: $this->customer->id,
        issuedAt: '2026-10-25',
        dueAt: '2026-11-24',
        createdBy: null,
        items: [['product_id' => $this->product->id, 'quantity' => '1.000', 'unit_price' => '10000.0000', 'discount_rate' => '0.00', 'tax_rate' => '0.00']],
    ));

    $this->recordPayment->execute(new RecordPaymentData(
        invoiceId: $invoice->id,
        amount: '10000.00',
        paidAt: '2026-10-26',
        paymentMethod: 'cash',
        reference: null,
        createdBy: null,
    ));

    expect(fn () => $this->recordPayment->execute(new RecordPaymentData(
        invoiceId: $invoice->id,
        amount: '1000.00',
        paidAt: '2026-10-27',
        paymentMethod: 'cash',
        reference: null,
        createdBy: null,
    )))->toThrow(InvoiceAlreadyPaidException::class);
});
