<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Exceptions\JournalNotBalancedException;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Services\PeriodLockService;
use App\Support\Documents\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class PostJournalAction
{
    public function execute(PostJournalData $data): Journal
    {
        app(PeriodLockService::class)->ensureUnlocked($data->postedAt);

        // Validate balance before persisting (ADR 0004)
        $debitTotal = '0.00';
        $creditTotal = '0.00';

        foreach ($data->lines as $line) {
            if ($line['type'] === 'debit') {
                $debitTotal = Money::add($debitTotal, $line['amount']);
            } else {
                $creditTotal = Money::add($creditTotal, $line['amount']);
            }
        }

        if (Money::compare($debitTotal, $creditTotal) !== 0) {
            throw new JournalNotBalancedException((float) $debitTotal, (float) $creditTotal);
        }

        return DB::transaction(function () use ($data): Journal {
            $journal = Journal::create([
                'journal_number' => app(DocumentNumberGenerator::class)->next('JRN'),
                'posted_at' => $data->postedAt,
                'description' => $data->description,
                'reference_type' => $data->referenceType,
                'reference_id' => $data->referenceId,
                'status' => 'posted',
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->lines as $line) {
                $journal->lines()->create([
                    'account_id' => $line['account_id'],
                    'type' => $line['type'],
                    'amount' => Money::round($line['amount']),
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $journal->load('lines');
        });
    }
}
