<?php

namespace App\Domain\Sales\Models;

use App\Domain\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $sales_order_id
 * @property string $product_id
 * @property string $quantity_ordered
 * @property string $quantity_invoiced
 * @property string $quantity_delivered
 * @property string $unit_price
 * @property string $discount_rate
 * @property string $tax_rate
 * @property string $subtotal
 */
class SalesOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'quantity_ordered',
        'quantity_invoiced',
        'quantity_delivered',
        'unit_price',
        'discount_rate',
        'tax_rate',
        'subtotal',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:3',
            'quantity_invoiced' => 'decimal:3',
            'quantity_delivered' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'discount_rate' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<SalesOrder, $this> */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
