<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReview extends Model
{
    use HasUuids;

    public const TARGET_VENUE = 'venue';

    public const TARGET_COACH = 'coach';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'target_type',
        'target_id',
        'rating',
        'rating_location',
        'rating_amenities',
        'rating_price',
        'body',
        'status',
        'profanity_flag',
        'moderated_by_user_id',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'rating_location' => 'integer',
            'rating_amenities' => 'integer',
            'rating_price' => 'integer',
            'profanity_flag' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function targetVenue(): ?CourtClient
    {
        if ($this->target_type !== self::TARGET_VENUE) {
            return null;
        }

        return CourtClient::query()->find($this->target_id);
    }

    public function targetCoach(): ?User
    {
        if ($this->target_type !== self::TARGET_COACH) {
            return null;
        }

        return User::query()->find($this->target_id);
    }
}
