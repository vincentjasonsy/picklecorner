<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\OpenPlayParticipant;
use App\Models\User;
use App\Livewire\Member\MemberCourtOpenPlayHost;
use App\Livewire\Member\MemberCourtOpenPlayJoin;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourtOpenPlayTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_open_court_open_play_hub(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('account.court-open-plays.index'))->assertOk();
    }

    public function test_host_can_manage_open_play_and_joiner_can_request(): void
    {
        $this->seed(UserTypeSeeder::class);

        $host = User::factory()->player()->create();
        $joiner = User::factory()->player()->create();
        $client = CourtClient::factory()->create(['is_active' => true]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'))->addDays(3)->setHour(10)->setMinute(0)->setSecond(0);
        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $host->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1000_00,
            'currency' => 'PHP',
            'is_open_play' => true,
            'open_play_max_slots' => 4,
            'open_play_public_notes' => 'Bring water.',
            'open_play_host_payment_details' => 'GCash 09xx — ₱200',
        ]);

        $this->actingAs($host)->get(route('account.court-open-plays.host', $booking))->assertOk()->assertSee('Manage open play');

        $this->actingAs($host)->get(route('account.court-open-plays.join', $booking))->assertOk()->assertSee('You’re the host');

        $this->actingAs($joiner)->get(route('account.court-open-plays.join', $booking))->assertOk();

        Livewire::actingAs($joiner)
            ->test(MemberCourtOpenPlayJoin::class, ['booking' => $booking])
            ->set('joinerNote', '3.5 player')
            ->call('requestJoin')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('open_play_participants', [
            'booking_id' => $booking->id,
            'user_id' => $joiner->id,
            'status' => OpenPlayParticipant::STATUS_PENDING,
        ]);

        $participant = OpenPlayParticipant::query()
            ->where('booking_id', $booking->id)
            ->where('user_id', $joiner->id)
            ->firstOrFail();

        Livewire::actingAs($host)
            ->test(MemberCourtOpenPlayHost::class, ['booking' => $booking])
            ->call('acceptParticipant', $participant->id);

        $participant->refresh();
        $this->assertSame(OpenPlayParticipant::STATUS_ACCEPTED, $participant->status);
    }

    public function test_non_host_cannot_open_host_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $host = User::factory()->player()->create();
        $other = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'C1',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $starts = Carbon::now()->addDay();
        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $host->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'is_open_play' => true,
            'open_play_max_slots' => 2,
            'open_play_host_payment_details' => 'Pay me',
        ]);

        $this->actingAs($other)->get(route('account.court-open-plays.host', $booking))->assertForbidden();
    }
}
