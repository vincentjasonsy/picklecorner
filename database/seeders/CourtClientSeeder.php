<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Models\VenueWeeklyHour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourtClientSeeder extends Seeder
{
    /**
     * One court client per court admin (1:1). Run after DemoUsersSeeder.
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

        if ($admins->count() !== 3) {
            throw new \RuntimeException(
                'Expected exactly 3 court admin users before seeding court clients; found '.$admins->count()
            );
        }

        $venues = [
            ['name' => 'Manila Bay Pickle Club', 'city' => 'Taguig'],
            ['name' => 'Quezon City Court Hub', 'city' => 'Quezon City'],
            ['name' => 'Cebu Pickle Center', 'city' => 'Cebu City'],
        ];

        // Stored as centavos: 35000 => ₱350/hr, 50000 => ₱500/hr peak, etc.
        $pricing = [
            ['hourly' => 35000, 'peak' => 50000],
            ['hourly' => 30000, 'peak' => 45000],
            ['hourly' => 28000, 'peak' => 40000],
        ];

        foreach ($venues as $index => $venue) {
            $client = CourtClient::query()->updateOrCreate(
                ['slug' => Str::slug($venue['name'])],
                [
                    'name' => $venue['name'],
                    'city' => $venue['city'],
                    'admin_user_id' => $admins[$index]->id,
                    'is_active' => true,
                    'hourly_rate_cents' => $pricing[$index]['hourly'],
                    'peak_hourly_rate_cents' => $pricing[$index]['peak'],
                    'currency' => 'PHP',
                ]
            );

            $this->seedCourtsAndSchedule($client);
        }
    }

    private function seedCourtsAndSchedule(CourtClient $client): void
    {
        if ($client->courts()->count() === 0) {
            $definitions = [
                ['environment' => Court::ENV_OUTDOOR],
                ['environment' => Court::ENV_INDOOR],
            ];
            foreach ($definitions as $i => $def) {
                Court::query()->create([
                    'court_client_id' => $client->id,
                    'name' => Court::defaultName($def['environment'], 1),
                    'sort_order' => $i,
                    'environment' => $def['environment'],
                    'hourly_rate_cents' => null,
                    'peak_hourly_rate_cents' => null,
                    'is_available' => true,
                ]);
            }
        }

        if ($client->weeklyHours()->count() === 0) {
            for ($d = 0; $d < 7; $d++) {
                VenueWeeklyHour::query()->create([
                    'court_client_id' => $client->id,
                    'day_of_week' => $d,
                    'is_closed' => false,
                    'opens_at' => '07:00',
                    'closes_at' => '22:00',
                ]);
            }
        }
    }
}
