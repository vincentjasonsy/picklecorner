<?php

namespace Database\Seeders;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserReview;
use App\Models\UserType;
use App\Services\UserReviewAggregateService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Approved member reviews for demo venues ({@see CourtClientSeeder} slug seed-venue-##).
 * Re-runnable: replaces existing seeded venue reviews, then syncs public_rating_* on each venue.
 */
class VenueReviewSeeder extends Seeder
{
    /** @var list<string> */
    private const BODIES = [
        'Great lights and the staff are welcoming.',
        'Courts were clean and booking online was easy.',
        'Solid venue — will book here again.',
        'Good value for the area. Peak hours get busy.',
        'Nice surface; locker area could be bigger but overall great.',
        'Easy to find parking. Played twice this month.',
        'Friendly front desk and fair pricing.',
        'Love the indoor courts — AC works well.',
        'Outdoor courts drain fast after rain. Recommended.',
        'Family-friendly vibe. See you next week.',
    ];

    public function run(): void
    {
        $playerTypeId = UserType::query()->where('slug', UserType::SLUG_USER)->value('id');
        $superAdminTypeId = UserType::query()->where('slug', UserType::SLUG_SUPER_ADMIN)->value('id');
        if ($playerTypeId === null || $superAdminTypeId === null) {
            $this->command?->warn('User types missing; skip venue reviews.');

            return;
        }

        $players = User::query()
            ->where('user_type_id', $playerTypeId)
            ->orderBy('email')
            ->get();
        if ($players->isEmpty()) {
            $this->command?->warn('No player users; skip venue reviews.');

            return;
        }

        $moderator = User::query()->where('user_type_id', $superAdminTypeId)->orderBy('id')->first();
        if ($moderator === null) {
            $this->command?->warn('No super admin; skip venue reviews.');

            return;
        }

        $venues = CourtClient::query()
            ->where('slug', 'like', 'seed-venue-%')
            ->orderBy('slug')
            ->get();
        if ($venues->isEmpty()) {
            $this->command?->warn('No seed-venue-* court clients; skip venue reviews.');

            return;
        }

        UserReview::query()
            ->where('target_type', UserReview::TARGET_VENUE)
            ->whereIn('target_id', $venues->pluck('id'))
            ->delete();

        $tz = config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        foreach ($venues->values() as $index => $venue) {
            $n = 4 + ($index % 7);
            $n = min($n, $players->count());

            for ($k = 0; $k < $n; $k++) {
                $player = $players[($index + $k) % $players->count()];
                $ratingLocation = 3 + (($index * 5 + $k * 2) % 3);
                $ratingAmenities = 3 + (($index * 3 + $k * 7) % 3);
                $ratingPrice = 3 + (($k * 11 + $index) % 3);
                $rating = (int) round(($ratingLocation + $ratingAmenities + $ratingPrice) / 3);
                $body = self::BODIES[($index + $k) % count(self::BODIES)];
                $moderatedAt = $now->copy()->subDays(1 + (($index * 3 + $k) % 45));

                UserReview::query()->create([
                    'user_id' => $player->id,
                    'target_type' => UserReview::TARGET_VENUE,
                    'target_id' => $venue->id,
                    'rating' => $rating,
                    'rating_location' => $ratingLocation,
                    'rating_amenities' => $ratingAmenities,
                    'rating_price' => $ratingPrice,
                    'body' => $body,
                    'status' => UserReview::STATUS_APPROVED,
                    'profanity_flag' => false,
                    'moderated_by_user_id' => $moderator->id,
                    'moderated_at' => $moderatedAt,
                ]);
            }

            UserReviewAggregateService::syncVenue($venue->fresh());
        }

        $this->command?->info('Seeded approved venue reviews for '.$venues->count().' demo venue(s).');
    }
}
