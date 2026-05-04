<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Member wallet row: one balance per user per currency, redeemable at any venue using that currency.
 */
class UserVenueCredit extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'balance_cents',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'balance_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<UserVenueCreditLedgerEntry, $this> */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(UserVenueCreditLedgerEntry::class);
    }
}
