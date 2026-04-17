<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Notifications\MemberNewCourtOpeningNotification;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourtOpeningMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_in_same_city_with_marketing_opt_in_gets_new_court_email(): void
    {
        $this->seed(UserTypeSeeder::class);

        config(['booking.court_opening_emails' => true]);
        Notification::fake();

        $player = User::factory()->player()->create([
            'home_city' => 'Makati',
            'marketing_emails_consent_at' => now(),
        ]);

        $client = CourtClient::factory()->create([
            'city' => 'Makati',
            'is_active' => true,
            'slug' => 'makati-hub',
        ]);

        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Outdoor 9',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        Notification::assertSentTo($player, MemberNewCourtOpeningNotification::class);

        $court = Court::query()->where('court_client_id', $client->id)->first();
        $this->assertNotNull($court);
        $this->assertNotNull($court->opening_notice_sent_at);
    }

    public function test_upcoming_opening_type_when_opens_at_is_in_the_future(): void
    {
        $this->seed(UserTypeSeeder::class);

        config(['booking.court_opening_emails' => true]);
        Notification::fake();

        $player = User::factory()->player()->create([
            'home_city' => 'Cebu',
            'marketing_emails_consent_at' => now(),
        ]);

        $client = CourtClient::factory()->create([
            'city' => 'Cebu',
            'is_active' => true,
            'slug' => 'cebu-hub',
        ]);

        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Indoor 2',
            'sort_order' => 0,
            'environment' => Court::ENV_INDOOR,
            'is_available' => true,
            'opens_at' => now()->addDays(14),
        ]);

        Notification::assertSentTo(
            $player,
            MemberNewCourtOpeningNotification::class,
            fn (MemberNewCourtOpeningNotification $n): bool => $n->isUpcomingOpening === true,
        );
    }

    public function test_skips_when_home_city_differs(): void
    {
        $this->seed(UserTypeSeeder::class);

        config(['booking.court_opening_emails' => true]);
        Notification::fake();

        $player = User::factory()->player()->create([
            'home_city' => 'Manila',
            'marketing_emails_consent_at' => now(),
        ]);

        $client = CourtClient::factory()->create([
            'city' => 'Makati',
            'is_active' => true,
            'slug' => 'other-hub',
        ]);

        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Outdoor 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        Notification::assertNotSentTo($player, MemberNewCourtOpeningNotification::class);
    }
}
