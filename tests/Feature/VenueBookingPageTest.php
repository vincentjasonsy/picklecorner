<?php

namespace Tests\Feature;

use App\Livewire\BookNow\VenueBookingPage;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtClientClosedDay;
use App\Models\GiftCard;
use App\Models\User;
use App\Models\VenueWeeklyHour;
use App\Services\GiftCardService;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VenueBookingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_again_query_advances_authenticated_member_to_review_step(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-04-17 12:00:00', $tz));

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'book-again-review']);
        for ($d = 0; $d < 7; $d++) {
            VenueWeeklyHour::query()->create([
                'court_client_id' => $client->id,
                'day_of_week' => $d,
                'is_closed' => false,
                'opens_at' => '07:00',
                'closes_at' => '23:00',
            ]);
        }
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $targetDate = '2026-04-18';
        $slots = $court->id.'-18,'.$court->id.'-19';
        $url = route('account.book.venue', $client).'?'.http_build_query([
            'book_date' => $targetDate,
            'book_slots' => $slots,
            'book_step' => 'review',
        ]);

        $this->actingAs($player)
            ->get($url)
            ->assertOk()
            ->assertSee('Courts subtotal', false);

        Carbon::setTestNow();
    }

    public function test_guest_can_open_venue_booking_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'alpha-club']);
        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $this->get(route('book-now.venue.book', $client))
            ->assertOk()
            ->assertSee('Partner venue', false)
            ->assertSee('Choose date', false);
    }

    public function test_player_can_open_venue_booking_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'beta-club']);
        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $player = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('book-now.venue.book', $client))
            ->assertOk()
            ->assertSee('Availability', false);
    }

    public function test_court_admin_can_view_public_venue_booking_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        $client = CourtClient::factory()->forAdmin($admin)->create(['is_active' => true, 'slug' => 'managed']);
        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('book-now.venue.book', $client))
            ->assertOk();
    }

    public function test_book_now_page_shows_all_venues_section(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create([
            'is_active' => true,
            'name' => 'Gamma Pickleball',
            'slug' => 'gamma-pb',
        ]);
        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $this->get(route('book-now'))
            ->assertOk()
            ->assertSee('Venues &amp; courts', false)
            ->assertSee('Gamma Pickleball', false)
            ->assertSee('Tap card to book', false);
    }

    public function test_venue_closed_day_shows_no_slots_on_public_booking_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'closed-day-club']);
        Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $closedDate = '2031-07-04';
        CourtClientClosedDay::query()->create([
            'court_client_id' => $client->id,
            'closed_on' => $closedDate,
        ]);

        Livewire::test(VenueBookingPage::class, ['courtClient' => $client])
            ->set('bookingCalendarDate', $closedDate)
            ->assertSet('bookingCalendarDate', $closedDate)
            ->tap(function ($c): void {
                $this->assertSame([], $c->instance()->slotHoursForSelectedDate());
                $this->assertTrue($c->instance()->isBookingDateVenueClosure());
            })
            ->assertSee('This venue is closed on this date', false);
    }

    public function test_draft_restores_after_login_flag_session(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'restore-club']);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $player = User::factory()->player()->create();

        session()->put(VenueBookingPage::DRAFT_SESSION_KEY, [
            'court_client_id' => $client->id,
            'booking_calendar_date' => now()->format('Y-m-d'),
            'selected_slots' => [$court->id.'-9'],
            'payment_method' => Booking::PAYMENT_PAYMONGO,
            'payment_reference' => '',
            'step' => 'review',
        ]);
        session()->put(VenueBookingPage::AFTER_LOGIN_SESSION_KEY, true);

        Livewire::actingAs($player)
            ->test(VenueBookingPage::class, ['courtClient' => $client])
            ->assertSet('step', 'review')
            ->assertSet('bookingCalendarDate', now()->format('Y-m-d'))
            ->assertSet('paymentMethod', Booking::PAYMENT_PAYMONGO);
    }

    public function test_draft_after_login_drops_review_when_date_is_venue_closure(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => true, 'slug' => 'restore-closed']);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $closedDate = '2032-01-01';
        CourtClientClosedDay::query()->create([
            'court_client_id' => $client->id,
            'closed_on' => $closedDate,
        ]);

        $player = User::factory()->player()->create();

        session()->put(VenueBookingPage::DRAFT_SESSION_KEY, [
            'court_client_id' => $client->id,
            'booking_calendar_date' => $closedDate,
            'selected_slots' => [$court->id.'-9'],
            'payment_method' => Booking::PAYMENT_PAYMONGO,
            'payment_reference' => '',
            'step' => 'review',
        ]);
        session()->put(VenueBookingPage::AFTER_LOGIN_SESSION_KEY, true);

        Livewire::actingAs($player)
            ->test(VenueBookingPage::class, ['courtClient' => $client])
            ->assertSet('step', 'times')
            ->assertSet('selectedSlots', [])
            ->assertSet('bookingCalendarDate', $closedDate);
    }

    public function test_player_can_submit_venue_booking_with_gift_card(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create([
            'is_active' => true,
            'slug' => 'gift-venue',
            'hourly_rate_cents' => 10_000,
        ]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court 1',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        GiftCardService::issue(
            $client,
            GiftCard::VALUE_FIXED,
            500_000,
            null,
            null,
            null,
            null,
            null,
            'MEMBER-GC',
        );

        $player = User::factory()->player()->create();

        Livewire::actingAs($player)
            ->test(VenueBookingPage::class, ['courtClient' => $client])
            ->set('bookingCalendarDate', now()->format('Y-m-d'))
            ->set('selectedSlots', [$court->id.'-9'])
            ->set('step', 'review')
            ->set('giftCardCode', 'MEMBER-GC')
            ->set('ackConvenienceFeeNonRefundable', true)
            ->call('submitRequest')
            ->assertHasNoErrors()
            ->assertRedirect();

        $booking = Booking::query()->where('court_id', $court->id)->first();
        $this->assertNotNull($booking);
        $this->assertNotNull($booking->gift_card_id);
        $this->assertGreaterThan(0, (int) $booking->gift_card_redeemed_cents);
        $this->assertTrue($booking->amount_cents === null || (int) $booking->amount_cents === 0);
    }
}
