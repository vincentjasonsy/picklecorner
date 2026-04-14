<?php

namespace Database\Seeders;

use App\Models\CoachCourt;
use App\Models\CoachHourAvailability;
use App\Models\CoachProfile;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\CourtClientBootstrap;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CourtClientSeeder extends Seeder
{
    private const VENUE_COUNT = 20;

    /**
     * One court client per court admin (1:1). Run after DemoUsersSeeder.
     *
     * Each venue: 5 indoor courts; outdoor count alternates 3 and 4 across venues.
     * One front-desk user per venue (password: password).
     */
    public function run(): void
    {
        $courtAdminTypeId = UserType::query()
            ->where('slug', UserType::SLUG_COURT_ADMIN)
            ->value('id');

        $admins = User::query()
            ->where('user_type_id', $courtAdminTypeId)
            ->orderBy('id')
            ->get();

        if ($admins->count() !== self::VENUE_COUNT) {
            throw new \RuntimeException(
                'Expected exactly '.self::VENUE_COUNT.' court admin users before seeding court clients; found '.$admins->count()
            );
        }

        $cities = [
            'Taguig', 'Quezon City', 'Cebu City', 'Davao City', 'Makati', 'Pasig', 'Mandaluyong',
            'Parañaque', 'Las Piñas', 'Marikina', 'Antipolo', 'Iloilo City', 'Bacolod', 'Cagayan de Oro',
            'Baguio', 'Santa Rosa', 'Batangas City', 'General Santos', 'Zamboanga City', 'Butuan',
        ];

        for ($index = 0; $index < self::VENUE_COUNT; $index++) {
            $i = $index + 1;
            $slug = 'seed-venue-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $city = $cities[$index];
            $name = "Pickle Hub {$i} — {$city}";

            // Alternate 3 and 4 outdoor courts per venue.
            $outdoorCount = $i % 2 === 1 ? 3 : 4;

            $hourlyBase = 25000 + ($index * 750);
            $peakBase = $hourlyBase + 12000;

            $client = CourtClient::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'city' => $city,
                    'admin_user_id' => $admins[$index]->id,
                    'subscription_tier' => CourtClient::TIER_PREMIUM,
                    'is_active' => true,
                    'hourly_rate_cents' => $hourlyBase,
                    'peak_hourly_rate_cents' => $peakBase,
                    'currency' => 'PHP',
                    'public_rating_average' => round(4.3 + (($index % 7) * 0.08), 1),
                    'public_rating_count' => 80 + ($index * 17),
                ]
            );

            CourtClientBootstrap::seedVenueCourtsIfEmpty($client, $outdoorCount, 5);
        }

        $this->seedDemoCoachScenario();
        $this->seedDeskUsers();
    }

    /** One front-desk user per seeded venue (password: password). */
    private function seedDeskUsers(): void
    {
        $deskTypeId = UserType::query()
            ->where('slug', UserType::SLUG_COURT_CLIENT_DESK)
            ->value('id');

        if ($deskTypeId === null) {
            return;
        }

        $clients = CourtClient::query()->where('slug', 'like', 'seed-venue-%')->orderBy('slug')->get();
        if ($clients->count() < self::VENUE_COUNT) {
            return;
        }

        $verified = ['email_verified_at' => now()];

        for ($i = 1; $i <= self::VENUE_COUNT; $i++) {
            User::query()->updateOrCreate(
                ['email' => "desk{$i}@picklecorner.ph"],
                [
                    'name' => "Desk {$i}",
                    'password' => 'password',
                    'user_type_id' => $deskTypeId,
                    'desk_court_client_id' => $clients[$i - 1]->id,
                    ...$verified,
                ],
            );
        }
    }

    /** Links demo coach1 to a court and tomorrow’s hours so “book with coach” can be tried after migrate + seed. */
    private function seedDemoCoachScenario(): void
    {
        $coach = User::query()->where('email', 'coach1@picklecorner.ph')->first();
        if ($coach === null) {
            return;
        }

        CoachProfile::query()->firstOrCreate(
            ['user_id' => $coach->id],
            [
                'hourly_rate_cents' => 80_000,
                'currency' => 'PHP',
                'bio' => 'Demo coach — players can add you when booking this court.',
            ],
        );

        $court = Court::query()->orderBy('court_client_id')->orderBy('sort_order')->first();
        if ($court === null) {
            return;
        }

        CoachCourt::query()->firstOrCreate(
            [
                'coach_user_id' => $coach->id,
                'court_id' => $court->id,
            ],
            [],
        );

        $tomorrow = Carbon::tomorrow(config('app.timezone', 'UTC'))->format('Y-m-d');
        foreach ([10, 11, 12, 13, 14, 15] as $hour) {
            CoachHourAvailability::query()->firstOrCreate(
                [
                    'coach_user_id' => $coach->id,
                    'court_id' => $court->id,
                    'date' => $tomorrow,
                    'hour' => $hour,
                ],
                [],
            );
        }
    }
}
