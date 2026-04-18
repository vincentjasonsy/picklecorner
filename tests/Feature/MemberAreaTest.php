<?php

namespace Tests\Feature;

use App\Livewire\Member\MemberBookingHistory;
use App\Livewire\Member\MemberBookingShow;
use App\Livewire\Member\MemberProfileSettings;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MemberAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_member_dashboard(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
    }

    public function test_player_can_view_dashboard_bookings_and_settings(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('account.dashboard'))->assertOk();
        $this->actingAs($player)->get(route('account.book'))->assertOk();
        $this->actingAs($player)->get(route('account.bookings'))->assertOk();
        $this->actingAs($player)->get(route('account.court-open-plays.index'))->assertOk();
        $this->actingAs($player)->get(route('account.dashboard'))->assertOk()->assertSee('GameQ — your rivals', false);
        $this->actingAs($player)->get(route('account.settings'))->assertOk()->assertSee('Account details', false);
    }

    public function test_player_can_update_profile(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create(['name' => 'Old Name']);

        Livewire::actingAs($player)
            ->test(MemberProfileSettings::class)
            ->set('name', 'New Racket Name')
            ->set('email', $player->email)
            ->call('saveProfile')
            ->assertHasNoErrors();

        $player->refresh();
        $this->assertSame('New Racket Name', $player->name);
    }

    public function test_player_sees_booking_in_history(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::parse('2026-05-01 10:00:00', config('app.timezone'));
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 10_000,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(MemberBookingHistory::class)
            ->assertSee('Court A')
            ->assertSee($client->name);
    }

    public function test_player_can_view_own_booking_details(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $other = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court B',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::parse('2026-06-10 14:00:00', config('app.timezone'));
        $mine = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHours(2),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5000,
            'currency' => 'PHP',
        ]);

        $theirs = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $other->id,
            'starts_at' => $starts->copy()->addDay(),
            'ends_at' => $starts->copy()->addDay()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(MemberBookingShow::class, ['booking' => $mine])
            ->assertOk()
            ->assertSee($client->name, escape: false)
            ->assertSee('Court B', escape: false);

        $this->actingAs($player)
            ->get(route('account.bookings.calendar', $mine))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('BEGIN:VCALENDAR', escape: false);

        $this->actingAs($player)->get(route('account.bookings.show', $theirs))->assertForbidden();
        $this->actingAs($other)->get(route('account.bookings.calendar', $mine))->assertForbidden();
    }

    public function test_booking_details_list_all_courts_when_same_booking_request(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $courtA = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'North Court',
            'sort_order' => 1,
            'environment' => Court::ENV_INDOOR,
            'is_available' => true,
        ]);
        $courtB = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'South Court',
            'sort_order' => 2,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $requestId = (string) Str::uuid();
        $starts = Carbon::parse('2026-07-01 09:00:00', config('app.timezone'));

        $first = Booking::query()->create([
            'court_client_id' => $client->id,
            'booking_request_id' => $requestId,
            'court_id' => $courtA->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 3000,
            'currency' => 'PHP',
        ]);

        Booking::query()->create([
            'court_client_id' => $client->id,
            'booking_request_id' => $requestId,
            'court_id' => $courtB->id,
            'user_id' => $player->id,
            'starts_at' => $starts->copy()->addHour(),
            'ends_at' => $starts->copy()->addHours(2),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 3000,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(MemberBookingShow::class, ['booking' => $first])
            ->assertOk()
            ->assertSee('Courts', escape: false)
            ->assertSee('North Court', escape: false)
            ->assertSee('South Court', escape: false);
    }
}
