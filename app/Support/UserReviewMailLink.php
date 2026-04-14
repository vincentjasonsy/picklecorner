<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserReview;
use App\Services\UserReviewEligibility;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;

final class UserReviewMailLink
{
    public static function linkTtlDays(): int
    {
        $days = (int) config('booking.review_mail_link_ttl_days', 30);

        return max(1, $days);
    }

    /**
     * Signed URL for the member to open the review form for a venue or coach.
     * The recipient must sign in as {@see User} (if not already); eligibility rules
     * in {@see UserReviewEligibility} still apply at submit time.
     */
    public static function signedUrl(
        User $user,
        string $targetType,
        string $targetId,
        ?CarbonInterface $expiresAt = null,
    ): string {
        if (! in_array($targetType, [UserReview::TARGET_VENUE, UserReview::TARGET_COACH], true)) {
            throw new \InvalidArgumentException('Invalid review target type.');
        }

        $expiresAt ??= now()->addDays(self::linkTtlDays());

        return URL::temporarySignedRoute(
            'reviews.write-signed',
            $expiresAt,
            [
                'user' => $user->getKey(),
                'target_type' => $targetType,
                'target_id' => $targetId,
            ],
        );
    }
}
