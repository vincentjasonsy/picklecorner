<?php

namespace Tests\Feature;

use App\Livewire\Desk\DeskCourtsLive;
use App\Livewire\Desk\DeskHome;
use App\Livewire\Desk\DeskManualBooking;
use App\Livewire\Desk\DeskMyRequests;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeskPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_desk_user_can_open_overview_and_my_requests(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $desk = User::factory()->courtClientDesk($client)->create();

        $this->actingAs($desk)
            ->get(route('desk.home'))
            ->assertOk();

        $this->actingAs($desk)
            ->get(route('desk.my-requests'))
            ->assertOk();

        Livewire::actingAs($desk)
            ->test(DeskHome::class)
            ->assertSee($client->name);

        Livewire::actingAs($desk)
            ->test(DeskMyRequests::class)
            ->assertSee('Nothing in the log yet');

        $this->actingAs($desk)
            ->get(route('desk.courts-live'))
            ->assertOk();

        Livewire::actingAs($desk)
            ->test(DeskCourtsLive::class)
            ->assertSee('Add courts on the venue side');
    }

    public function test_desk_my_requests_only_shows_own_submissions(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $desk = User::factory()->courtClientDesk($client)->create();
        $otherDesk = User::factory()->courtClientDesk($client)->create();
        $player = User::factory()->player()->create();

        $mine = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'desk_submitted_by' => $desk->id,
            'starts_at' => Carbon::now()->addDays(5),
            'ends_at' => Carbon::now()->addDays(5)->addHour(),
            'status' => Booking::STATUS_PENDING_APPROVAL,
            'currency' => 'PHP',
        ]);

        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'desk_submitted_by' => $otherDesk->id,
            'starts_at' => Carbon::now()->addDays(6),
            'ends_at' => Carbon::now()->addDays(6)->addHour(),
            'status' => Booking::STATUS_PENDING_APPROVAL,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($desk)
            ->test(DeskMyRequests::class)
            ->assertSee($player->name)
            ->assertSee('Pending')
            ->assertSee($court->name);
    }

    public function test_player_cannot_access_desk_routes(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('desk.home'))
            ->assertForbidden();
    }

    public function test_desk_courts_live_shows_current_and_next_guest_per_court(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court Alpha',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $desk = User::factory()->courtClientDesk($client)->create();
        $onCourtPlayer = User::factory()->player()->create(['name' => 'Alex Active']);
        $nextPlayer = User::factory()->player()->create(['name' => 'Blake Next']);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-06-10 10:30:00', $tz));

        try {
            Booking::query()->create([
                'court_client_id' => $client->id,
                'court_id' => $court->id,
                'user_id' => $onCourtPlayer->id,
                'starts_at' => Carbon::parse('2026-06-10 10:00:00', $tz),
                'ends_at' => Carbon::parse('2026-06-10 11:00:00', $tz),
                'status' => Booking::STATUS_CONFIRMED,
                'currency' => 'PHP',
            ]);

            Booking::query()->create([
                'court_client_id' => $client->id,
                'court_id' => $court->id,
                'user_id' => $nextPlayer->id,
                'starts_at' => Carbon::parse('2026-06-10 11:00:00', $tz),
                'ends_at' => Carbon::parse('2026-06-10 12:00:00', $tz),
                'status' => Booking::STATUS_CONFIRMED,
                'currency' => 'PHP',
            ]);

            Livewire::actingAs($desk)
                ->test(DeskCourtsLive::class)
                ->assertSee('Court Alpha')
                ->assertSee('Alex Active')
                ->assertSee('Blake Next')
                ->assertSee('On court now')
                ->assertSee('Up next');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_desk_can_open_booking_details_from_booked_grid_cell(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $desk = User::factory()->courtClientDesk($client)->create();
        $player = User::factory()->player()->create();

        $tz = config('app.timezone', 'UTC');
        $bookingDate = '2026-08-20';
        $starts = Carbon::parse($bookingDate.' 10:00:00', $tz);
        $ends = $starts->copy()->addHour();

        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
            'payment_method' => Booking::PAYMENT_GCASH,
            'payment_reference' => 'REF-999',
        ]);

        Livewire::actingAs($desk)
            ->test(DeskManualBooking::class)
            ->set('bookingCalendarDate', $bookingDate)
            ->call('openDeskBookedSlot', $court->id, 10)
            ->assertSet('deskViewBookingId', $booking->id)
            ->assertSee('Booking details')
            ->assertSee($player->email)
            ->assertSee('GCash')
            ->assertSee('REF-999')
            ->call('closeDeskViewBooking')
            ->assertSet('deskViewBookingId', null);
    }
}
