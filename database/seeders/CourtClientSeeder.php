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
    private const VENUE_COUNT = 20;

    /**
     * Public listing fields + map pins for each seeded city (same order as {@see $cities} in run()).
     *
     * @var list<array{address: string, phone: string, facebook_url: string, latitude: float, longitude: float, amenities: list<string>}>
     */
    private const VENUE_PUBLIC_DETAILS = [
        [
            'address' => '7th Ave cor 26th St, Bonifacio Global City, Taguig, 1634 Metro Manila',
            'phone' => '+63 917 555 0101',
            'facebook_url' => 'https://www.facebook.com/PickleHubTaguig',
            'latitude' => 14.5547,
            'longitude' => 121.0484,
            'amenities' => ['Indoor & outdoor courts', 'Parking', 'Locker rooms', 'Pro shop', 'Drinking water', 'Restrooms'],
        ],
        [
            'address' => 'West Ave, Diliman, Quezon City, 1104 Metro Manila',
            'phone' => '+63 917 555 0102',
            'facebook_url' => 'https://www.facebook.com/PickleHubQuezonCity',
            'latitude' => 14.6760,
            'longitude' => 121.0437,
            'amenities' => ['Air-conditioned indoor', 'Ball machine rental', 'Parking', 'Shower rooms', 'Café'],
        ],
        [
            'address' => 'Salinas Dr, Lahug, Cebu City, 6000 Cebu',
            'phone' => '+63 917 555 0103',
            'facebook_url' => 'https://www.facebook.com/PickleHubCebu',
            'latitude' => 10.3157,
            'longitude' => 123.8854,
            'amenities' => ['Outdoor courts', 'Covered viewing', 'Parking', 'Restrooms', 'Equipment rental'],
        ],
        [
            'address' => 'J.P. Laurel Ave, Bajada, Davao City, 8000 Davao del Sur',
            'phone' => '+63 917 555 0104',
            'facebook_url' => 'https://www.facebook.com/PickleHubDavao',
            'latitude' => 7.1907,
            'longitude' => 125.4553,
            'amenities' => ['Indoor courts', 'Parking', 'Locker rooms', 'First aid', 'Wi-Fi'],
        ],
        [
            'address' => 'Ayala Ave, Makati, 1226 Metro Manila',
            'phone' => '+63 917 555 0105',
            'facebook_url' => 'https://www.facebook.com/PickleHubMakati',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
            'amenities' => ['Rooftop outdoor', 'Valet parking', 'Pro shop', 'Lounge', 'Restrooms'],
        ],
        [
            'address' => 'Ortigas Center, Pasig, 1605 Metro Manila',
            'phone' => '+63 917 555 0106',
            'facebook_url' => 'https://www.facebook.com/PickleHubPasig',
            'latitude' => 14.5864,
            'longitude' => 121.0613,
            'amenities' => ['Indoor courts', 'Parking building', 'Locker rooms', 'Coaching desk', 'Drinking water'],
        ],
        [
            'address' => 'EDSA cor Shaw Blvd, Mandaluyong, 1552 Metro Manila',
            'phone' => '+63 917 555 0107',
            'facebook_url' => 'https://www.facebook.com/PickleHubMandaluyong',
            'latitude' => 14.5794,
            'longitude' => 121.0359,
            'amenities' => ['Mixed indoor/outdoor', 'Mall parking', 'Restrooms', 'Ball hopper', 'Wi-Fi'],
        ],
        [
            'address' => 'Sucat Rd, Parañaque, 1700 Metro Manila',
            'phone' => '+63 917 555 0108',
            'facebook_url' => 'https://www.facebook.com/PickleHubParanaque',
            'latitude' => 14.4793,
            'longitude' => 121.0198,
            'amenities' => ['Outdoor courts', 'Open parking', 'Covered benches', 'Equipment sales', 'Restrooms'],
        ],
        [
            'address' => 'Alabang–Zapote Rd, Las Piñas, 1747 Metro Manila',
            'phone' => '+63 917 555 0109',
            'facebook_url' => 'https://www.facebook.com/PickleHubLasPinas',
            'latitude' => 14.4492,
            'longitude' => 120.9828,
            'amenities' => ['Indoor courts', 'Parking', 'Family lounge', 'Locker rooms', 'Vending'],
        ],
        [
            'address' => 'Marikina–Infanta Hwy, Marikina, 1800 Metro Manila',
            'phone' => '+63 917 555 0110',
            'facebook_url' => 'https://www.facebook.com/PickleHubMarikina',
            'latitude' => 14.6507,
            'longitude' => 121.1029,
            'amenities' => ['Outdoor courts', 'Street parking', 'Restrooms', 'Night lighting', 'First aid'],
        ],
        [
            'address' => 'Sumulong Hwy, Antipolo, 1870 Rizal',
            'phone' => '+63 917 555 0111',
            'facebook_url' => 'https://www.facebook.com/PickleHubAntipolo',
            'latitude' => 14.6255,
            'longitude' => 121.1245,
            'amenities' => ['Mountain-view outdoor', 'Parking', 'Covered rest area', 'Pro shop', 'Drinking water'],
        ],
        [
            'address' => 'Diversion Rd, Mandurriao, Iloilo City, 5000 Iloilo',
            'phone' => '+63 917 555 0112',
            'facebook_url' => 'https://www.facebook.com/PickleHubIloilo',
            'latitude' => 10.7202,
            'longitude' => 122.5621,
            'amenities' => ['Indoor courts', 'Parking', 'Locker rooms', 'Coaching', 'Restrooms'],
        ],
        [
            'address' => 'Lacson St, Bacolod, 6100 Negros Occidental',
            'phone' => '+63 917 555 0113',
            'facebook_url' => 'https://www.facebook.com/PickleHubBacolod',
            'latitude' => 10.6407,
            'longitude' => 122.9689,
            'amenities' => ['Outdoor courts', 'Parking', 'Bleachers', 'Snack bar', 'Wi-Fi'],
        ],
        [
            'address' => 'CM Recto Ave, Cagayan de Oro, 9000 Misamis Oriental',
            'phone' => '+63 917 555 0114',
            'facebook_url' => 'https://www.facebook.com/PickleHubCDO',
            'latitude' => 8.4542,
            'longitude' => 124.6319,
            'amenities' => ['Indoor courts', 'Mall parking', 'Locker rooms', 'Pro shop', 'Restrooms'],
        ],
        [
            'address' => 'Session Rd, Baguio, 2600 Benguet',
            'phone' => '+63 917 555 0115',
            'facebook_url' => 'https://www.facebook.com/PickleHubBaguio',
            'latitude' => 16.4023,
            'longitude' => 120.5960,
            'amenities' => ['Cool-climate outdoor', 'Parking', 'Heated lounge', 'Equipment rental', 'Restrooms'],
        ],
        [
            'address' => 'Sta. Rosa–Tagaytay Rd, Santa Rosa, 4026 Laguna',
            'phone' => '+63 917 555 0116',
            'facebook_url' => 'https://www.facebook.com/PickleHubSantaRosa',
            'latitude' => 14.3122,
            'longitude' => 121.1114,
            'amenities' => ['Indoor & outdoor', 'Parking', 'Kids area', 'Café', 'Locker rooms'],
        ],
        [
            'address' => 'P. Burgos, Batangas City, 4200 Batangas',
            'phone' => '+63 917 555 0117',
            'facebook_url' => 'https://www.facebook.com/PickleHubBatangas',
            'latitude' => 13.7565,
            'longitude' => 121.0583,
            'amenities' => ['Harbor-side outdoor', 'Parking', 'Covered seating', 'Restrooms', 'Drinking water'],
        ],
        [
            'address' => 'San Pedro St, General Santos, 9500 South Cotabato',
            'phone' => '+63 917 555 0118',
            'facebook_url' => 'https://www.facebook.com/PickleHubGenSan',
            'latitude' => 6.1164,
            'longitude' => 125.1716,
            'amenities' => ['Indoor courts', 'Parking', 'Locker rooms', 'Front desk', 'Wi-Fi'],
        ],
        [
            'address' => 'Gov. Camins Ave, Zamboanga City, 7000 Zamboanga del Sur',
            'phone' => '+63 917 555 0119',
            'facebook_url' => 'https://www.facebook.com/PickleHubZamboanga',
            'latitude' => 6.9214,
            'longitude' => 122.0790,
            'amenities' => ['Outdoor courts', 'Open parking', 'Security', 'Restrooms', 'First aid'],
        ],
        [
            'address' => 'J.C. Aquino Ave, Butuan, 8600 Agusan del Norte',
            'phone' => '+63 917 555 0120',
            'facebook_url' => 'https://www.facebook.com/PickleHubButuan',
            'latitude' => 8.9475,
            'longitude' => 125.5436,
            'amenities' => ['Indoor courts', 'Parking', 'Locker rooms', 'Pro shop', 'Drinking water'],
        ],
    ];

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
            $public = self::VENUE_PUBLIC_DETAILS[$index];

            // Alternate 3 and 4 outdoor courts per venue.
            $outdoorCount = $i % 2 === 1 ? 3 : 4;

            $hourlyBase = 25000 + ($index * 750);
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
                    'admin_user_id' => $admins[$index]->id,
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
            $this->seedVenueApprovedGallery($client, $index);
            $this->seedCourtsApprovedGallery($client, $index);
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
