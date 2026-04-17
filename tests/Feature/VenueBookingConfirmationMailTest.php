<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Notifications\CourtAdminVenueBookingSubmittedNotification;
use App\Notifications\MemberVenueBookingSubmittedNotification;
use App\Services\BookingCheckoutSnapshot;
use App\Services\VenueBookingConfirmationNotifier;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class VenueBookingConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_and_venue_admin_receive_confirmation_notifications(): void
    {
        $this->seed(UserTypeSeeder::class);

        Notification::fake();

        $admin = User::factory()->courtAdmin()->create(['email' => 'venue-admin@example.com']);
        $player = User::factory()->player()->create(['email' => 'player@example.com']);

        $client = CourtClient::factory()->forAdmin($admin)->create(['slug' => 'mail-test-club']);

        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $rid = (string) Str::uuid();
        $starts = now()->addDay()->setTime(9, 0);
        $ends = now()->addDay()->setTime(10, 0);

        $booking = Booking::query()->create([
            'id' => (string) Str::uuid(),
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'booking_request_id' => $rid,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => Booking::STATUS_PENDING_APPROVAL,
            'currency' => 'PHP',
            'checkout_snapshot' => BookingCheckoutSnapshot::memberPublicCheckout(
                'PHP',
                'test fee',
                10000,
                0,
                1000,
                11000,
                null,
                10000,
                0,
                10000,
                0,
                10000,
                0,
                1000,
            ),
        ]);

        VenueBookingConfirmationNotifier::notifyMemberPublicSubmission($client, $player, [$booking]);

        Notification::assertSentTo($player, MemberVenueBookingSubmittedNotification::class);
        Notification::assertSentTo($admin, CourtAdminVenueBookingSubmittedNotification::class);
    }

    public function test_when_booker_is_venue_admin_only_member_notification_sent(): void
    {
        $this->seed(UserTypeSeeder::class);

        Notification::fake();

        $user = User::factory()->courtAdmin()->create(['email' => 'same@example.com']);

        $client = CourtClient::factory()->forAdmin($user)->create(['slug' => 'mail-same-admin']);

        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $rid = (string) Str::uuid();
        $starts = now()->addDay()->setTime(9, 0);
        $ends = now()->addDay()->setTime(10, 0);

        $booking = Booking::query()->create([
            'id' => (string) Str::uuid(),
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $user->id,
            'booking_request_id' => $rid,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
            'checkout_snapshot' => BookingCheckoutSnapshot::memberPublicCheckout(
                'PHP',
                null,
                5000,
                0,
                0,
                5000,
                null,
                5000,
                0,
                5000,
                0,
                5000,
                0,
                0,
            ),
        ]);

        VenueBookingConfirmationNotifier::notifyMemberPublicSubmission($client, $user, [$booking]);

        Notification::assertSentTo($user, MemberVenueBookingSubmittedNotification::class);
        Notification::assertNotSentTo($user, CourtAdminVenueBookingSubmittedNotification::class);
    }
}
