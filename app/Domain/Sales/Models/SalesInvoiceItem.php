<?php

namespace App\Domain\Sales\Models;

use App\Domain\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $sales_invoice_id
 * @property string $product_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $discount_rate
 * @property string $tax_rate
 * @property string $subtotal
 */
class SalesInvoiceItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_rate',
        'tax_rate',
        'subtotal',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'discount_rate' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<SalesInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
