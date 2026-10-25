<?php

namespace App\Domain\Tax\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $type ppn|pph_21|pph_22|pph_23|pph_final|other
 * @property string $rate
 * @property bool $is_active
 * @property string|null $description
 */
class TaxRate extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'rate',
        'is_active',
        'description',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<TaxTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(TaxTransaction::class);
    }
}
