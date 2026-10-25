<?php

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Exceptions\PeriodLockedException;
use App\Domain\Accounting\Models\Account;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cash = Account::create(['code' => '1-0001', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit']);
    $this->revenue = Account::create(['code' => '4-0001', 'name' => 'Pendapatan', 'type' => 'revenue', 'normal_balance' => 'credit']);
});

function postTestJournal(PostJournalAction $action, string $date): void
{
    $action->execute(new PostJournalData(
        postedAt: $date,
        description: 'Test journal',
        lines: [
            ['account_id' => Account::query()->where('code', '1-0001')->firstOrFail()->id, 'type' => 'debit', 'amount' => '100.00'],
            ['account_id' => Account::query()->where('code', '4-0001')->firstOrFail()->id, 'type' => 'credit', 'amount' => '100.00'],
        ],
    ));
}

it('rejects journals in a locked period', function () {
    Setting::create(['key' => 'period_locked_until', 'value' => '2026-01-31']);

    expect(fn () => postTestJournal(new PostJournalAction, '2026-01-15'))
        ->toThrow(PeriodLockedException::class);
});

it('allows journals after the locked period', function () {
    Setting::create(['key' => 'period_locked_until', 'value' => '2026-01-31']);

    postTestJournal(new PostJournalAction, '2026-02-01');

    expect(true)->toBeTrue();
});
