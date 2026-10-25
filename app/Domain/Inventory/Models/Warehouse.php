<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, StockBalance> $stockBalances
 * @property-read Collection<int, StockLedger> $stockLedgers
 */
class Warehouse extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** @return HasMany<StockBalance, $this> */
    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class, 'warehouse_id');
    }

    /** @return HasMany<StockLedger, $this> */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'warehouse_id');
    }
}
