<?php

namespace App\Services;

use App\Models\Court;
use App\Models\CourtClient;
use App\Models\VenueWeeklyHour;

final class CourtClientBootstrap
{
    /**
     * Seed weekly hours (7 rows) and default courts when none exist: one outdoor + one indoor.
     * Used when creating a new venue from the admin UI.
     */
    public static function ensureDefaultCourtsAndSchedule(CourtClient $client): void
    {
        self::ensureWeeklyHours($client);
        if ($client->courts()->count() === 0) {
            self::createCourts($client, 1, 1);
        }
    }

    /**
     * Ensure seven weekly hour rows, then create courts only if the venue has none.
     * Used by database seeders for richer demo data (e.g. several outdoor + several indoor courts).
     */
    public static function seedVenueCourtsIfEmpty(CourtClient $client, int $outdoorCount, int $indoorCount): void
    {
        self::ensureWeeklyHours($client);
        if ($client->courts()->count() === 0) {
            self::createCourts($client, $outdoorCount, $indoorCount);
        }
    }

    public static function ensureWeeklyHours(CourtClient $client): void
    {
        if ($client->weeklyHours()->count() > 0) {
            return;
        }

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

    /**
     * All outdoor courts first (sort order), then all indoor — matches venue grid ordering elsewhere.
     */
    private static function createCourts(CourtClient $client, int $outdoorCount, int $indoorCount): void
    {
        $sort = 0;
        for ($o = 1; $o <= $outdoorCount; $o++) {
            Court::query()->create([
                'court_client_id' => $client->id,
                'name' => Court::defaultName(Court::ENV_OUTDOOR, $o),
                'sort_order' => $sort++,
                'environment' => Court::ENV_OUTDOOR,
                'hourly_rate_cents' => null,
                'peak_hourly_rate_cents' => null,
                'is_available' => true,
            ]);
        }
        for ($i = 1; $i <= $indoorCount; $i++) {
            Court::query()->create([
                'court_client_id' => $client->id,
                'name' => Court::defaultName(Court::ENV_INDOOR, $i),
                'sort_order' => $sort++,
                'environment' => Court::ENV_INDOOR,
                'hourly_rate_cents' => null,
                'peak_hourly_rate_cents' => null,
                'is_available' => true,
            ]);
        }
    }
}
