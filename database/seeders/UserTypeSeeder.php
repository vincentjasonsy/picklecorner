<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['slug' => UserType::SLUG_SUPER_ADMIN, 'name' => 'Super Admin', 'sort_order' => 1],
            ['slug' => UserType::SLUG_COURT_ADMIN, 'name' => 'Court Admin', 'sort_order' => 2],
            [
                'slug' => UserType::SLUG_COURT_CLIENT_DESK,
                'name' => 'Court Client Desk',
                'sort_order' => 3,
            ],
            ['slug' => UserType::SLUG_COACH, 'name' => 'Coach', 'sort_order' => 4],
            ['slug' => UserType::SLUG_USER, 'name' => 'User', 'sort_order' => 5],
            [
                'slug' => UserType::SLUG_OPEN_PLAY_HOST,
                'name' => 'Open play host',
                'sort_order' => 6,
            ],
        ];

        foreach ($types as $type) {
            UserType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                ]
            );
        }
    }
}
