<?php

namespace Tests\Feature;

use App\Livewire\Venue\VenueCrmContact;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\VenueContactNote;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VenueCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_tier_court_admin_can_open_customer_list_only_bookers_shown(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->basicTier()->create();
        $guest = User::factory()->player()->create();
        $neverBooked = User::factory()->player()->create();

        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::now(config('app.timezone'));
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

        $this->actingAs($admin)
            ->get(route('venue.crm.index'))
            ->assertOk()
            ->assertSee($guest->email, escape: false)
            ->assertDontSee($neverBooked->email, false);
    }

    public function test_basic_tier_court_admin_cannot_open_crm_contact_notes_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        CourtClient::factory()->forAdmin($admin)->basicTier()->create();
        $guest = User::factory()->player()->create();

        $this->actingAs($admin)
            ->get(route('venue.crm.contacts.show', $guest))
            ->assertRedirect(route('venue.plan'));
    }

    public function test_player_cannot_open_venue_crm(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('venue.crm.index'))->assertForbidden();
    }

    public function test_court_admin_sees_customer_list_and_contact_detail_for_own_venue_only(): void
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

        $this->actingAs($admin)->get(route('venue.crm.index'))->assertOk()->assertSee($guest->email, escape: false);

        $this->actingAs($admin)
            ->get(route('venue.crm.contacts.show', $guest))
            ->assertOk()
            ->assertSee('Internal notes', escape: false);

        $otherClient = CourtClient::factory()->create();
        $otherCourt = Court::query()->create([
            'court_client_id' => $otherClient->id,
            'name' => 'Other',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $stranger = User::factory()->player()->create();
        Booking::query()->create([
            'court_client_id' => $otherClient->id,
            'court_id' => $otherCourt->id,
            'user_id' => $stranger->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $this->actingAs($admin)->get(route('venue.crm.contacts.show', $stranger))->assertNotFound();
    }

    public function test_court_admin_can_add_internal_note(): void
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

        Livewire::actingAs($admin)
            ->test(VenueCrmContact::class, ['contact' => $guest])
            ->set('newNoteBody', 'Prefers morning slots.')
            ->call('addNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('venue_contact_notes', [
            'court_client_id' => $client->id,
            'user_id' => $guest->id,
            'body' => 'Prefers morning slots.',
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function test_notes_are_scoped_per_venue(): void
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
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $otherClient = CourtClient::factory()->create();
        VenueContactNote::query()->create([
            'court_client_id' => $otherClient->id,
            'user_id' => $guest->id,
            'body' => 'Secret from other venue',
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('venue.crm.contacts.show', $guest))
            ->assertOk()
            ->assertDontSee('Secret from other venue', escape: false);
    }
}
