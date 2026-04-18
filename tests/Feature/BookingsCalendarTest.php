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

class BookingsCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_venue_admin_sees_booking_on_calendar(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create();
        $guest = User::factory()->player()->create();

        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court Alpha',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'))->startOfMonth()->addDays(5)->setTime(10, 0);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1000,
            'currency' => 'PHP',
        ]);

        $ym = $starts->format('Y-m');
        $this->actingAs($admin)
            ->get(route('venue.bookings.calendar', ['ym' => $ym]))
            ->assertOk()
            ->assertSee('Court Alpha', escape: false);
    }

    public function test_desk_calendar_only_shows_own_submissions(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $desk = User::factory()->courtClientDesk($client)->create();
        $otherDesk = User::factory()->courtClientDesk($client)->create();
        $guest = User::factory()->player()->create();

        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Desk Court',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'))->startOfMonth()->addDays(8)->setTime(14, 0);

        $mine = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'desk_submitted_by' => $desk->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 500,
            'currency' => 'PHP',
        ]);

        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'desk_submitted_by' => $otherDesk->id,
            'starts_at' => $starts->copy()->addDay(),
            'ends_at' => $starts->copy()->addDay()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 500,
            'currency' => 'PHP',
        ]);

        $ym = $starts->format('Y-m');
        $html = $this->actingAs($desk)
            ->get(route('desk.bookings.calendar', ['ym' => $ym]))
            ->assertOk()
            ->assertSee('Desk Court', escape: false)
            ->getContent();

        $this->assertSame(1, preg_match_all('/wire:key="cal-b-[^"]+"/', $html));
    }

    public function test_coach_calendar_and_show_are_scoped(): void
    {
        $this->seed(UserTypeSeeder::class);

        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $guest = User::factory()->player()->create();

        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Coach Court',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'))->startOfMonth()->addDays(12)->setTime(9, 0);

        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'coach_user_id' => $coach->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 2000,
            'currency' => 'PHP',
        ]);

        $other = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'coach_user_id' => $otherCoach->id,
            'starts_at' => $starts->copy()->addDay(),
            'ends_at' => $starts->copy()->addDay()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 2000,
            'currency' => 'PHP',
        ]);

        $ym = $starts->format('Y-m');
        $this->actingAs($coach)
            ->get(route('account.coach.bookings.calendar', ['ym' => $ym]))
            ->assertOk()
            ->assertSee('Coach Court', escape: false);

        $this->actingAs($coach)
            ->get(route('account.coach.bookings.show', $booking))
            ->assertOk();

        $this->actingAs($coach)
            ->get(route('account.coach.bookings.show', $other))
            ->assertForbidden();
    }

    public function test_desk_booking_show_allows_same_venue_even_if_submitted_by_another_desk_user(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $desk = User::factory()->courtClientDesk($client)->create();
        $otherDesk = User::factory()->courtClientDesk($client)->create();
        $guest = User::factory()->player()->create();

        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'X',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'))->addDays(3);
        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'desk_submitted_by' => $otherDesk->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 100,
            'currency' => 'PHP',
        ]);

        $this->actingAs($desk)
            ->get(route('desk.bookings.show', $booking))
            ->assertOk();
    }

    public function test_desk_booking_show_forbids_other_venue(): void
    {
        $this->seed(UserTypeSeeder::class);

        $myVenue = CourtClient::factory()->create();
        $otherVenue = CourtClient::factory()->create();

        $desk = User::factory()->courtClientDesk($myVenue)->create();
        $guest = User::factory()->player()->create();

        $courtOther = Court::query()->create([
            'court_client_id' => $otherVenue->id,
            'name' => 'Away',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'))->addDays(3);
        $booking = Booking::query()->create([
            'court_client_id' => $otherVenue->id,
            'court_id' => $courtOther->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 100,
            'currency' => 'PHP',
        ]);

        $this->actingAs($desk)
            ->get(route('desk.bookings.show', $booking))
            ->assertForbidden();
    }
}
