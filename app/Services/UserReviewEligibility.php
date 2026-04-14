<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserReview;

final class UserReviewEligibility
{
    public static function windowDays(): int
    {
        $days = (int) config('booking.review_window_days_after_booking_ends', 2);

        return max(1, $days);
    }

    /**
     * Whether the member may create or update a pending review for this target
     * (confirmed/completed booking ended, and still within the post-end window).
     */
    public static function maySubmitOrUpdate(User $user, string $targetType, string $targetId): bool
    {
        if (! in_array($targetType, [UserReview::TARGET_VENUE, UserReview::TARGET_COACH], true)) {
            return false;
        }

        $now = now();
        $windowStart = $now->copy()->subDays(self::windowDays());

        $query = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED])
            ->where('ends_at', '<=', $now)
            ->where('ends_at', '>=', $windowStart);

        if ($targetType === UserReview::TARGET_VENUE) {
            $query->where('court_client_id', $targetId);
        } else {
            $query->where('coach_user_id', $targetId);
        }

        return $query->exists();
    }
}
