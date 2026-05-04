<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserVenueCreditLedgerEntry extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    public const ENTRY_TYPE_REFUND = 'refund_credit';

    /** Manual pending booking denied by venue admin (desk / member request queue). */
    public const ENTRY_TYPE_DESK_DENIAL = 'desk_denial_credit';

    public const ENTRY_TYPE_CHECKOUT_REDEEM = 'checkout_redeem';

    protected $fillable = [
        'user_venue_credit_id',
        'amount_cents',
        'balance_after_cents',
        'entry_type',
        'source_type',
        'source_id',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'balance_after_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function userVenueCredit(): BelongsTo
    {
        return $this->belongsTo(UserVenueCredit::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
