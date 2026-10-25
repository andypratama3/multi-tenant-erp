<?php

namespace App\Domain\Tax\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tax_rate_id
 * @property string $document_type
 * @property string $document_id
 * @property string $base_amount
 * @property string $tax_amount
 * @property Carbon $transaction_date
 * @property string|null $tax_number
 * @property string $direction in|out
 * @property string|null $created_by
 */
class TaxTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'tax_rate_id',
        'document_type',
        'document_id',
        'base_amount',
        'tax_amount',
        'transaction_date',
        'tax_number',
        'direction',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
