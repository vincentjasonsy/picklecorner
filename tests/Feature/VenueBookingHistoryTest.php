<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueBookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_cannot_open_venue_booking_history(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('venue.bookings.history'))->assertForbidden();
    }

    public function test_court_admin_can_open_history_and_show_for_own_venue_only(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();
        $guest = User::factory()->player()->create();

        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'));
        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1000,
            'currency' => 'PHP',
        ]);

        $this->actingAs($admin)->get(route('venue.bookings.history'))->assertOk();

        $this->actingAs($admin)
            ->get(route('venue.bookings.show', $booking))
            ->assertOk()
            ->assertSee($guest->email, escape: false);

        $otherClient = CourtClient::factory()->create();
        $otherCourt = Court::query()->create([
            'court_client_id' => $otherClient->id,
            'name' => 'Other',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $otherBooking = Booking::query()->create([
            'court_client_id' => $otherClient->id,
            'court_id' => $otherCourt->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $this->actingAs($admin)->get(route('venue.bookings.show', $otherBooking))->assertForbidden();
    }
}
