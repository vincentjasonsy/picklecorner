<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\VenueWeeklyHour;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberBookAgainControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_book_again_redirects_to_venue_with_book_date_and_slots_when_available(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00', $tz));

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create(['is_active' => true]);
        for ($d = 0; $d < 7; $d++) {
            VenueWeeklyHour::query()->create([
                'court_client_id' => $client->id,
                'day_of_week' => $d,
                'is_closed' => false,
                'opens_at' => '07:00',
                'closes_at' => '23:00',
            ]);
        }
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $lastSaturday = Carbon::parse('2026-04-11 18:00:00', $tz);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $lastSaturday,
            'ends_at' => $lastSaturday->copy()->addHours(2),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        $targetDate = '2026-04-18';
        $slots = $court->id.'-18,'.$court->id.'-19';

        $response = $this->actingAs($player)->get(route('account.book.again'));

        $response->assertRedirect();
        $url = $response->headers->get('Location');
        $this->assertStringContainsString('/account/book/venues/'.$client->slug, $url);
        $this->assertStringContainsString('book_date='.$targetDate, $url);
        $this->assertStringContainsString('book_slots='.rawurlencode($slots), $url);
    }

    public function test_book_again_redirects_to_dashboard_when_no_repeatable_booking(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $response = $this->actingAs($player)->get(route('account.book.again'));

        $response->assertRedirect(route('account.dashboard'));
        $response->assertSessionHas('status');
    }
}
