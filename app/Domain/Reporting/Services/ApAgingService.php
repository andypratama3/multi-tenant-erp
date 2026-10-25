<?php

namespace App\Domain\Reporting\Services;

use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApAgingService
{
    /**
     * Returns accounts payable aging grouped by supplier and calculated in PHP
     * for portability across MySQL, PostgreSQL, and SQLite.
     *
     * @return Collection<int, \stdClass>
     */
    public function aging(string $asOf): Collection
    {
        $asOfDate = Carbon::parse($asOf)->startOfDay();

        $rows = DB::table('purchase_invoices as pi')
            ->join('suppliers as s', 's.id', '=', 'pi.supplier_id')
            ->select('s.code', 's.name', 'pi.due_date')
            ->selectRaw('pi.total - pi.paid_amount as outstanding')
            ->whereNull('pi.deleted_at')
            ->whereIn('pi.status', ['posted', 'partial'])
            ->get();

        /** @var array<string, \stdClass> $grouped */
        $grouped = [];

        foreach ($rows as $row) {
            $key = (string) $row->code.'|'.(string) $row->name;

            if (! isset($grouped[$key])) {
                $grouped[$key] = (object) [
                    'code' => (string) $row->code,
                    'name' => (string) $row->name,
                    'outstanding' => '0.00',
                    'current_due' => '0.00',
                    'overdue_1_30' => '0.00',
                    'overdue_31_60' => '0.00',
                    'overdue_60_plus' => '0.00',
                ];
            }

            $amount = Money::decimal((string) $row->outstanding);
            $grouped[$key]->outstanding = Money::add($grouped[$key]->outstanding, $amount);

            $overdueDays = (int) Carbon::parse((string) $row->due_date)->startOfDay()->diffInDays($asOfDate, false);

            $bucket = match (true) {
                $overdueDays <= 0 => 'current_due',
                $overdueDays <= 30 => 'overdue_1_30',
                $overdueDays <= 60 => 'overdue_31_60',
                default => 'overdue_60_plus',
            };

            $grouped[$key]->{$bucket} = Money::add($grouped[$key]->{$bucket}, $amount);
        }

        $result = array_values($grouped);

        usort($result, fn (\stdClass $a, \stdClass $b): int => Money::compare(
            (string) $b->outstanding,
            (string) $a->outstanding,
        ));

        return collect($result);
    }
}
