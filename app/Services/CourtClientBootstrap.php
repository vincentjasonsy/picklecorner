<?php

namespace App\Services;

use App\Models\Court;
use App\Models\CourtClient;
use App\Models\VenueWeeklyHour;

final class CourtClientBootstrap
{
    /**
     * Seed two default courts (outdoor + indoor) and seven weekly hour rows when missing.
     */
    public static function ensureDefaultCourtsAndSchedule(CourtClient $client): void
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
