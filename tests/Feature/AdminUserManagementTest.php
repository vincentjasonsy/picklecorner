<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserForm;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_create_user_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.users.create'))
            ->assertOk();
    }

    public function test_super_admin_users_index_has_venue_filter(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('All venues', false);
    }

    public function test_super_admin_can_create_user(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $playerTypeId = (string) UserType::query()->where('slug', UserType::SLUG_USER)->value('id');

        Livewire::actingAs($super)
            ->test(UserForm::class)
            ->set('name', 'New Player')
            ->set('email', 'newplayer@example.com')
            ->set('password', 'aB1!aaaa')
            ->set('password_confirmation', 'aB1!aaaa')
            ->set('user_type_id', $playerTypeId)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newplayer@example.com',
            'name' => 'New Player',
        ]);
    }

    public function test_super_admin_can_update_user(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $player = User::factory()->player()->create(['name' => 'Before']);

        Livewire::actingAs($super)
            ->test(UserForm::class, ['user' => $player])
            ->set('name', 'After')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $player->id,
            'name' => 'After',
        ]);
    }

    public function test_super_admin_can_delete_non_super_user(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $player = User::factory()->player()->create();

        Livewire::actingAs($super)
            ->test('admin-users-index')
            ->call('deleteUser', $player->id);

        $this->assertDatabaseMissing('users', ['id' => $player->id]);
    }

    public function test_super_admin_cannot_delete_self_via_livewire(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        Livewire::actingAs($super)
            ->test('admin-users-index')
            ->call('deleteUser', $super->id);

        $this->assertDatabaseHas('users', ['id' => $super->id]);
    }

    public function test_court_client_desk_user_requires_venue(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $venue = CourtClient::factory()->create();
        $deskTypeId = (string) UserType::query()->where('slug', UserType::SLUG_COURT_CLIENT_DESK)->value('id');

        Livewire::actingAs($super)
            ->test(UserForm::class)
            ->set('name', 'Desk Staff')
            ->set('email', 'desk@example.com')
            ->set('password', 'aB1!aaaa')
            ->set('password_confirmation', 'aB1!aaaa')
            ->set('user_type_id', $deskTypeId)
            ->set('desk_court_client_id', '')
            ->call('save')
            ->assertHasErrors(['desk_court_client_id']);

        Livewire::actingAs($super)
            ->test(UserForm::class)
            ->set('name', 'Desk Staff')
            ->set('email', 'desk@example.com')
            ->set('password', 'aB1!aaaa')
            ->set('password_confirmation', 'aB1!aaaa')
            ->set('user_type_id', $deskTypeId)
            ->set('desk_court_client_id', (string) $venue->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'desk@example.com',
            'desk_court_client_id' => $venue->id,
        ]);
    }

    public function test_super_admin_can_set_court_admin_venue_subscription_tier_from_user_edit(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->basicTier()->create();

        Livewire::actingAs($super)
            ->test(UserForm::class, ['user' => $admin])
            ->set('venue_subscription_tier', CourtClient::TIER_PREMIUM)
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $client->refresh();
        $this->assertSame(CourtClient::TIER_PREMIUM, $client->subscription_tier);
        $this->assertTrue($client->hasPremiumSubscription());
    }

    public function test_super_admin_can_view_user_summary(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $player = User::factory()->player()->create();

        $this->actingAs($super)
            ->get(route('admin.users.summary', $player))
            ->assertOk()
            ->assertSee($player->email, false)
            ->assertSee('Booking history', false);
    }

    public function test_player_cannot_view_user_summary(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $other = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('admin.users.summary', $other))
            ->assertForbidden();
    }
}
