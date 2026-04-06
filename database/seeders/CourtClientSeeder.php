<?php

namespace Database\Seeders;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Services\CourtClientBootstrap;
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

        $ratings = [
            ['avg' => 4.8, 'count' => 214],
            ['avg' => 4.6, 'count' => 156],
            ['avg' => 4.9, 'count' => 302],
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
                    'public_rating_average' => $ratings[$index]['avg'],
                    'public_rating_count' => $ratings[$index]['count'],
                ]
            );

            CourtClientBootstrap::ensureDefaultCourtsAndSchedule($client);
        }
    }
}
