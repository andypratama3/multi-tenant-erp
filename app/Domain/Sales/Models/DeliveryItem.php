<?php

namespace App\Domain\Sales\Models;

use App\Domain\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $delivery_id
 * @property string $sales_order_item_id
 * @property string $product_id
 * @property string $quantity_delivered
 */
class DeliveryItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'delivery_id',
        'sales_order_item_id',
        'product_id',
        'quantity_delivered',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_delivered' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<Delivery, $this> */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /** @return BelongsTo<SalesOrderItem, $this> */
    public function soItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
