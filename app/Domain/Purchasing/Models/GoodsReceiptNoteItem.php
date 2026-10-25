<?php

namespace App\Domain\Purchasing\Models;

use App\Domain\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $goods_receipt_note_id
 * @property string $purchase_order_item_id
 * @property string $product_id
 * @property string $quantity_received
 * @property string $unit_cost
 */
class GoodsReceiptNoteItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'goods_receipt_note_id',
        'purchase_order_item_id',
        'product_id',
        'quantity_received',
        'unit_cost',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:3',
            'unit_cost' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<GoodsReceiptNote, $this> */
    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'goods_receipt_note_id');
    }

    /** @return BelongsTo<PurchaseOrderItem, $this> */
    public function poItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
