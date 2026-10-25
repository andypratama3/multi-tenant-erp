<?php

namespace App\Domain\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only ledger per ADR 0003. Updates and deletes are forbidden.
 *
 * @property string $id
 * @property string $product_id
 * @property string $warehouse_id
 * @property string $movement_type
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string $quantity
 * @property string $unit_cost
 * @property string $running_balance
 * @property string|null $notes
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property-read Product $product
 * @property-read Warehouse $warehouse
 * @property-read User|null $creator
 */
class StockLedger extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'stock_ledgers';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'running_balance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'running_balance' => 'decimal:3',
    ];

    /**
     * Prevent any update to an existing ledger row (append-only).
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->exists && $this->isDirty()) {
            throw new LogicException('StockLedger is append-only and cannot be updated.');
        }

        return parent::save($options);
    }

    /**
     * Prevent deletion of ledger rows (append-only).
     */
    public function delete(): ?bool
    {
        throw new LogicException('StockLedger is append-only and cannot be deleted.');
    }

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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
