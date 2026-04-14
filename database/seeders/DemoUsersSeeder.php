<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    /**
     * Demo accounts: 1 super admin, {@see CourtClientSeeder::VENUE_COUNT} court admins, 5 coaches, 10 players.
     * CourtClientSeeder adds one front-desk user per venue.
     * Password for every account: "password" (hashed via User model's "hashed" cast).
     *
     * Uses updateOrCreate on email so db:seed can be re-run without duplicate errors.
     */
    public function run(): void
    {
        $verified = ['email_verified_at' => now()];

        User::query()->updateOrCreate(
            ['email' => 'superadmin@picklecorner.ph'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'user_type_id' => UserType::query()->where('slug', UserType::SLUG_SUPER_ADMIN)->value('id'),
                ...$verified,
            ],
        );

        for ($i = 1; $i <= CourtClientSeeder::VENUE_COUNT; $i++) {
            User::query()->updateOrCreate(
                ['email' => "courtadmin{$i}@picklecorner.ph"],
                [
                    'name' => "Court Admin {$i}",
                    'password' => 'password',
                    'user_type_id' => UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id'),
                    ...$verified,
                ],
            );
        }

        for ($i = 1; $i <= 5; $i++) {
            User::query()->updateOrCreate(
                ['email' => "coach{$i}@picklecorner.ph"],
                [
                    'name' => "Coach {$i}",
                    'password' => 'password',
                    'user_type_id' => UserType::query()->where('slug', UserType::SLUG_COACH)->value('id'),
                    ...$verified,
                ],
            );
        }

        for ($i = 1; $i <= 10; $i++) {
            User::query()->updateOrCreate(
                ['email' => "player{$i}@picklecorner.ph"],
                [
                    'name' => "Player {$i}",
                    'password' => 'password',
                    'user_type_id' => UserType::query()->where('slug', UserType::SLUG_USER)->value('id'),
                    ...$verified,
                ],
            );
        }
    }
}
