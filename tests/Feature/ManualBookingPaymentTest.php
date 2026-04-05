<?php

namespace Tests\Feature;

use App\Livewire\Admin\CourtClientManualBooking;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManualBookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_booking_requires_payment_reference(): void
    {
        $this->seed(UserTypeSeeder::class);
        Storage::fake('public');

        $super = User::factory()->superAdmin()->create();
        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);

        $bookingDate = Carbon::now(config('app.timezone', 'UTC'))->addDays(10)->format('Y-m-d');

        Livewire::actingAs($super)
            ->test(CourtClientManualBooking::class, ['courtClient' => $client])
            ->set('bookingCalendarDate', $bookingDate)
            ->set('selectedManualSlots', [$court->id.'-10'])
            ->set('manualBookingUserId', $player->id)
            ->set('manualBookingPaymentReference', '')
            ->call('saveManualBooking')
            ->assertHasErrors(['manualBookingPaymentReference']);
    }

    public function test_manual_booking_stores_gcash_reference_and_proof(): void
    {
        $this->seed(UserTypeSeeder::class);
        Storage::fake('public');

        $super = User::factory()->superAdmin()->create();
        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);

        $bookingDate = Carbon::now(config('app.timezone', 'UTC'))->addDays(10)->format('Y-m-d');
        $proof = UploadedFile::fake()->image('gcash.jpg', 400, 800);

        Livewire::actingAs($super)
            ->test(CourtClientManualBooking::class, ['courtClient' => $client])
            ->set('bookingCalendarDate', $bookingDate)
            ->set('selectedManualSlots', [$court->id.'-10'])
            ->set('manualBookingUserId', $player->id)
            ->set('manualBookingPaymentMethod', Booking::PAYMENT_GCASH)
            ->set('manualBookingPaymentReference', 'GCASH-REF-12345')
            ->set('manualBookingPaymentProof', $proof)
            ->call('saveManualBooking')
            ->assertHasNoErrors();

        $booking = Booking::query()->where('user_id', $player->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(Booking::PAYMENT_GCASH, $booking->payment_method);
        $this->assertSame('GCASH-REF-12345', $booking->payment_reference);
        $this->assertNotNull($booking->payment_proof_path);
        Storage::disk('public')->assertExists($booking->payment_proof_path);
    }

    public function test_manual_booking_grid_shows_booked_guest_and_skips_selection(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);

        $tz = config('app.timezone', 'UTC');
        $bookingDate = Carbon::now($tz)->addDays(10)->format('Y-m-d');
        $starts = Carbon::parse($bookingDate.' 10:00:00', $tz);
        $ends = $starts->copy()->addHour();

        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($super)
            ->test(CourtClientManualBooking::class, ['courtClient' => $client])
            ->set('bookingCalendarDate', $bookingDate)
            ->assertSee('Booked')
            ->assertSee($player->name)
            ->assertSee('Confirmed')
            ->call('toggleManualSlot', $court->id, 10)
            ->assertSet('selectedManualSlots', []);
    }
}
