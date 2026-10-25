<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Actions\PostJournalAction;
use App\Domain\Accounting\Actions\PostJournalData;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VoidSalesInvoiceAction
{
    public function __construct(
        private readonly PostJournalAction $journalAction = new PostJournalAction,
    ) {}

    public function execute(VoidSalesInvoiceData $data): SalesInvoice
    {
        return DB::transaction(function () use ($data): SalesInvoice {
            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($data->invoiceId);

            if ($invoice->status === 'cancelled') {
                throw new InvalidDocumentStatusException(
                    "Invoice {$invoice->invoice_number}",
                    $invoice->status,
                    'issued/partial',
                );
            }

            if (Money::isPositive($invoice->paid_amount)) {
                throw new InvalidArgumentException('Invoice yang sudah memiliki pembayaran tidak dapat dibatalkan.');
            }

            /** @var Journal|null $original */
            $original = Journal::query()
                ->with('lines')
                ->where('reference_type', SalesInvoice::class)
                ->where('reference_id', $invoice->id)
                ->latest('created_at')
                ->first();

            if ($original !== null) {
                $reversalLines = $original->lines->map(fn ($line): array => [
                    'account_id' => $line->account_id,
                    'type' => $line->type === 'debit' ? 'credit' : 'debit',
                    'amount' => Money::round($line->amount),
                    'description' => 'Pembalik: '.($line->description ?? $original->description),
                ])->all();

                $this->journalAction->execute(new PostJournalData(
                    postedAt: now()->toDateString(),
                    description: "Pembatalan Invoice #{$invoice->invoice_number}",
                    lines: $reversalLines,
                    referenceType: SalesInvoice::class,
                    referenceId: $invoice->id,
                    createdBy: $data->createdBy,
                ));
            }

            $invoice->status = 'cancelled';
            $invoice->notes = trim(($invoice->notes ?? '')."\nDibatalkan: {$data->reason}");
            $invoice->save();

            return $invoice->load('items');
        });
    }
}
