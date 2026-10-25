<?php

namespace App\Domain\Purchasing\Models;

use App\Models\User;
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
 * @property string $ap_number
 * @property string|null $supplier_invoice_number
 * @property string $grn_id
 * @property string $supplier_id
 * @property string $status
 * @property Carbon $invoice_date
 * @property Carbon $due_date
 * @property string $subtotal
 * @property string $total
 * @property string $paid_amount
 * @property string|null $notes
 * @property string|null $created_by
 */
class PurchaseInvoice extends Model
{
    use HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'ap_number',
        'supplier_invoice_number',
        'grn_id',
        'supplier_id',
        'status',
        'invoice_date',
        'due_date',
        'subtotal',
        'total',
        'paid_amount',
        'notes',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /** @return BelongsTo<GoodsReceiptNote, $this> */
    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id');
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<SupplierPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
