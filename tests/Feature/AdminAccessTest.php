<?php

namespace Tests\Feature;

use App\Livewire\NotificationBell;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Notifications\InternalTeamPlayReminderNotification;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_player_cannot_access_admin(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get('/admin')->assertForbidden();
    }

    public function test_super_admin_can_open_admin_dashboard(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get('/admin')->assertOk();
    }

    public function test_super_admin_can_open_activity_log(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get('/admin/activity')->assertOk();
    }

    public function test_super_admin_can_open_manual_booking_hub(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.manual-booking.hub'))->assertOk();
    }

    public function test_super_admin_can_open_booking_rates(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.booking-rates'))
            ->assertOk()
            ->assertSee('Platform booking service fee', escape: false)
            ->assertSee('Rate history', escape: false)
            ->assertSee('Add new rate', escape: false);
    }

    public function test_super_admin_can_open_internal_play_reminders_dashboard(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.internal-play-reminders'))
            ->assertOk()
            ->assertSee('Team play reminders', escape: false);
    }

    public function test_player_cannot_open_internal_play_reminders_dashboard(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('admin.internal-play-reminders'))
            ->assertForbidden();
    }

    public function test_player_cannot_open_gallery_approvals(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('admin.gallery-approvals'))
            ->assertForbidden();
    }

    public function test_court_admin_cannot_open_gallery_approvals(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        CourtClient::factory()->forAdmin($admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.gallery-approvals'))
            ->assertForbidden();
    }

    public function test_super_admin_can_open_review_approvals(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.review-approvals'))
            ->assertOk()
            ->assertSee('User review approvals', escape: false);
    }

    public function test_player_cannot_open_review_approvals(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('admin.review-approvals'))
            ->assertForbidden();
    }

    public function test_super_admin_can_open_featured_venues(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.featured-venues'))
            ->assertOk()
            ->assertSee('Featured venues by city', false);
    }

    public function test_internal_play_reminder_command_sends_database_notification_when_eligible(): void
    {
        $this->seed(UserTypeSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-04-08 12:00:00', config('app.timezone')));

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create(['is_active' => true]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $starts = Carbon::now()->subDays(15);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        Artisan::call('internal:send-team-play-reminders');

        $this->assertSame(1, $player->fresh()->notifications()->count());
        $this->assertStringContainsString(
            'Time to book another game?',
            (string) $player->fresh()->notifications()->first()?->data['title'],
        );
    }

    public function test_internal_play_reminder_command_skips_when_booking_recent(): void
    {
        $this->seed(UserTypeSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-04-08 12:00:00', config('app.timezone')));

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $starts = Carbon::now()->subDays(5);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        Artisan::call('internal:send-team-play-reminders');

        $this->assertSame(0, $player->fresh()->notifications()->count());
    }

    public function test_internal_play_reminder_command_skips_when_unsubscribed(): void
    {
        $this->seed(UserTypeSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-04-08 12:00:00', config('app.timezone')));

        $player = User::factory()->player()->create();
        $player->forceFill(['internal_team_play_reminders_unsubscribed_at' => now()->subDay()])->save();
        $client = CourtClient::factory()->create(['is_active' => true]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $starts = Carbon::now()->subDays(15);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        Artisan::call('internal:send-team-play-reminders');

        $this->assertSame(0, $player->fresh()->notifications()->count());
    }

    public function test_internal_play_reminder_command_skips_when_upcoming_booking_is_latest(): void
    {
        $this->seed(UserTypeSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-04-08 12:00:00', config('app.timezone')));

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $past = Carbon::now()->subDays(30);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $past,
            'ends_at' => $past->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);
        $future = Carbon::now()->addDays(2);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $future,
            'ends_at' => $future->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        Artisan::call('internal:send-team-play-reminders');

        $this->assertSame(0, $player->fresh()->notifications()->count());
    }

    public function test_internal_play_reminder_command_respects_cooldown_between_sends(): void
    {
        $this->seed(UserTypeSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-04-08 12:00:00', config('app.timezone')));

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create(['is_active' => true]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $starts = Carbon::now()->subDays(20);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        Artisan::call('internal:send-team-play-reminders');
        Artisan::call('internal:send-team-play-reminders');

        $this->assertSame(1, $player->fresh()->notifications()->count());
    }

    public function test_notification_bell_shows_play_reminder_copy(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $player->notify(new InternalTeamPlayReminderNotification(14, []));

        Livewire::actingAs($player)
            ->test(NotificationBell::class)
            ->set('open', true)
            ->assertSee('Court reminder', escape: false)
            ->assertSee('Time to book another game?', escape: false)
            ->assertSee('Unsubscribe from booking reminders', escape: false);
    }

    public function test_internal_play_reminder_signed_unsubscribe_works(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $url = URL::signedRoute('internal-team-play-reminders.unsubscribe', ['user' => $player]);

        $this->get($url)->assertOk()->assertSee('Reminders turned off', escape: false);

        $this->assertNotNull($player->fresh()->internal_team_play_reminders_unsubscribed_at);
    }

    public function test_internal_play_reminder_unsubscribe_requires_valid_signature(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->get(route('internal-team-play-reminders.unsubscribe', ['user' => $player]))->assertForbidden();
    }

    public function test_member_can_resubscribe_play_reminders(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $player->forceFill(['internal_team_play_reminders_unsubscribed_at' => now()])->save();

        $this->actingAs($player)
            ->post(route('internal-team-play-reminders.resubscribe'))
            ->assertRedirect(route('account.dashboard'));

        $this->assertNull($player->fresh()->internal_team_play_reminders_unsubscribed_at);
    }

    public function test_super_admin_resubscribe_redirects_to_admin_home(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $super->forceFill(['internal_team_play_reminders_unsubscribed_at' => now()])->save();

        $this->actingAs($super)
            ->post(route('internal-team-play-reminders.resubscribe'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNull($super->fresh()->internal_team_play_reminders_unsubscribed_at);
    }

    public function test_guest_cannot_resubscribe_play_reminders(): void
    {
        $this->post(route('internal-team-play-reminders.resubscribe'))->assertRedirect(route('login'));
    }
}
