<?php

namespace Tests\Feature;

use App\Livewire\Member\MemberBookingNudge;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Support\MemberBookingNudge as MemberBookingNudgeSupport;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class MemberBookingNudgeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_nudge_shows_when_dormant_and_no_upcoming_booking(): void
    {
        $this->seed(UserTypeSeeder::class);
        Cache::flush();

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00', $tz));

        $player = User::factory()->player()->create([
            'created_at' => Carbon::parse('2026-01-01 10:00:00', $tz),
        ]);
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $past = Carbon::parse('2026-07-15 10:00:00', $tz);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $past,
            'ends_at' => $past->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(MemberBookingNudge::class)
            ->assertSet('open', true)
            ->assertSee('We miss you', escape: false);
    }

    public function test_nudge_hidden_when_upcoming_booking_exists(): void
    {
        $this->seed(UserTypeSeeder::class);
        Cache::flush();

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00', $tz));

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $past = Carbon::parse('2026-07-01 10:00:00', $tz);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $past,
            'ends_at' => $past->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        $future = Carbon::parse('2026-08-05 10:00:00', $tz);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $future,
            'ends_at' => $future->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(MemberBookingNudge::class)
            ->assertSet('open', false);
    }

    public function test_dismiss_sets_cache_so_nudge_stays_hidden(): void
    {
        $this->seed(UserTypeSeeder::class);
        Cache::flush();

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00', $tz));

        $player = User::factory()->player()->create([
            'created_at' => Carbon::parse('2026-01-01 10:00:00', $tz),
        ]);
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $past = Carbon::parse('2026-07-15 10:00:00', $tz);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $past,
            'ends_at' => $past->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(MemberBookingNudge::class)
            ->assertSet('open', true)
            ->call('dismiss')
            ->assertSet('open', false);

        $this->assertTrue(Cache::has(MemberBookingNudgeSupport::cacheKey($player)));

        Livewire::actingAs($player)
            ->test(MemberBookingNudge::class)
            ->assertSet('open', false);
    }
}
