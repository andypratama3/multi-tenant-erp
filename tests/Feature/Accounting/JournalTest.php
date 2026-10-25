<?php

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Exceptions\JournalNotBalancedException;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cash = Account::create([
        'code' => '1-0001', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit',
    ]);
    $this->revenue = Account::create([
        'code' => '4-0001', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue', 'normal_balance' => 'credit',
    ]);
    $this->action = new PostJournalAction;
});

it('posts a balanced journal successfully', function () {
    $journal = $this->action->execute(new PostJournalData(
        postedAt: '2026-10-25',
        description: 'Penjualan tunai',
        lines: [
            ['account_id' => $this->cash->id, 'type' => 'debit', 'amount' => '500000.00'],
            ['account_id' => $this->revenue->id, 'type' => 'credit', 'amount' => '500000.00'],
        ],
    ));

    expect($journal->status)->toBe('posted')
        ->and($journal->lines)->toHaveCount(2)
        ->and(Journal::count())->toBe(1);
});

it('throws JournalNotBalancedException when debit != credit', function () {
    expect(fn () => $this->action->execute(new PostJournalData(
        postedAt: '2026-10-25',
        description: 'Unbalanced',
        lines: [
            ['account_id' => $this->cash->id, 'type' => 'debit', 'amount' => '500000.00'],
            ['account_id' => $this->revenue->id, 'type' => 'credit', 'amount' => '400000.00'],
        ],
    )))->toThrow(JournalNotBalancedException::class);
});

it('does not persist journal when balance check fails', function () {
    try {
        $this->action->execute(new PostJournalData(
            postedAt: '2026-10-25',
            description: 'Unbalanced',
            lines: [
                ['account_id' => $this->cash->id, 'type' => 'debit', 'amount' => '100.00'],
                ['account_id' => $this->revenue->id, 'type' => 'credit', 'amount' => '99.00'],
            ],
        ));
    } catch (JournalNotBalancedException) {
    }

    expect(Journal::count())->toBe(0);
});
