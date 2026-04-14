<?php

namespace App\Services;

use App\Models\CoachProfile;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserReview;

final class UserReviewAggregateService
{
    public static function syncVenue(CourtClient $client): void
    {
        $approvedVenue = fn () => UserReview::query()
            ->where('target_type', UserReview::TARGET_VENUE)
            ->where('target_id', $client->id)
            ->where('status', UserReview::STATUS_APPROVED);

        $count = (int) $approvedVenue()->count();
        if ($count === 0) {
            $client->forceFill([
                'public_rating_average' => null,
                'public_rating_count' => 0,
                'public_rating_location' => null,
                'public_rating_amenities' => null,
                'public_rating_price' => null,
            ])->save();

            return;
        }

        $avg = round((float) $approvedVenue()->avg('rating'), 1);

        $locQ = $approvedVenue()->whereNotNull('rating_location');
        $locCount = (int) $locQ->count();
        $avgLoc = $locCount > 0 ? round((float) $locQ->avg('rating_location'), 1) : null;

        $amenQ = $approvedVenue()->whereNotNull('rating_amenities');
        $amenCount = (int) $amenQ->count();
        $avgAmen = $amenCount > 0 ? round((float) $amenQ->avg('rating_amenities'), 1) : null;

        $priceQ = $approvedVenue()->whereNotNull('rating_price');
        $priceCount = (int) $priceQ->count();
        $avgPrice = $priceCount > 0 ? round((float) $priceQ->avg('rating_price'), 1) : null;

        $client->forceFill([
            'public_rating_average' => $avg,
            'public_rating_count' => $count,
            'public_rating_location' => $avgLoc,
            'public_rating_amenities' => $avgAmen,
            'public_rating_price' => $avgPrice,
        ])->save();
    }

    public static function syncCoach(User $coach): void
    {
        if (! $coach->isCoach()) {
            return;
        }

        $approvedCoach = fn () => UserReview::query()
            ->where('target_type', UserReview::TARGET_COACH)
            ->where('target_id', $coach->id)
            ->where('status', UserReview::STATUS_APPROVED);

        $count = (int) $approvedCoach()->count();
        $profile = CoachProfile::query()->firstOrCreate(
            ['user_id' => $coach->id],
            ['hourly_rate_cents' => 0, 'currency' => 'PHP', 'bio' => null],
        );

        if ($count === 0) {
            $profile->forceFill([
                'public_rating_average' => null,
                'public_rating_count' => 0,
            ])->save();

            return;
        }

        $avg = round((float) $approvedCoach()->avg('rating'), 1);
        $profile->forceFill([
            'public_rating_average' => $avg,
            'public_rating_count' => $count,
        ])->save();
    }
}
