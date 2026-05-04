<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'court_client_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courtClient(): BelongsTo
    {
        return $this->belongsTo(CourtClient::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Limits logs to booking lifecycle and checkout gift redemption — for court admin customer summary only.
     *
     * @param  Builder<ActivityLog>  $query
     * @return Builder<ActivityLog>
     */
    public function scopeBookingRelatedForCustomerSummary(Builder $query): Builder
    {
        $bookingMorph = (new Booking)->getMorphClass();

        return $query->where(function (Builder $q) use ($bookingMorph): void {
            $q->where('action', 'like', 'booking.%')
                ->orWhere('subject_type', $bookingMorph)
                ->orWhere('action', 'gift_card.redeemed');
        });
    }
}
