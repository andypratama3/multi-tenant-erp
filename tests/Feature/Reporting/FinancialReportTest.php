<?php

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Account;
use App\Domain\Reporting\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cash = Account::create(['code' => '1-0001', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit']);
    $this->inventory = Account::create(['code' => '1-2000', 'name' => 'Persediaan', 'type' => 'asset', 'normal_balance' => 'debit']);
    $this->payable = Account::create(['code' => '2-0001', 'name' => 'Utang', 'type' => 'liability', 'normal_balance' => 'credit']);
    $this->revenue = Account::create(['code' => '4-0001', 'name' => 'Pendapatan', 'type' => 'revenue', 'normal_balance' => 'credit']);
    $this->expense = Account::create(['code' => '5-0001', 'name' => 'Beban', 'type' => 'expense', 'normal_balance' => 'debit']);

    $action = new PostJournalAction;

    $action->execute(new PostJournalData(
        postedAt: '2026-01-05',
        description: 'Penjualan tunai',
        lines: [
            ['account_id' => $this->cash->id, 'type' => 'debit', 'amount' => '1000.00'],
            ['account_id' => $this->revenue->id, 'type' => 'credit', 'amount' => '1000.00'],
        ],
    ));

    $action->execute(new PostJournalData(
        postedAt: '2026-01-06',
        description: 'Beban operasional',
        lines: [
            ['account_id' => $this->expense->id, 'type' => 'debit', 'amount' => '300.00'],
            ['account_id' => $this->cash->id, 'type' => 'credit', 'amount' => '300.00'],
        ],
    ));

    $action->execute(new PostJournalData(
        postedAt: '2026-01-07',
        description: 'Pembelian persediaan',
        lines: [
            ['account_id' => $this->inventory->id, 'type' => 'debit', 'amount' => '500.00'],
            ['account_id' => $this->payable->id, 'type' => 'credit', 'amount' => '500.00'],
        ],
    ));
});

it('builds a balanced trial balance', function () {
    $rows = (new FinancialReportService)->trialBalance('2026-01-01', '2026-01-31');

    expect($rows)->toHaveCount(5)
        ->and(array_sum($rows->map(fn ($row): float => (float) $row->debit)->all()))->toBe(1800.0)
        ->and(array_sum($rows->map(fn ($row): float => (float) $row->credit)->all()))->toBe(1800.0);
});

it('calculates the income statement', function () {
    $report = (new FinancialReportService)->incomeStatement('2026-01-01', '2026-01-31');

    expect($report['revenue'])->toBe('1000.00')
        ->and($report['expense'])->toBe('300.00')
        ->and($report['net_profit'])->toBe('700.00');
});

it('produces a balanced balance sheet', function () {
    $report = (new FinancialReportService)->balanceSheet('2026-01-31');

    expect($report['total_assets'])->toBe('1200.00')
        ->and($report['total_liabilities'])->toBe('500.00')
        ->and($report['net_profit'])->toBe('700.00')
        ->and($report['total_liabilities_equity'])->toBe('1200.00')
        ->and($report['balanced'])->toBeTrue();
});
