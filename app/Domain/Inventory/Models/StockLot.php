<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A received batch of stock with an optional expiry date.
 *
 * @property string $id
 * @property string $product_id
 * @property string $warehouse_id
 * @property string|null $source_type
 * @property string|null $source_id
 * @property string|null $lot_number
 * @property Carbon $received_at
 * @property Carbon|null $expiry_date
 * @property string $quantity_received
 * @property string $quantity_remaining
 * @property string $unit_cost
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 * @property-read Warehouse $warehouse
 * @property-read Collection<int, StockLotAllocation> $allocations
 * @property-read int|null $days_until_expiry
 * @property-read bool $is_expired
 * @property-read string $expiry_status
 */
class StockLot extends Model
{
    use HasUuids;

    public const STATUS_FRESH = 'fresh';

    public const STATUS_EXPIRING = 'expiring';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'stock_lots';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'source_type',
        'source_id',
        'lot_number',
        'received_at',
        'expiry_date',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'date',
        'expiry_date' => 'date',
        'quantity_received' => 'decimal:3',
        'quantity_remaining' => 'decimal:3',
        'unit_cost' => 'decimal:4',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return MorphTo<Model, $this> */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<StockLotAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(StockLotAllocation::class);
    }

    /** @param Builder<StockLot> $query */
    public function scopeWithRemainingStock(Builder $query): void
    {
        $query->where('quantity_remaining', '>', 0);
    }

    /** @param Builder<StockLot> $query */
    public function scopeSellable(Builder $query): void
    {
        $query
            ->where('quantity_remaining', '>', 0)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', today()->toDateString());
            });
    }

    /** @param Builder<StockLot> $query */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', today()->addDays($days)->toDateString());
    }

    /** @param Builder<StockLot> $query */
    public function scopeExpired(Builder $query): void
    {
        $query
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today()->toDateString());
    }

    /** @return Attribute<int|null, never> */
    protected function daysUntilExpiry(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->expiry_date === null) {
                return null;
            }

            return (int) today()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
        });
    }

    /** @return Attribute<bool, never> */
    protected function isExpired(): Attribute
    {
        return Attribute::get(fn (): bool => $this->expiry_date !== null && $this->expiry_date->lt(today()));
    }

    /** @return Attribute<string, never> */
    protected function expiryStatus(): Attribute
    {
        return Attribute::get(function (): string {
            $days = $this->days_until_expiry;

            if ($days === null) {
                return self::STATUS_FRESH;
            }

            if ($days < 0) {
                return self::STATUS_EXPIRED;
            }

            $alertDays = $this->relationLoaded('product')
                ? $this->product->expiry_alert_days
                : (int) (Product::query()->whereKey($this->product_id)->value('expiry_alert_days') ?? 7);

            return $days <= $alertDays ? self::STATUS_EXPIRING : self::STATUS_FRESH;
        });
    }
}
