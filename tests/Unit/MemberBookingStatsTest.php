<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Support\MemberBookingStats;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberBookingStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_returns_zeros_without_bookings(): void
    {
        $this->seed(UserTypeSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00', 'UTC'));

        $player = User::factory()->player()->create();

        $stats = MemberBookingStats::forUser($player);

        $this->assertSame(0.0, $stats['total_hours']);
        $this->assertSame(0, $stats['bookings_this_month']);
        $this->assertSame(0.0, $stats['hours_this_week']);
        $this->assertNull($stats['favorite_venue']);
        $this->assertNull($stats['favorite_day_time']);
        $this->assertSame(0, $stats['streak_weeks']);
        $this->assertFalse($stats['this_week_has_booking']);
    }

    public function test_weekly_streak_and_hours_when_booking_this_week(): void
    {
        $this->seed(UserTypeSeeder::class);
        $tz = 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-04-17 14:00:00', $tz));

        $venue = CourtClient::factory()->create(['name' => 'Gold Club']);
        $court = Court::query()->create([
            'court_client_id' => $venue->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $player = User::factory()->player()->create();

        Booking::query()->create([
            'court_client_id' => $venue->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => Carbon::parse('2026-04-16 10:00:00', $tz),
            'ends_at' => Carbon::parse('2026-04-16 12:00:00', $tz),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        $stats = MemberBookingStats::forUser($player);

        $this->assertSame(2.0, $stats['hours_this_week']);
        $this->assertSame(2.0, $stats['total_hours']);
        $this->assertSame(1, $stats['bookings_this_month']);
        $this->assertTrue($stats['this_week_has_booking']);
        $this->assertSame(1, $stats['streak_weeks']);
        $this->assertSame('Gold Club', $stats['favorite_venue']);
        $this->assertStringContainsString('Thursday', (string) $stats['favorite_day_time']);
    }
}
