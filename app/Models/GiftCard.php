<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class GiftCard extends Model
{
    use HasUuids;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXHAUSTED = 'exhausted';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const VALUE_FIXED = 'fixed';

    public const VALUE_PERCENT = 'percent';

    protected $fillable = [
        'court_client_id',
        'code',
        'title',
        'event_label',
        'value_type',
        'percent_off',
        'face_value_cents',
        'balance_cents',
        'currency',
        'valid_from',
        'valid_until',
        'cancelled_at',
        'created_by',
        'notes',
        'max_redemptions_total',
        'max_redemptions_per_user',
    ];

    protected function casts(): array
    {
        return [
            'percent_off' => 'integer',
            'face_value_cents' => 'integer',
            'balance_cents' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'cancelled_at' => 'datetime',
            'max_redemptions_total' => 'integer',
            'max_redemptions_per_user' => 'integer',
        ];
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isPlatformWide(): bool
    {
        return $this->court_client_id === null;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(GiftCardRedemption::class);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isFixedValue(): bool
    {
        return $this->value_type === self::VALUE_FIXED || $this->value_type === null;
    }

    public function isPercentValue(): bool
    {
        return $this->value_type === self::VALUE_PERCENT;
    }

    /**
     * Raw discount from booking gross before the fixed-value face cap ({@see GiftCardService::debitForGrossAmount}).
     * Fixed: returns gross (cap is min(face, gross)); percent: returns floor(gross × percent / 100).
     */
    public function computeDiscountFromGross(int $grossCents): int
    {
        $grossCents = max(0, $grossCents);

        if ($this->isPercentValue()) {
            $pct = (int) ($this->percent_off ?? 0);

            return (int) floor($grossCents * $pct / 100);
        }

        return $grossCents;
    }

    /**
     * Legacy / display: balance is no longer reduced when redeeming. Exhausted only if balance was zeroed (e.g. old data).
     */
    public function isExhausted(): bool
    {
        return $this->balance_cents <= 0;
    }

    /** Bookings that applied this gift card (each booking row counts as one use). */
    public function bookingsUsingGiftCount(): int
    {
        return Booking::query()->where('gift_card_id', $this->id)->count();
    }

    public function bookingsUsingGiftCountForUser(string $userId): int
    {
        return Booking::query()
            ->where('gift_card_id', $this->id)
            ->where('user_id', $userId)
            ->count();
    }

    /** Highest number of bookings with this card for a single guest (for validating lower per-user caps). */
    public function peakBookingsUsingGiftForSingleUser(): int
    {
        return (int) (DB::table('bookings')
            ->where('gift_card_id', $this->id)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->selectRaw('COUNT(*) as c')
            ->orderByDesc('c')
            ->limit(1)
            ->value('c') ?? 0);
    }

    public function hasReachedTotalRedemptionLimit(): bool
    {
        if ($this->max_redemptions_total === null) {
            return false;
        }

        return $this->bookingsUsingGiftCount() >= $this->max_redemptions_total;
    }

    public function redeemableNow(): bool
    {
        if ($this->isCancelled() || $this->isExhausted()) {
            return false;
        }

        $now = now();

        if ($this->valid_from !== null && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until !== null && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    public function adminStatusLabel(): string
    {
        if ($this->isCancelled()) {
            return self::STATUS_CANCELLED;
        }

        if ($this->isExhausted() || $this->hasReachedTotalRedemptionLimit()) {
            return self::STATUS_EXHAUSTED;
        }

        $now = now();
        if ($this->valid_from !== null && $now->lt($this->valid_from)) {
            return self::STATUS_SCHEDULED;
        }

        if ($this->valid_until !== null && $now->gt($this->valid_until)) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }
}
