<?php

namespace App\Models;

use App\Services\BookingCheckoutSnapshot;
use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasUuids;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DENIED = 'denied';

    public const STATUS_COMPLETED = 'completed';

    /** @return list<string> */
    public static function statusesBlockingCourtAvailability(): array
    {
        return [
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_CONFIRMED,
            self::STATUS_COMPLETED,
        ];
    }

    public static function statusDisplayLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING_APPROVAL => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_DENIED => 'Denied',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
            default => $status,
        };
    }

    public const PAYMENT_GCASH = 'gcash';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_OTHER = 'other';

    public const PAYMENT_PAYMONGO = 'paymongo';

    /** @return list<string> */
    public static function paymentMethodOptions(): array
    {
        $base = [
            self::PAYMENT_GCASH,
            self::PAYMENT_BANK_TRANSFER,
            self::PAYMENT_CASH,
            self::PAYMENT_OTHER,
        ];

        if (config('paymongo.enabled') && config('paymongo.secret_key') !== '') {
            array_unshift($base, self::PAYMENT_PAYMONGO);
        }

        return $base;
    }

    /**
     * Desk, venue admin, and super-admin manual booking — record how the customer paid (no hosted PayMongo).
     *
     * @return list<string>
     */
    public static function paymentMethodOptionsDesk(): array
    {
        return [
            self::PAYMENT_GCASH,
            self::PAYMENT_BANK_TRANSFER,
            self::PAYMENT_CASH,
            self::PAYMENT_OTHER,
        ];
    }

    public static function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            self::PAYMENT_GCASH => 'GCash',
            self::PAYMENT_BANK_TRANSFER => 'Bank transfer',
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_OTHER => 'Other',
            self::PAYMENT_PAYMONGO => 'PayMongo (GCash / QRPh)',
            default => $method,
        };
    }

    protected $fillable = [
        'court_client_id',
        'booking_request_id',
        'court_id',
        'user_id',
        'desk_submitted_by',
        'starts_at',
        'ends_at',
        'status',
        'amount_cents',
        'currency',
        'notes',
        'gift_card_id',
        'gift_card_redeemed_cents',
        'venue_credit_redeemed_cents',
        'payment_method',
        'payment_reference',
        'payment_proof_path',
        'coach_user_id',
        'coach_fee_cents',
        'platform_booking_fee_cents',
        'checkout_snapshot',
        'is_open_play',
        'open_play_max_slots',
        'open_play_public_notes',
        'open_play_host_payment_details',
        'open_play_external_contact',
        'open_play_refund_policy',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'gift_card_redeemed_cents' => 'integer',
            'venue_credit_redeemed_cents' => 'integer',
            'coach_fee_cents' => 'integer',
            'platform_booking_fee_cents' => 'integer',
            'checkout_snapshot' => 'array',
            'is_open_play' => 'boolean',
            'open_play_max_slots' => 'integer',
        ];
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function deskSubmitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desk_submitted_by');
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    /** @return HasMany<OpenPlayParticipant, $this> */
    public function openPlayParticipants(): HasMany
    {
        return $this->hasMany(OpenPlayParticipant::class);
    }

    public function acceptedOpenPlayParticipantsCount(): int
    {
        if (! $this->is_open_play) {
            return 0;
        }

        return $this->openPlayParticipants()
            ->where('status', OpenPlayParticipant::STATUS_ACCEPTED)
            ->count();
    }

    /**
     * Remaining slots for additional players (host is not counted; max_slots = joiner cap).
     */
    public function openPlaySlotsRemaining(): int
    {
        if (! $this->is_open_play || $this->open_play_max_slots === null) {
            return 0;
        }

        return max(0, $this->open_play_max_slots - $this->acceptedOpenPlayParticipantsCount());
    }

    public function allowsOpenPlayJoinRequests(): bool
    {
        if (! $this->is_open_play || $this->open_play_max_slots === null) {
            return false;
        }

        if ($this->status !== self::STATUS_CONFIRMED) {
            return false;
        }

        if ($this->starts_at === null || $this->starts_at->lte(now())) {
            return false;
        }

        return $this->openPlaySlotsRemaining() > 0;
    }

    /**
     * Confirmed future open play — joiners can queue on the waiting list when accepted slots are full.
     */
    public function allowsOpenPlayWaitlistRequests(): bool
    {
        if (! $this->is_open_play || $this->open_play_max_slots === null) {
            return false;
        }

        if ($this->status !== self::STATUS_CONFIRMED) {
            return false;
        }

        if ($this->starts_at === null || $this->starts_at->lte(now())) {
            return false;
        }

        return true;
    }

    /** Invoices that include this booking (each booking may appear on at most one invoice). */
    public function courtClientInvoices(): BelongsToMany
    {
        return $this->belongsToMany(CourtClientInvoice::class, 'invoice_bookings', 'booking_id', 'court_client_invoice_id')
            ->withPivot('amount_cents')
            ->withTimestamps();
    }

    /** @return HasMany<BookingChangeRequest, $this> */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(BookingChangeRequest::class);
    }

    public function paymentProofUrl(): ?string
    {
        if ($this->payment_proof_path === null || $this->payment_proof_path === '') {
            return null;
        }

        return PublicStorageUrl::forPath($this->payment_proof_path);
    }

    /**
     * One id per submission: shared by every court row from the same request.
     * Rows without a batch id (legacy) use this booking’s row id.
     */
    public function transactionReference(): string
    {
        $rid = $this->booking_request_id;
        if ($rid !== null && $rid !== '') {
            return (string) $rid;
        }

        return (string) $this->id;
    }

    /** Compact label for dense tables; pair with {@see transactionReference()} in a title attribute. */
    public function transactionReferenceShort(): string
    {
        $ref = $this->transactionReference();

        return strlen($ref) > 12 ? substr($ref, 0, 8).'…' : $ref;
    }

    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /** Confirmed or completed bookings (excludes pending, denied, cancelled). */
    public function scopeCountingTowardRevenue($query)
    {
        return $query->whereIn('status', [self::STATUS_CONFIRMED, self::STATUS_COMPLETED]);
    }

    /**
     * Desk / admin manual bookings only (not member Book now checkout), paid outside hosted PayMongo.
     */
    public function scopeEligibleForCourtClientInvoice($query)
    {
        return $query
            ->where('checkout_snapshot->source', BookingCheckoutSnapshot::SOURCE_MANUAL_DESK)
            ->where(function ($q) {
                $q->whereNull('payment_method')
                    ->orWhere('payment_method', '<>', self::PAYMENT_PAYMONGO);
            });
    }
}
