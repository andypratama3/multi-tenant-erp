<?php

namespace App\Domain\Tax\Actions;

use App\Domain\Tax\Models\TaxRate;
use App\Domain\Tax\Models\TaxTransaction;

class RecordTaxTransactionAction
{
    public function execute(RecordTaxTransactionData $data): TaxTransaction
    {
        /** @var TaxRate $taxRate */
        $taxRate = TaxRate::findOrFail($data->taxRateId);

        $taxAmount = round((float) $data->baseAmount * (float) $taxRate->rate / 100, 2);

        return TaxTransaction::create([
            'tax_rate_id' => $data->taxRateId,
            'document_type' => $data->documentType,
            'document_id' => $data->documentId,
            'base_amount' => $data->baseAmount,
            'tax_amount' => number_format($taxAmount, 2, '.', ''),
            'transaction_date' => $data->transactionDate,
            'tax_number' => $data->taxNumber,
            'direction' => $data->direction,
            'created_by' => $data->createdBy,
        ]);
    }
}
