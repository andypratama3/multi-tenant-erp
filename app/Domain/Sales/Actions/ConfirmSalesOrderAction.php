<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Sales\Exceptions\InsufficientCreditException;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\SalesInvoice;
use App\Domain\Sales\Models\SalesOrder;
use App\Domain\Shared\Exceptions\InvalidDocumentStatusException;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ConfirmSalesOrderAction
{
    public function execute(ConfirmSalesOrderData $data): SalesOrder
    {
        return DB::transaction(function () use ($data): SalesOrder {
            /** @var SalesOrder $so */
            $so = SalesOrder::query()->lockForUpdate()->findOrFail($data->salesOrderId);

            if ($so->status !== 'draft') {
                throw new InvalidDocumentStatusException(
                    "SO {$so->so_number}",
                    $so->status,
                    'draft',
                );
            }

            /** @var Customer $customer */
            $customer = Customer::query()->findOrFail($so->customer_id);

            $outstanding = '0.00';

            $invoices = SalesInvoice::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['issued', 'partial'])
                ->get(['total', 'paid_amount']);

            foreach ($invoices as $invoice) {
                $outstanding = Money::add(
                    $outstanding,
                    Money::sub($invoice->total, $invoice->paid_amount),
                );
            }

            $available = Money::sub($customer->credit_limit, $outstanding);

            if (Money::compare($so->total, $available) === 1) {
                throw new InsufficientCreditException(
                    $customer->id,
                    $so->total,
                    $available,
                );
            }

            $so->status = 'confirmed';
            $so->confirmed_by = $data->confirmedBy;
            $so->confirmed_at = Carbon::now();
            $so->save();

            return $so->load('items');
        });
    }
}
