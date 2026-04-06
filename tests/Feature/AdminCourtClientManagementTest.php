<?php

namespace Tests\Feature;

use App\Livewire\Admin\CourtClientCreate;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCourtClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_court_client_create(): void
    {
        $this->get(route('admin.court-clients.create'))->assertRedirect(route('login'));
    }

    public function test_player_cannot_access_court_client_create(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('admin.court-clients.create'))->assertForbidden();
    }

    public function test_super_admin_can_open_court_client_create(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.court-clients.create'))->assertOk();
    }

    public function test_super_admin_can_create_venue_and_redirect_to_edit(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $courtAdmin = User::factory()->courtAdmin()->create();

        $r = Livewire::actingAs($super)
            ->test(CourtClientCreate::class)
            ->set('name', 'Test Venue Alpha')
            ->set('city', 'Manila')
            ->set('hourly_rate_pesos', '350')
            ->set('peak_hourly_rate_pesos', '500')
            ->set('admin_user_id', $courtAdmin->id)
            ->call('save')
            ->assertHasNoErrors();

        $client = CourtClient::query()->where('name', 'Test Venue Alpha')->first();
        $this->assertNotNull($client);
        $r->assertRedirect(route('admin.court-clients.edit', $client));
        $this->assertSame($courtAdmin->id, $client->admin_user_id);
        $this->assertGreaterThanOrEqual(2, $client->courts()->count());
        $this->assertSame(7, $client->weeklyHours()->count());
    }

    public function test_super_admin_can_delete_venue_without_bookings(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $client = CourtClient::factory()->create();

        Livewire::actingAs($super)
            ->test('admin-court-clients-index')
            ->call('deleteCourtClient', $client->id);

        $this->assertDatabaseMissing('court_clients', ['id' => $client->id]);
    }

    public function test_super_admin_cannot_delete_venue_with_booking(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
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

        Livewire::actingAs($super)
            ->test('admin-court-clients-index')
            ->call('deleteCourtClient', $client->id);

        $this->assertDatabaseHas('court_clients', ['id' => $client->id]);
    }
}
