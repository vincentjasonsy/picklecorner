<?php

namespace Tests\Feature;

use App\Livewire\Desk\DeskManualBooking;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeskBookingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeVenueWithCourtForDate(string $bookingDate): array
    {
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);

        return [$client, $court, $bookingDate];
    }

    public function test_desk_manual_review_leaves_booking_pending(): void
    {
        $this->seed(UserTypeSeeder::class);

        $bookingDate = Carbon::now(config('app.timezone', 'UTC'))->addDays(10)->format('Y-m-d');
        [$client, $court] = $this->makeVenueWithCourtForDate($bookingDate);

        $client->update(['desk_booking_policy' => CourtClient::DESK_BOOKING_POLICY_MANUAL]);

        $desk = User::factory()->courtClientDesk($client)->create();
        $player = User::factory()->player()->create();

        Livewire::actingAs($desk)
            ->test(DeskManualBooking::class)
            ->set('bookingCalendarDate', $bookingDate)
            ->set('selectedManualSlots', [$court->id.'-10'])
            ->set('manualBookingUserId', $player->id)
            ->call('saveManualBooking')
            ->assertHasNoErrors();

        $booking = Booking::query()->where('user_id', $player->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(Booking::STATUS_PENDING_APPROVAL, $booking->status);
        $this->assertSame($desk->id, $booking->desk_submitted_by);
    }

    public function test_desk_auto_approve_confirms_booking(): void
    {
        $this->seed(UserTypeSeeder::class);

        $bookingDate = Carbon::now(config('app.timezone', 'UTC'))->addDays(10)->format('Y-m-d');
        [$client, $court] = $this->makeVenueWithCourtForDate($bookingDate);

        $client->update(['desk_booking_policy' => CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE]);

        $desk = User::factory()->courtClientDesk($client)->create();
        $player = User::factory()->player()->create();

        Livewire::actingAs($desk)
            ->test(DeskManualBooking::class)
            ->set('bookingCalendarDate', $bookingDate)
            ->set('selectedManualSlots', [$court->id.'-10'])
            ->set('manualBookingUserId', $player->id)
            ->call('saveManualBooking')
            ->assertHasNoErrors();

        $booking = Booking::query()->where('user_id', $player->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);
    }

    public function test_desk_auto_deny_marks_booking_denied(): void
    {
        $this->seed(UserTypeSeeder::class);

        $bookingDate = Carbon::now(config('app.timezone', 'UTC'))->addDays(10)->format('Y-m-d');
        [$client, $court] = $this->makeVenueWithCourtForDate($bookingDate);

        $client->update(['desk_booking_policy' => CourtClient::DESK_BOOKING_POLICY_AUTO_DENY]);

        $desk = User::factory()->courtClientDesk($client)->create();
        $player = User::factory()->player()->create();

        Livewire::actingAs($desk)
            ->test(DeskManualBooking::class)
            ->set('bookingCalendarDate', $bookingDate)
            ->set('selectedManualSlots', [$court->id.'-10'])
            ->set('manualBookingUserId', $player->id)
            ->set('manualBookingNotes', 'Walk-in')
            ->call('saveManualBooking')
            ->assertHasNoErrors();

        $booking = Booking::query()->where('user_id', $player->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(Booking::STATUS_DENIED, $booking->status);
        $this->assertStringContainsString('Auto-denied by venue desk booking policy.', (string) $booking->notes);
        $this->assertStringContainsString('Walk-in', (string) $booking->notes);
    }
}
