<?php

namespace App\Domain\Reporting\Services;

use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesSummaryReport
{
    /**
     * Returns daily sales summary between two dates.
     *
     * @return Collection<int, \stdClass>
     */
    public function dailySummary(string $from, string $to): Collection
    {
        return DB::table('sales_invoices')
            ->select(
                DB::raw('DATE(issued_at) as date'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(subtotal) as subtotal'),
                DB::raw('SUM(tax_amount) as tax_amount'),
                DB::raw('SUM(total) as total'),
                DB::raw('SUM(paid_amount) as paid_amount'),
            )
            ->whereNull('deleted_at')
            ->whereIn('status', ['issued', 'paid', 'partial'])
            ->whereBetween('issued_at', [$from, $to])
            ->groupBy(DB::raw('DATE(issued_at)'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Returns top-selling products by revenue.
     *
     * @return Collection<int, \stdClass>
     */
    public function topProducts(string $from, string $to, int $limit = 10): Collection
    {
        return DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->join('products as p', 'p.id', '=', 'sii.product_id')
            ->select(
                'p.sku',
                'p.name',
                DB::raw('SUM(sii.quantity) as total_quantity'),
                DB::raw('SUM(sii.subtotal) as total_revenue'),
            )
            ->whereNull('si.deleted_at')
            ->whereIn('si.status', ['issued', 'paid', 'partial'])
            ->whereBetween('si.issued_at', [$from, $to])
            ->groupBy('p.id', 'p.sku', 'p.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Returns accounts receivable aging summary.
     *
     * Aging buckets are calculated in PHP so the report works on MySQL,
     * PostgreSQL, and SQLite without database-specific date functions.
     *
     * @return Collection<int, \stdClass>
     */
    public function arAging(string $asOf): Collection
    {
        $asOfDate = Carbon::parse($asOf)->startOfDay();

        $rows = DB::table('sales_invoices as si')
            ->join('customers as c', 'c.id', '=', 'si.customer_id')
            ->select('c.code', 'c.name', 'si.due_at')
            ->selectRaw('si.total - si.returned_amount - si.paid_amount as outstanding')
            ->whereNull('si.deleted_at')
            ->whereIn('si.status', ['issued', 'partial'])
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

            $overdueDays = (int) Carbon::parse((string) $row->due_at)->startOfDay()->diffInDays($asOfDate, false);

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
