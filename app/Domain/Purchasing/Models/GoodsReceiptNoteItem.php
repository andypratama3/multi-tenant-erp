<?php

namespace App\Domain\Purchasing\Models;

use App\Domain\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $goods_receipt_note_id
 * @property string $purchase_order_item_id
 * @property string $product_id
 * @property string $quantity_received
 * @property string $unit_cost
 * @property string|null $lot_number
 * @property Carbon|null $expiry_date
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
        'lot_number',
        'expiry_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'expiry_date' => 'date',
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
