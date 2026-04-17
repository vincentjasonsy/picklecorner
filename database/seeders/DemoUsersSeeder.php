<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;

/**
 * Minimal demo users for local/staging seed (password for each: "password").
 * {@see CourtClientSeeder} adds the desk account linked to the single seeded venue.
 *
 * Uses updateOrCreate on email so db:seed can be re-run without duplicate errors.
 */
class DemoUsersSeeder extends Seeder
{
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

        User::query()->updateOrCreate(
            ['email' => 'courtadmin@picklecorner.ph'],
            [
                'name' => 'Court Admin',
                'password' => 'password',
                'user_type_id' => UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id'),
                ...$verified,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'coach@picklecorner.ph'],
            [
                'name' => 'Coach',
                'password' => 'password',
                'user_type_id' => UserType::query()->where('slug', UserType::SLUG_COACH)->value('id'),
                ...$verified,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'player@picklecorner.ph'],
            [
                'name' => 'Player',
                'password' => 'password',
                'user_type_id' => UserType::query()->where('slug', UserType::SLUG_USER)->value('id'),
                ...$verified,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'openplayhost@picklecorner.ph'],
            [
                'name' => 'Open play host',
                'password' => 'password',
                'user_type_id' => UserType::query()->where('slug', UserType::SLUG_OPEN_PLAY_HOST)->value('id'),
                ...$verified,
            ],
        );
    }
}
