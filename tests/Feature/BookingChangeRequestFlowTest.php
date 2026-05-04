<?php

namespace Tests\Feature;

use App\Livewire\Desk\DeskBookingChangeRequests;
use App\Livewire\Member\MemberBookingShow;
use App\Livewire\Venue\VenueBookingChangeRequests;
use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserVenueCredit;
use App\Models\UserVenueCreditLedgerEntry;
use App\Models\VenueWeeklyHour;
use App\Services\BookingChangeRequestService;
use App\Services\BookingFeeService;
use App\Services\PublicVenueBookingSubmission;
use App\Services\UserVenueCreditService;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingChangeRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserTypeSeeder::class);
    }

    public function test_member_submits_refund_request_and_venue_admin_accepts_credits_and_cancels_booking(): void
    {
        $client = CourtClient::factory()->create();
        $admin = User::factory()->courtAdmin()->create();
        $client->update(['admin_user_id' => $admin->id]);

        $player = User::factory()->player()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::parse('2026-08-10 10:00:00', config('app.timezone'));
        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5_000,
            'platform_booking_fee_cents' => 100,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(MemberBookingShow::class, ['booking' => $booking])
            ->set('refundNote', 'Rain out')
            ->call('submitRefundRequest')
            ->assertHasNoErrors();

        $req = BookingChangeRequest::query()->where('booking_id', $booking->id)->first();
        $this->assertNotNull($req);
        $this->assertSame(BookingChangeRequest::TYPE_REFUND_CREDIT, $req->type);
        $this->assertSame(5_100, (int) $req->offered_credit_cents);

        Livewire::actingAs($admin)
            ->test(VenueBookingChangeRequests::class)
            ->call('openAccept', $req->id)
            ->call('confirmAccept');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->status);

        $creditRow = UserVenueCredit::query()
            ->where('user_id', $player->id)
            ->where('currency', 'PHP')
            ->first();
        $this->assertNotNull($creditRow);
        $this->assertSame(5_100, $creditRow->balance_cents);
    }

    public function test_platform_credit_issued_at_one_venue_redeemable_at_another_same_currency(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-05-01 12:00:00', $tz));

        $venueA = CourtClient::factory()->create();
        $venueB = CourtClient::factory()->create();
        foreach ([$venueA, $venueB] as $client) {
            for ($d = 0; $d < 7; $d++) {
                VenueWeeklyHour::query()->create([
                    'court_client_id' => $client->id,
                    'day_of_week' => $d,
                    'is_closed' => false,
                    'opens_at' => '07:00',
                    'closes_at' => '23:00',
                ]);
            }
        }

        $player = User::factory()->player()->create();
        $courtA = Court::query()->create([
            'court_client_id' => $venueA->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $courtB = Court::query()->create([
            'court_client_id' => $venueB->id,
            'name' => 'Court B',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $startsA = Carbon::parse('2026-05-10 10:00:00', $tz);
        $bookingA = Booking::query()->create([
            'court_client_id' => $venueA->id,
            'court_id' => $courtA->id,
            'user_id' => $player->id,
            'starts_at' => $startsA,
            'ends_at' => $startsA->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1_000,
            'currency' => 'PHP',
        ]);

        UserVenueCreditService::addCredit(
            $player,
            $venueA,
            10_000,
            UserVenueCreditLedgerEntry::ENTRY_TYPE_REFUND,
            $bookingA,
            'Test credit issuance',
        );

        $startsB = Carbon::parse('2026-05-20 10:00:00', $tz);
        $endsB = $startsB->copy()->addHour();
        $specs = [
            [
                'court' => $courtB,
                'starts' => $startsB,
                'ends' => $endsB,
                'gross_cents' => 2_000,
                'court_gross_cents' => 2_000,
                'hours' => [10],
                'coach_fee_cents' => 0,
            ],
        ];
        $checkoutSlice = 2_000 + BookingFeeService::calculateCentsForSpecs($specs);

        PublicVenueBookingSubmission::submit(
            $venueB,
            $player,
            $specs,
            null,
            Booking::PAYMENT_GCASH,
            null,
            null,
            null,
            null,
            null,
            null,
            true,
        );

        $balanceAfter = (int) (UserVenueCredit::query()
            ->where('user_id', $player->id)
            ->where('currency', 'PHP')
            ->value('balance_cents') ?? 0);
        $this->assertSame(10_000 - $checkoutSlice, $balanceAfter);

        Carbon::setTestNow();
    }

    public function test_member_reschedule_request_accepted_by_desk_updates_booking_times(): void
    {
        $client = CourtClient::factory()->create();
        $desk = User::factory()->courtClientDesk($client)->create();

        $player = User::factory()->player()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court B',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::parse('2026-09-01 14:00:00', config('app.timezone'));
        $booking = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHours(2),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1_000,
            'currency' => 'PHP',
        ]);

        $tz = config('app.timezone', 'UTC');
        $newStart = Carbon::parse('2026-09-15 09:00:00', $tz);
        $newEnd = $newStart->copy()->addHours(2);

        Livewire::actingAs($player)
            ->test(MemberBookingShow::class, ['booking' => $booking->fresh()])
            ->set('rescheduleDate', '2026-09-15')
            ->set('rescheduleStartTime', '09:00')
            ->call('submitRescheduleRequest')
            ->assertHasNoErrors();

        $req = BookingChangeRequest::query()->where('booking_id', $booking->id)->first();
        $this->assertNotNull($req);
        $this->assertSame(BookingChangeRequest::TYPE_RESCHEDULE, $req->type);

        Livewire::actingAs($desk)
            ->test(DeskBookingChangeRequests::class)
            ->call('openAccept', $req->id)
            ->call('confirmAccept');

        $booking->refresh();
        $this->assertTrue($booking->starts_at->equalTo($newStart));
        $this->assertTrue($booking->ends_at->equalTo($newEnd));
    }

    public function test_reschedule_submit_fails_when_court_is_busy(): void
    {
        $client = CourtClient::factory()->create();
        $player = User::factory()->player()->create();
        $other = User::factory()->player()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court C',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::parse('2026-10-01 10:00:00', config('app.timezone'));
        $mine = Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1_000,
            'currency' => 'PHP',
        ]);

        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $other->id,
            'starts_at' => $starts->copy()->addWeek(),
            'ends_at' => $starts->copy()->addWeek()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1_000,
            'currency' => 'PHP',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        BookingChangeRequestService::submitReschedule(
            $mine,
            $player,
            $starts->copy()->addWeek(),
            $starts->copy()->addWeek()->addHour(),
            null,
        );
    }
}
