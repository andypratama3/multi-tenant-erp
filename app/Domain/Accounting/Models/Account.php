<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chart of Accounts entry (ADR 0004 — double-entry).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $type asset|liability|equity|revenue|expense
 * @property string $normal_balance debit|credit
 * @property string|null $parent_id
 * @property bool $is_leaf
 * @property bool $is_active
 * @property string|null $description
 */
class Account extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'parent_id',
        'is_leaf',
        'is_active',
        'description',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_leaf' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /** @return HasMany<Account, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /** @return HasMany<JournalLine, $this> */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
