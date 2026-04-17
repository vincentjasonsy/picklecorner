<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserTypeSeeder::class,
            DemoUsersSeeder::class,
            CourtClientSeeder::class,
            VenueReviewSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
