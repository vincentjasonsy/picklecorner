<?php

namespace Database\Seeders;

use App\Models\CoachCourt;
use App\Models\CoachHourAvailability;
use App\Models\CoachProfile;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtClientGalleryImage;
use App\Models\CourtGalleryImage;
use App\Models\User;
use App\Models\UserType;
use App\Services\CourtClientBootstrap;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * One active venue, one outdoor court, one desk user. Run after {@see DemoUsersSeeder}.
 */
class CourtClientSeeder extends Seeder
{
    public const VENUE_COUNT = 1;

    /**
     * @return array{address: string, phone: string, facebook_url: string, latitude: float, longitude: float, amenities: list<string>}
     */
    private static function venuePublicDetails(): array
    {
        return [
            'address' => '7th Ave cor 26th St, Bonifacio Global City, Taguig, 1634 Metro Manila — Site 1',
            'phone' => '+63 917 555 0101',
            'facebook_url' => 'https://www.facebook.com/PickleCornerTaguig',
            'latitude' => 14.5547,
            'longitude' => 121.0484,
            'amenities' => ['Indoor & outdoor courts', 'Parking', 'Locker rooms', 'Pro shop', 'Drinking water', 'Restrooms'],
        ];
    }

    public function run(): void
    {
        $admin = User::query()->where('email', 'courtadmin@picklecorner.ph')->first();
        if ($admin === null) {
            throw new \RuntimeException('Court admin courtadmin@picklecorner.ph not found. Run DemoUsersSeeder first.');
        }

        $public = self::venuePublicDetails();

        $client = CourtClient::query()->updateOrCreate(
            ['slug' => 'seed-venue-01'],
            [
                'name' => 'Pickle Dink Tank & Rally Room — Taguig',
                'city' => 'Taguig',
                'address' => $public['address'],
                'phone' => $public['phone'],
                'facebook_url' => $public['facebook_url'],
                'latitude' => $public['latitude'],
                'longitude' => $public['longitude'],
                'amenities' => $public['amenities'],
                'admin_user_id' => $admin->id,
                'subscription_tier' => CourtClient::TIER_PREMIUM,
                'venue_status' => CourtClient::VENUE_STATUS_ACTIVE,
                'hourly_rate_cents' => 25_000,
                'peak_hourly_rate_cents' => 37_000,
                'currency' => 'PHP',
                'public_rating_average' => null,
                'public_rating_count' => 0,
            ]
        );

        CourtClientBootstrap::seedVenueCourtsIfEmpty($client, 1, 0);
        $client->load('courts');
        $this->seedVenueApprovedGallery($client, 0);
        $this->seedCourtsApprovedGallery($client, 0);
        $this->seedDemoCoachScenario();
        $this->seedDeskUser($client);

        $this->command?->info('Seeded 1 demo venue (seed-venue-01), 1 court, desk@picklecorner.ph.');
    }

    /** @return list<string> */
    private static function bundledGallerySourcePaths(): array
    {
        $paths = [
            public_path('images/slider/slide-1.svg'),
            public_path('images/slider/slide-2.svg'),
            public_path('images/courts/indoor-a.svg'),
            public_path('images/courts/outdoor-a.svg'),
            public_path('images/courts/indoor-b.svg'),
            public_path('images/courts/outdoor-b.svg'),
        ];

        return array_values(array_filter($paths, fn (string $p): bool => is_readable($p)));
    }

    private function seedVenueApprovedGallery(CourtClient $client, int $venueIndex): void
    {
        $sources = self::bundledGallerySourcePaths();
        if ($sources === []) {
            return;
        }

        CourtClientGalleryImage::query()
            ->where('court_client_id', $client->id)
            ->where('path', 'like', '%/gallery/seed-slide-%')
            ->delete();

        $disk = Storage::disk('public');
        $firstRelativePath = null;
        foreach (range(0, 3) as $sort) {
            $src = $sources[($venueIndex + $sort) % count($sources)];
            $ext = pathinfo($src, PATHINFO_EXTENSION);
            $ext = $ext !== '' ? $ext : 'svg';
            $relative = 'court-clients/'.$client->id.'/gallery/seed-slide-'.$sort.'.'.$ext;
            $disk->put($relative, File::get($src));
            if ($firstRelativePath === null) {
                $firstRelativePath = $relative;
            }
            CourtClientGalleryImage::query()->create([
                'court_client_id' => $client->id,
                'path' => $relative,
                'sort_order' => $sort,
                'alt_text' => $client->name.' — '.match ($sort) {
                    0 => 'Courts overview',
                    1 => 'Indoor play',
                    2 => 'Outdoor courts',
                    default => 'Facility',
                },
                'approved_at' => now(),
            ]);
        }

        if ($firstRelativePath !== null) {
            CourtClient::query()
                ->whereKey($client->id)
                ->where(function ($q): void {
                    $q->whereNull('cover_image_path')->orWhere('cover_image_path', '');
                })
                ->update(['cover_image_path' => $firstRelativePath]);
        }
    }

    private function seedCourtsApprovedGallery(CourtClient $client, int $venueIndex): void
    {
        $sources = self::bundledGallerySourcePaths();
        if ($sources === []) {
            return;
        }

        $disk = Storage::disk('public');
        $courtIndex = 0;
        foreach ($client->courts as $court) {
            CourtGalleryImage::query()
                ->where('court_id', $court->id)
                ->where('path', 'like', '%/gallery/seed-slide-%')
                ->delete();

            foreach (range(0, 1) as $sort) {
                $src = $sources[($venueIndex + $courtIndex + $sort) % count($sources)];
                $ext = pathinfo($src, PATHINFO_EXTENSION);
                $ext = $ext !== '' ? $ext : 'svg';
                $relative = 'courts/'.$court->id.'/gallery/seed-slide-'.$sort.'.'.$ext;
                $disk->put($relative, File::get($src));
                CourtGalleryImage::query()->create([
                    'court_id' => $court->id,
                    'path' => $relative,
                    'sort_order' => $sort,
                    'alt_text' => $court->name.' — '.($sort === 0 ? 'Play' : 'Courts'),
                    'approved_at' => now(),
                ]);
            }
            $courtIndex++;
        }
    }

    private function seedDemoCoachScenario(): void
    {
        $coach = User::query()->where('email', 'coach@picklecorner.ph')->first();
        if ($coach === null) {
            return;
        }

        CoachProfile::query()->firstOrCreate(
            ['user_id' => $coach->id],
            [
                'hourly_rate_cents' => 80_000,
                'currency' => 'PHP',
                'bio' => 'Demo coach — players can add you when booking this court.',
            ]
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
            []
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
                []
            );
        }
    }

    private function seedDeskUser(CourtClient $client): void
    {
        $deskTypeId = UserType::query()
            ->where('slug', UserType::SLUG_COURT_CLIENT_DESK)
            ->value('id');

        if ($deskTypeId === null) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'desk@picklecorner.ph'],
            [
                'name' => 'Desk',
                'password' => 'password',
                'user_type_id' => $deskTypeId,
                'desk_court_client_id' => $client->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
