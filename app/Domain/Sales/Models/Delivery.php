<?php

namespace App\Domain\Sales\Models;

use App\Domain\Inventory\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property string $id
 * @property string $delivery_number
 * @property string $sales_order_id
 * @property string|null $warehouse_id
 * @property string $status draft|shipped|delivered|returned
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property string|null $notes
 * @property string|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read SalesOrder $salesOrder
 * @property-read Collection<int, DeliveryItem> $items
 * @property-read User|null $creator
 */
class Delivery extends Model
{
    use HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'delivery_number',
        'sales_order_id',
        'warehouse_id',
        'status',
        'shipped_at',
        'delivered_at',
        'notes',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'shipped_at' => 'date',
            'delivered_at' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    /** @return BelongsTo<SalesOrder, $this> */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return HasMany<DeliveryItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
