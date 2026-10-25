<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Denormalized current stock balance per product per warehouse (ADR 0005).
 *
 * @property string $id
 * @property string $product_id
 * @property string $warehouse_id
 * @property string $quantity
 * @property string $average_cost
 * @property Carbon|null $last_movement_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 * @property-read Warehouse $warehouse
 */
class StockBalance extends Model
{
    use HasUuids;

    protected $table = 'stock_balances';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'average_cost',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'average_cost' => 'decimal:4',
        'last_movement_at' => 'datetime',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
