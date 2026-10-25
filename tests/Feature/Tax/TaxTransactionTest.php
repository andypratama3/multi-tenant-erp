<?php

use App\Domain\Tax\Actions\RecordTaxTransactionAction;
use App\Domain\Tax\Actions\RecordTaxTransactionData;
use App\Domain\Tax\Models\TaxRate;
use App\Domain\Tax\Models\TaxTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ppn = TaxRate::create([
        'code' => 'PPN-11',
        'name' => 'PPN 11%',
        'type' => 'ppn',
        'rate' => '11.00',
        'is_active' => true,
    ]);
    $this->action = new RecordTaxTransactionAction;
});

it('records a tax transaction with correct tax amount', function () {
    $tx = $this->action->execute(new RecordTaxTransactionData(
        taxRateId: $this->ppn->id,
        documentType: 'sales_invoice',
        documentId: '00000000-0000-0000-0000-000000000001',
        baseAmount: '100000.00',
        transactionDate: '2026-10-25',
        direction: 'out',
    ));

    expect($tx->tax_amount)->toBe('11000.00')
        ->and(TaxTransaction::count())->toBe(1);
});

it('records tax transactions for both in and out directions', function () {
    $this->action->execute(new RecordTaxTransactionData(
        taxRateId: $this->ppn->id,
        documentType: 'sales_invoice',
        documentId: '00000000-0000-0000-0000-000000000001',
        baseAmount: '200000.00',
        transactionDate: '2026-10-25',
        direction: 'out',
    ));

    $this->action->execute(new RecordTaxTransactionData(
        taxRateId: $this->ppn->id,
        documentType: 'purchase_order',
        documentId: '00000000-0000-0000-0000-000000000002',
        baseAmount: '150000.00',
        transactionDate: '2026-10-25',
        direction: 'in',
    ));

    expect(TaxTransaction::where('direction', 'out')->count())->toBe(1)
        ->and(TaxTransaction::where('direction', 'in')->count())->toBe(1);
});
