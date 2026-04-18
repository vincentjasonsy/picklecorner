<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\PaymongoBookingIntent;
use App\Models\User;
use App\Models\VenueWeeklyHour;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayMongoBookingReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_payment_redirects_to_first_booking_show(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'pay-return-club']);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $player = User::factory()->player()->create();

        $requestId = (string) Str::uuid();
        $intentId = (string) Str::uuid();

        PaymongoBookingIntent::query()->create([
            'id' => $intentId,
            'user_id' => $player->id,
            'court_client_id' => $client->id,
            'amount_centavos' => 50_000,
            'currency' => 'PHP',
            'payload_json' => [],
            'status' => PaymongoBookingIntent::STATUS_COMPLETED,
            'booking_request_id' => $requestId,
        ]);

        $starts = Carbon::parse('2026-05-01 09:00:00', config('app.timezone'));
        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'booking_request_id' => $requestId,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'payment_method' => Booking::PAYMENT_PAYMONGO,
            'currency' => 'PHP',
        ]);

        $this->actingAs($player)
            ->get(route('paymongo.booking.return', ['intent' => $intentId]))
            ->assertRedirect(route('account.bookings.show', $booking))
            ->assertSessionHas('status');
    }

    public function test_completed_without_matching_booking_redirects_to_bookings_index(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'pay-return-empty']);
        $player = User::factory()->player()->create();

        $intentId = (string) Str::uuid();

        PaymongoBookingIntent::query()->create([
            'id' => $intentId,
            'user_id' => $player->id,
            'court_client_id' => $client->id,
            'amount_centavos' => 50_000,
            'currency' => 'PHP',
            'payload_json' => [],
            'status' => PaymongoBookingIntent::STATUS_COMPLETED,
            'booking_request_id' => (string) Str::uuid(),
        ]);

        $this->actingAs($player)
            ->get(route('paymongo.booking.return', ['intent' => $intentId]))
            ->assertRedirect(route('account.bookings'));
    }

    public function test_pending_intent_after_return_shows_unpaid_checkout_notice(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'pay-return-pending']);
        $player = User::factory()->player()->create();

        $intentId = (string) Str::uuid();

        PaymongoBookingIntent::query()->create([
            'id' => $intentId,
            'user_id' => $player->id,
            'court_client_id' => $client->id,
            'amount_centavos' => 50_000,
            'currency' => 'PHP',
            'payload_json' => ['booking_calendar_date' => '2026-06-15'],
            'status' => PaymongoBookingIntent::STATUS_PENDING,
        ]);

        $this->actingAs($player)
            ->get(route('paymongo.booking.return', ['intent' => $intentId]))
            ->assertRedirect(route('book-now.venue.book', $client))
            ->assertSessionHas('paymongo_checkout');

        $flash = session('paymongo_checkout');
        $this->assertIsArray($flash);
        $this->assertSame('unpaid', $flash['kind']);
        $this->assertArrayHasKey('amount_label', $flash);
        $this->assertSame($intentId, $flash['intent_id']);
    }

    public function test_failed_intent_shows_failed_checkout_notice(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'pay-return-failed']);
        $player = User::factory()->player()->create();

        $intentId = (string) Str::uuid();

        PaymongoBookingIntent::query()->create([
            'id' => $intentId,
            'user_id' => $player->id,
            'court_client_id' => $client->id,
            'amount_centavos' => 50_000,
            'currency' => 'PHP',
            'payload_json' => [],
            'status' => PaymongoBookingIntent::STATUS_FAILED,
        ]);

        $this->actingAs($player)
            ->get(route('paymongo.booking.return', ['intent' => $intentId]))
            ->assertRedirect(route('book-now.venue.book', $client))
            ->assertSessionHas('paymongo_checkout');

        $this->assertSame('failed', session('paymongo_checkout')['kind']);
    }

    public function test_cancel_route_shows_cancelled_notice_for_owner(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'pay-cancel-club']);
        $player = User::factory()->player()->create();

        $intentId = (string) Str::uuid();

        PaymongoBookingIntent::query()->create([
            'id' => $intentId,
            'user_id' => $player->id,
            'court_client_id' => $client->id,
            'amount_centavos' => 40_000,
            'currency' => 'PHP',
            'payload_json' => ['booking_calendar_date' => '2026-07-01'],
            'status' => PaymongoBookingIntent::STATUS_PENDING,
        ]);

        $this->actingAs($player)
            ->get(route('paymongo.booking.cancel', ['intent' => $intentId]))
            ->assertRedirect(route('book-now.venue.book', $client))
            ->assertSessionHas('paymongo_checkout');

        $this->assertSame('cancelled', session('paymongo_checkout')['kind']);
        $this->assertArrayHasKey('intent_id', session('paymongo_checkout'));
        $this->assertSame($intentId, session('paymongo_checkout')['intent_id']);
    }

    public function test_cancel_restores_review_step_and_shows_slots_from_intent_payload(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00', $tz));

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'pay-restore-club']);
        for ($d = 0; $d < 7; $d++) {
            VenueWeeklyHour::query()->create([
                'court_client_id' => $client->id,
                'day_of_week' => $d,
                'is_closed' => false,
                'opens_at' => '07:00',
                'closes_at' => '22:00',
            ]);
        }
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $player = User::factory()->player()->create();
        $intentId = (string) Str::uuid();
        $targetDate = '2026-04-18';
        $slots = [(string) $court->id.'-18', (string) $court->id.'-19'];

        PaymongoBookingIntent::query()->create([
            'id' => $intentId,
            'user_id' => $player->id,
            'court_client_id' => $client->id,
            'amount_centavos' => 103_500,
            'currency' => 'PHP',
            'payload_json' => [
                'booking_calendar_date' => $targetDate,
                'selected_slots' => $slots,
                'gift_card_code' => '',
                'coach_user_id' => null,
                'coach_paid_hours' => 0,
                'is_open_play' => false,
            ],
            'status' => PaymongoBookingIntent::STATUS_PENDING,
        ]);

        $this->actingAs($player)
            ->get(route('paymongo.booking.cancel', ['intent' => $intentId]))
            ->assertRedirect(route('book-now.venue.book', $client));

        $page = $this->actingAs($player)
            ->get(route('book-now.venue.book', $client));

        $page->assertOk();
        $page->assertSee('Review your request', false);
        $page->assertSee('Selected slots', false);

        Carbon::setTestNow();
    }
}
