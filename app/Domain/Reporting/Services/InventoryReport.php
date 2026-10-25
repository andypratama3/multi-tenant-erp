<?php

namespace App\Domain\Reporting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryReport
{
    /**
     * Returns current stock valuation per product per warehouse.
     *
     * @return Collection<int, \stdClass>
     */
    public function stockValuation(): Collection
    {
        return DB::table('stock_balances as sb')
            ->join('products as p', 'p.id', '=', 'sb.product_id')
            ->join('warehouses as w', 'w.id', '=', 'sb.warehouse_id')
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->select(
                'p.sku',
                'p.name as product_name',
                'w.code as warehouse_code',
                'w.name as warehouse_name',
                'u.symbol as unit',
                'sb.quantity',
                'sb.average_cost',
                DB::raw('sb.quantity * sb.average_cost as total_value'),
                'sb.last_movement_at',
            )
            ->where('p.is_stockable', true)
            ->whereNull('p.deleted_at')
            ->whereNull('w.deleted_at')
            ->orderBy('p.sku')
            ->get();
    }

    /**
     * Returns slow-moving stock (no movement in N days).
     *
     * @return Collection<int, \stdClass>
     */
    public function slowMoving(int $days = 90): Collection
    {
        return DB::table('stock_balances as sb')
            ->join('products as p', 'p.id', '=', 'sb.product_id')
            ->join('warehouses as w', 'w.id', '=', 'sb.warehouse_id')
            ->select(
                'p.sku',
                'p.name',
                'w.name as warehouse',
                'sb.quantity',
                'sb.last_movement_at',
                DB::raw('DATEDIFF(NOW(), sb.last_movement_at) as days_since_movement'),
            )
            ->where('sb.quantity', '>', 0)
            ->whereNull('p.deleted_at')
            ->where(DB::raw('DATEDIFF(NOW(), sb.last_movement_at)'), '>=', $days)
            ->orderByDesc(DB::raw('DATEDIFF(NOW(), sb.last_movement_at)'))
            ->get();
    }
}
