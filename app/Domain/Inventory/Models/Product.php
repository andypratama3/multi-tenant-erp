<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string|null $product_category_id
 * @property string $unit_id
 * @property string $cost_price
 * @property string $selling_price
 * @property string|null $barcode
 * @property bool $is_active
 * @property bool $is_stockable
 * @property string $min_stock
 * @property string $reorder_point
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ProductCategory|null $category
 * @property-read Unit $unit
 * @property-read Collection<int, StockLedger> $stockLedgers
 * @property-read StockBalance|null $currentStock
 */
class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'sku',
        'name',
        'description',
        'product_category_id',
        'unit_id',
        'cost_price',
        'selling_price',
        'barcode',
        'is_active',
        'is_stockable',
        'min_stock',
        'reorder_point',
    ];

    protected $casts = [
        'cost_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'min_stock' => 'decimal:3',
        'reorder_point' => 'decimal:3',
        'is_active' => 'boolean',
        'is_stockable' => 'boolean',
    ];

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /** @return HasMany<StockLedger, $this> */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'product_id');
    }

    /**
     * Returns the current stock balance.
     * For multi-warehouse, filter per warehouse_id externally.
     *
     * @return HasOne<StockBalance, $this>
     */
    public function currentStock(): HasOne
    {
        return $this->hasOne(StockBalance::class, 'product_id');
    }
}
