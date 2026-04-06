<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenPlayParticipant extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /** Host removed an accepted or pending player (row kept so they can see why). */
    public const STATUS_REMOVED_BY_HOST = 'removed_by_host';

    /** Joiner slots are full; player is queued for if a spot opens (does not count toward max until accepted). */
    public const STATUS_WAITING_LIST = 'waiting_list';

    public const CLOSURE_WRONG_TRANSACTION = 'wrong_transaction';

    public const CLOSURE_FULL_SLOTS = 'full_slots';

    public const CLOSURE_OTHER = 'other';

    /** @return list<string> */
    public static function statusValues(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
            self::STATUS_REMOVED_BY_HOST,
            self::STATUS_WAITING_LIST,
        ];
    }

    /** @return list<string> */
    public static function hostClosureReasonValues(): array
    {
        return [
            self::CLOSURE_WRONG_TRANSACTION,
            self::CLOSURE_FULL_SLOTS,
            self::CLOSURE_OTHER,
        ];
    }

    public static function hostClosureReasonLabel(string $reason): string
    {
        return match ($reason) {
            self::CLOSURE_WRONG_TRANSACTION => 'Wrong or unverifiable transaction reference',
            self::CLOSURE_FULL_SLOTS => 'No slots left',
            self::CLOSURE_OTHER => 'Other',
            default => $reason,
        };
    }

    protected $fillable = [
        'booking_id',
        'user_id',
        'status',
        'paid_at',
        'joiner_note',
        'gcash_reference',
        'host_closure_reason',
        'host_closure_message',
        'host_closed_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'host_closed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
