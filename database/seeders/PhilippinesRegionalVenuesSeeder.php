<?php

namespace Database\Seeders;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\CourtClientBootstrap;
use Illuminate\Database\Seeder;

/**
 * Twenty active venues across Luzon, Visayas, and Mindanao for browse / booking demos.
 *
 * - Each venue: 4 indoor courts.
 * - Two venues (Baguio, Malay) also have 4 outdoor courts.
 * - Per venue: one court admin + one desk user (password: "password").
 *
 * Skips if venues with slug prefix {@see self::SLUG_PREFIX} already exist (re-runnable).
 */
class PhilippinesRegionalVenuesSeeder extends Seeder
{
    public const SLUG_PREFIX = 'ph-regional-';

    /** @var list<string> */
    private const VENUE_PREFIXES = [
        'Summit', 'Harbor', 'Metro', 'Coast', 'North', 'South', 'East', 'West',
        'Central', 'Bay', 'Peak', 'Island', 'Sunrise', 'Plaza', 'Arena',
        'Courtside', 'Baseline', 'Kitchen', 'Rally', 'Dink',
    ];

    /**
     * Cities by island group. Exactly two entries have outdoor courts (see outdoor => true).
     *
     * @var list<array{city: string, region: string, outdoor: bool}>
     */
    private static function venueRows(): array
    {
        return [
            // Luzon (7)
            ['city' => 'Quezon City', 'region' => 'Luzon', 'outdoor' => false],
            ['city' => 'Makati', 'region' => 'Luzon', 'outdoor' => false],
            ['city' => 'Taguig', 'region' => 'Luzon', 'outdoor' => false],
            ['city' => 'Manila', 'region' => 'Luzon', 'outdoor' => false],
            ['city' => 'Pasig', 'region' => 'Luzon', 'outdoor' => false],
            ['city' => 'Baguio', 'region' => 'Luzon', 'outdoor' => true],
            ['city' => 'Angeles', 'region' => 'Luzon', 'outdoor' => false],
            // Visayas (7)
            ['city' => 'Cebu City', 'region' => 'Visayas', 'outdoor' => false],
            ['city' => 'Iloilo City', 'region' => 'Visayas', 'outdoor' => false],
            ['city' => 'Bacolod', 'region' => 'Visayas', 'outdoor' => false],
            ['city' => 'Dumaguete', 'region' => 'Visayas', 'outdoor' => false],
            ['city' => 'Tacloban', 'region' => 'Visayas', 'outdoor' => false],
            ['city' => 'Lapu-Lapu', 'region' => 'Visayas', 'outdoor' => false],
            ['city' => 'Malay', 'region' => 'Visayas', 'outdoor' => true],
            // Mindanao (6)
            ['city' => 'Davao City', 'region' => 'Mindanao', 'outdoor' => false],
            ['city' => 'Cagayan de Oro', 'region' => 'Mindanao', 'outdoor' => false],
            ['city' => 'Zamboanga City', 'region' => 'Mindanao', 'outdoor' => false],
            ['city' => 'General Santos', 'region' => 'Mindanao', 'outdoor' => false],
            ['city' => 'Iligan', 'region' => 'Mindanao', 'outdoor' => false],
            ['city' => 'Butuan', 'region' => 'Mindanao', 'outdoor' => false],
        ];
    }

    public function run(): void
    {
        if (CourtClient::query()->where('slug', 'like', self::SLUG_PREFIX.'%')->exists()) {
            $this->command?->warn('Philippines regional venues already present (slug '.self::SLUG_PREFIX.'*); skip.');

            return;
        }

        $deskTypeId = UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id');
        if ($deskTypeId === null) {
            $this->command?->error('Court Client Desk user type missing. Run UserTypeSeeder.');

            return;
        }

        $rows = self::venueRows();
        foreach ($rows as $index => $meta) {
            $n = $index + 1;
            $slug = self::SLUG_PREFIX.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $prefix = self::VENUE_PREFIXES[$index] ?? 'Club';

            $admin = User::factory()->courtAdmin()->create([
                'name' => 'Court admin · '.$meta['city'],
                'email' => sprintf('ph-%02d-admin@seed.picklecorner.ph', $n),
                'password' => 'password',
                'email_verified_at' => now(),
            ]);

            $client = CourtClient::factory()->forAdmin($admin)->create([
                'name' => $prefix.' Pickle — '.$meta['city'],
                'slug' => $slug,
                'city' => $meta['city'],
                'is_active' => true,
                'currency' => 'PHP',
            ]);

            $outdoorCount = $meta['outdoor'] ? 4 : 0;
            CourtClientBootstrap::seedVenueCourtsIfEmpty($client, $outdoorCount, 4);

            User::query()->create([
                'name' => 'Front desk · '.$meta['city'],
                'email' => sprintf('ph-%02d-desk@seed.picklecorner.ph', $n),
                'password' => 'password',
                'user_type_id' => $deskTypeId,
                'desk_court_client_id' => $client->id,
                'email_verified_at' => now(),
            ]);
        }

        $outdoorVenues = collect($rows)->filter(fn (array $r): bool => $r['outdoor'])->pluck('city')->implode(', ');
        $this->command?->info(
            'Seeded '.count($rows).' PH venues (4 indoor each; +4 outdoor only in: '.$outdoorVenues.'. Per venue: ph-NN-admin@seed.picklecorner.ph & ph-NN-desk@seed.picklecorner.ph (NN 01–'.sprintf('%02d', count($rows)).'). Password: password.',
        );
    }
}
