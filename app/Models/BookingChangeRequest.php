<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingChangeRequest extends Model
{
    use HasUuids;

    public const TYPE_REFUND_CREDIT = 'refund_credit';

    public const TYPE_RESCHEDULE = 'reschedule';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'booking_id',
        'user_id',
        'court_client_id',
        'type',
        'status',
        'member_note',
        'requested_starts_at',
        'requested_ends_at',
        'offered_credit_cents',
        'resolved_credit_cents',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_starts_at' => 'datetime',
            'requested_ends_at' => 'datetime',
            'offered_credit_cents' => 'integer',
            'resolved_credit_cents' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_REFUND_CREDIT => 'Credit refund',
            self::TYPE_RESCHEDULE => 'Reschedule',
            default => $type,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            default => $status,
        };
    }
}
