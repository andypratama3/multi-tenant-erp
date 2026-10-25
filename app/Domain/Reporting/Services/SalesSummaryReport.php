<?php

namespace App\Domain\Reporting\Services;

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
     * @return Collection<int, \stdClass>
     */
    public function arAging(string $asOf): Collection
    {
        // Validate date format via Carbon to ensure it's safe to interpolate
        $safeAsOf = Carbon::parse($asOf)->format('Y-m-d');

        return DB::table('sales_invoices as si')
            ->join('customers as c', 'c.id', '=', 'si.customer_id')
            ->select(
                'c.code',
                'c.name',
                DB::raw('SUM(si.total - si.paid_amount) as outstanding'),
                DB::raw('SUM(CASE WHEN DATEDIFF(?, si.due_at) <= 0 THEN si.total - si.paid_amount ELSE 0 END) as current_due'),
                DB::raw('SUM(CASE WHEN DATEDIFF(?, si.due_at) BETWEEN 1 AND 30 THEN si.total - si.paid_amount ELSE 0 END) as overdue_1_30'),
                DB::raw('SUM(CASE WHEN DATEDIFF(?, si.due_at) BETWEEN 31 AND 60 THEN si.total - si.paid_amount ELSE 0 END) as overdue_31_60'),
                DB::raw('SUM(CASE WHEN DATEDIFF(?, si.due_at) > 60 THEN si.total - si.paid_amount ELSE 0 END) as overdue_60_plus'),
            )
            ->whereNull('si.deleted_at')
            ->whereIn('si.status', ['issued', 'partial'])
            ->groupBy('c.id', 'c.code', 'c.name')
            ->orderByDesc('outstanding')
            ->addBinding([$safeAsOf, $safeAsOf, $safeAsOf, $safeAsOf], 'select')
            ->get();
    }
}
