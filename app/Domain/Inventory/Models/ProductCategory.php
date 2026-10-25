<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $parent_id
 * @property bool $is_active
 * @property bool $requires_expiry
 * @property int|null $default_shelf_life_days
 * @property int $default_expiry_alert_days
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ProductCategory|null $parent
 * @property-read Collection<int, ProductCategory> $children
 * @property-read Collection<int, Product> $products
 */
class ProductCategory extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'is_active',
        'requires_expiry',
        'default_shelf_life_days',
        'default_expiry_alert_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_expiry' => 'boolean',
        'default_shelf_life_days' => 'integer',
        'default_expiry_alert_days' => 'integer',
    ];

    /** @return BelongsTo<ProductCategory, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /** @return HasMany<ProductCategory, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }
}
