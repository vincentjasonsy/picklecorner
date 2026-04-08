<?php

namespace Tests\Feature;

use App\Livewire\Admin\VenueQuickSetup;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVenueQuickSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_venue_quick_setup(): void
    {
        $this->get(route('admin.venue-quick-setup'))->assertRedirect(route('login'));
    }

    public function test_player_cannot_access_venue_quick_setup(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('admin.venue-quick-setup'))->assertForbidden();
    }

    public function test_super_admin_can_open_venue_quick_setup(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.venue-quick-setup'))
            ->assertOk()
            ->assertSee('Quick venue setup', escape: false);
    }

    public function test_super_admin_can_create_venue_admin_and_desk_in_one_step(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $courtAdminTypeId = UserType::query()->where('slug', UserType::SLUG_COURT_ADMIN)->value('id');
        $deskTypeId = UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id');

        Livewire::actingAs($super)
            ->test(VenueQuickSetup::class)
            ->set('name', 'Quick Venue Beta')
            ->set('city', 'Cebu')
            ->set('hourly_rate_pesos', '400')
            ->set('admin_name', 'Admin Person')
            ->set('admin_email', 'admin-quick@example.test')
            ->set('admin_password', 'Password-1Quick!')
            ->set('admin_password_confirmation', 'Password-1Quick!')
            ->set('create_desk_account', true)
            ->set('desk_name', 'Desk Person')
            ->set('desk_email', 'desk-quick@example.test')
            ->set('desk_password', 'Password-1Desk!')
            ->set('desk_password_confirmation', 'Password-1Desk!')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('setupComplete', true);

        $client = CourtClient::query()->where('name', 'Quick Venue Beta')->first();
        $this->assertNotNull($client);
        $this->assertSame('Cebu', $client->city);
        $this->assertSame(40_000, $client->hourly_rate_cents);

        $admin = User::query()->where('email', 'admin-quick@example.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame((string) $courtAdminTypeId, (string) $admin->user_type_id);
        $this->assertSame($admin->id, $client->admin_user_id);
        $this->assertNull($admin->desk_court_client_id);

        $desk = User::query()->where('email', 'desk-quick@example.test')->first();
        $this->assertNotNull($desk);
        $this->assertSame((string) $deskTypeId, (string) $desk->user_type_id);
        $this->assertSame($client->id, $desk->desk_court_client_id);

        $this->assertGreaterThanOrEqual(2, $client->courts()->count());
        $this->assertSame(7, $client->weeklyHours()->count());
    }

    public function test_desk_account_can_be_skipped(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        Livewire::actingAs($super)
            ->test(VenueQuickSetup::class)
            ->set('name', 'No Desk Venue')
            ->set('admin_name', 'Solo Admin')
            ->set('admin_email', 'solo-admin@example.test')
            ->set('admin_password', 'Password-1Solo!')
            ->set('admin_password_confirmation', 'Password-1Solo!')
            ->set('create_desk_account', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('setupComplete', true)
            ->assertSet('createdDeskAccount', false);

        $client = CourtClient::query()->where('name', 'No Desk Venue')->first();
        $this->assertNotNull($client);

        $this->assertSame(1, User::query()->where('email', 'solo-admin@example.test')->count());
        $this->assertSame(
            0,
            User::query()->where('user_type_id', UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id'))
                ->where('desk_court_client_id', $client->id)
                ->count(),
        );
    }

    public function test_desk_email_must_differ_from_admin_email(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        Livewire::actingAs($super)
            ->test(VenueQuickSetup::class)
            ->set('name', 'Dup Email Venue')
            ->set('admin_name', 'A')
            ->set('admin_email', 'same@example.test')
            ->set('admin_password', 'Password-1Same!')
            ->set('admin_password_confirmation', 'Password-1Same!')
            ->set('create_desk_account', true)
            ->set('desk_name', 'B')
            ->set('desk_email', 'same@example.test')
            ->set('desk_password', 'Password-1Same!')
            ->set('desk_password_confirmation', 'Password-1Same!')
            ->call('save')
            ->assertHasErrors(['desk_email']);
    }
}
