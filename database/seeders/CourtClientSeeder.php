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

class CourtClientSeeder extends Seeder
{
    /** Venues sharing the same {@see CourtClient::$city} string per cluster (within 10–15). */
    public const VENUES_PER_CITY = 12;

    /** Five demo metros; each gets {@see self::VENUES_PER_CITY} venues for featured / browse density. */
    public const VENUE_COUNT = 60;

    /**
     * @var list<string>
     */
    private const CLUSTER_CITY_NAMES = [
        'Taguig',
        'Quezon City',
        'Cebu City',
        'Davao City',
        'Makati',
    ];

    /**
     * Rotates by venue slot within each city (12 per metro). Full venue name: "{name} — {city}".
     *
     * @var list<string>
     */
    private const QUIRKY_VENUE_NAMES = [
        'The Dink Tank',
        'Third Shot Social Club',
        'Kitchen Is Closed Pickleball',
        'Sidewinder Alley Sportsplex',
        'No Man\'s Land Arena',
        'Net Gains Athletic Club',
        'Paddle Royale',
        'Baseline & Banter Courts',
        'CrossCourt Comedy Club',
        'Spin City Pickle',
        'Drop Shot Society',
        'The Good Volley Tribe',
    ];

    /**
     * Base listing for each cluster city; per-site address and map pins are derived in {@see venuePublicDetails()}.
     *
     * @var list<array{address: string, phone: string, facebook_url: string, latitude: float, longitude: float, amenities: list<string>}>
     */
    private const CITY_CLUSTER_PUBLIC_BASE = [
        [
            'address' => '7th Ave cor 26th St, Bonifacio Global City, Taguig, 1634 Metro Manila',
            'phone' => '+63 917 555 0101',
            'facebook_url' => 'https://www.facebook.com/QuirkyCourtsTaguig',
            'latitude' => 14.5547,
            'longitude' => 121.0484,
            'amenities' => ['Indoor & outdoor courts', 'Parking', 'Locker rooms', 'Pro shop', 'Drinking water', 'Restrooms'],
        ],
        [
            'address' => 'West Ave, Diliman, Quezon City, 1104 Metro Manila',
            'phone' => '+63 917 555 0102',
            'facebook_url' => 'https://www.facebook.com/QuirkyCourtsQuezonCity',
            'latitude' => 14.6760,
            'longitude' => 121.0437,
            'amenities' => ['Air-conditioned indoor', 'Ball machine rental', 'Parking', 'Shower rooms', 'Café'],
        ],
        [
            'address' => 'Salinas Dr, Lahug, Cebu City, 6000 Cebu',
            'phone' => '+63 917 555 0103',
            'facebook_url' => 'https://www.facebook.com/QuirkyCourtsCebu',
            'latitude' => 10.3157,
            'longitude' => 123.8854,
            'amenities' => ['Outdoor courts', 'Covered viewing', 'Parking', 'Restrooms', 'Equipment rental'],
        ],
        [
            'address' => 'J.P. Laurel Ave, Bajada, Davao City, 8000 Davao del Sur',
            'phone' => '+63 917 555 0104',
            'facebook_url' => 'https://www.facebook.com/QuirkyCourtsDavao',
            'latitude' => 7.1907,
            'longitude' => 125.4553,
            'amenities' => ['Indoor courts', 'Parking', 'Locker rooms', 'First aid', 'Wi-Fi'],
        ],
        [
            'address' => 'Ayala Ave, Makati, 1226 Metro Manila',
            'phone' => '+63 917 555 0105',
            'facebook_url' => 'https://www.facebook.com/QuirkyCourtsMakati',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
            'amenities' => ['Rooftop outdoor', 'Valet parking', 'Pro shop', 'Lounge', 'Restrooms'],
        ],
    ];

    /**
     * @return array{address: string, phone: string, facebook_url: string, latitude: float, longitude: float, amenities: list<string>}
     */
    private static function venuePublicDetails(int $clusterIndex, int $slotInCity): array
    {
        $base = self::CITY_CLUSTER_PUBLIC_BASE[$clusterIndex];
        $slotInCity = max(1, min(self::VENUES_PER_CITY, $slotInCity));

        return [
            'address' => $base['address'].' — Site '.$slotInCity,
            'phone' => $base['phone'],
            'facebook_url' => $base['facebook_url'],
            'latitude' => round($base['latitude'] + ($slotInCity - 1) * 0.0018 - 0.0099, 6),
            'longitude' => round($base['longitude'] + (($slotInCity - 1) % 6) * 0.0017 - 0.00425, 6),
            'amenities' => $base['amenities'],
        ];
    }

    /**
     * One court client per court admin (1:1). Run after DemoUsersSeeder.
     *
     * {@see self::VENUES_PER_CITY} venues share each cluster city name (Taguig, Quezon City, Cebu City, Davao City, Makati).
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

        if (count(self::CLUSTER_CITY_NAMES) * self::VENUES_PER_CITY !== self::VENUE_COUNT) {
            throw new \LogicException('VENUE_COUNT must equal cluster cities × venues per city.');
        }

        if (count(self::QUIRKY_VENUE_NAMES) !== self::VENUES_PER_CITY) {
            throw new \LogicException('QUIRKY_VENUE_NAMES must have exactly VENUES_PER_CITY entries.');
        }

        $globalIndex = 0;
        foreach (self::CLUSTER_CITY_NAMES as $clusterIndex => $city) {
            for ($slot = 1; $slot <= self::VENUES_PER_CITY; $slot++) {
                $globalIndex++;
                $i = $globalIndex;
                $slug = 'seed-venue-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $name = self::QUIRKY_VENUE_NAMES[$slot - 1].' — '.$city;
                $public = self::venuePublicDetails($clusterIndex, $slot);

                // Alternate 3 and 4 outdoor courts per venue.
                $outdoorCount = $i % 2 === 1 ? 3 : 4;

                $hourlyBase = 25000 + (($globalIndex - 1) * 750);
                $peakBase = $hourlyBase + 12000;

                $client = CourtClient::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'city' => $city,
                        'address' => $public['address'],
                        'phone' => $public['phone'],
                        'facebook_url' => $public['facebook_url'],
                        'latitude' => $public['latitude'],
                        'longitude' => $public['longitude'],
                        'amenities' => $public['amenities'],
                        'admin_user_id' => $admins[$globalIndex - 1]->id,
                        'subscription_tier' => CourtClient::TIER_PREMIUM,
                        'is_active' => true,
                        'hourly_rate_cents' => $hourlyBase,
                        'peak_hourly_rate_cents' => $peakBase,
                        'currency' => 'PHP',
                        'public_rating_average' => null,
                        'public_rating_count' => 0,
                    ]
                );

                CourtClientBootstrap::seedVenueCourtsIfEmpty($client, $outdoorCount, 5);
                $client->load('courts');
                $this->seedVenueApprovedGallery($client, $globalIndex - 1);
                $this->seedCourtsApprovedGallery($client, $globalIndex - 1);
            }
        }

        $this->seedDemoCoachScenario();
        $this->seedDeskUsers();
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

    /**
     * Approved gallery rows under the public disk so venue carousels ({@see CourtClient::carouselSlides()}) show
     * multiple slides. Uses bundled SVGs from public/images (no network).
     */
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

    /** Two slides per court for public court page carousels ({@see Court::carouselSlides()}). */
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
