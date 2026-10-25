<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Links a consumed quantity from a stock lot to its append-only ledger row.
 *
 * @property string $id
 * @property string $stock_lot_id
 * @property string $stock_ledger_id
 * @property string $quantity
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StockLot $stockLot
 * @property-read StockLedger $stockLedger
 */
class StockLotAllocation extends Model
{
    use HasUuids;

    protected $table = 'stock_lot_allocations';

    protected $fillable = [
        'stock_lot_id',
        'stock_ledger_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    /** @return BelongsTo<StockLot, $this> */
    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
    }

    /** @return BelongsTo<StockLedger, $this> */
    public function stockLedger(): BelongsTo
    {
        return $this->belongsTo(StockLedger::class);
    }
}
