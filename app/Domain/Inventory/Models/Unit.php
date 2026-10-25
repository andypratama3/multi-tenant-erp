<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $symbol
 * @property bool $is_base
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Product> $products
 */
class Unit extends Model
{
    use HasUuids;

    protected $table = 'units';

    protected $fillable = [
        'name',
        'symbol',
        'is_base',
    ];

    protected $casts = [
        'is_base' => 'boolean',
    ];

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}
