<?php

namespace Tests\Feature;

use App\Livewire\Coach\CoachCourtsManage;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoachPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_coach_dashboard(): void
    {
        $this->get(route('account.coach.dashboard'))->assertRedirect(route('login'));
    }

    public function test_player_cannot_access_coach_routes(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('account.coach.dashboard'))->assertForbidden();
    }

    public function test_coach_can_access_coach_routes(): void
    {
        $this->seed(UserTypeSeeder::class);

        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)->get(route('account.coach.dashboard'))->assertOk();
        $this->actingAs($coach)->get(route('account.coach.courts'))->assertOk();
        $this->actingAs($coach)->get(route('account.coach.availability'))->assertOk();
        $this->actingAs($coach)->get(route('account.coach.profile'))->assertOk();
    }

    public function test_coach_toggle_venue_creates_coach_court_rows(): void
    {
        $this->seed(UserTypeSeeder::class);

        $coach = User::factory()->coach()->create();
        $client = CourtClient::factory()->create(['is_active' => true]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        Livewire::actingAs($coach)
            ->test(CoachCourtsManage::class)
            ->call('toggleVenue', $client->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('coach_courts', [
            'coach_user_id' => $coach->id,
            'court_id' => $court->id,
        ]);
    }
}
