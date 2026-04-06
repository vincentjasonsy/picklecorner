<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

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

    /** @return list<string> */
    public static function paymentMethodOptions(): array
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
            default => $method,
        };
    }

    protected $fillable = [
        'court_client_id',
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
        'payment_method',
        'payment_reference',
        'payment_proof_path',
        'coach_user_id',
        'coach_fee_cents',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'gift_card_redeemed_cents' => 'integer',
            'coach_fee_cents' => 'integer',
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

    /** Invoices that include this booking (each booking may appear on at most one invoice). */
    public function courtClientInvoices(): BelongsToMany
    {
        return $this->belongsToMany(CourtClientInvoice::class, 'invoice_bookings', 'booking_id', 'court_client_invoice_id')
            ->withPivot('amount_cents')
            ->withTimestamps();
    }

    public function paymentProofUrl(): ?string
    {
        if ($this->payment_proof_path === null || $this->payment_proof_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->payment_proof_path);
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
}
