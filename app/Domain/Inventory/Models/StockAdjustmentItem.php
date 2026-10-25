<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $stock_adjustment_id
 * @property string $product_id
 * @property string $system_quantity
 * @property string $counted_quantity
 * @property string $difference_quantity
 * @property string $unit_cost
 */
class StockAdjustmentItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'difference_quantity',
        'unit_cost',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:3',
            'counted_quantity' => 'decimal:3',
            'difference_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<StockAdjustment, $this> */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
