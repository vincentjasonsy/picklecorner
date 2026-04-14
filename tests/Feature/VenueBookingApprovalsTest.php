<?php

namespace Tests\Feature;

use App\Livewire\Venue\VenueBookingApprovals;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VenueBookingApprovalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_court_admin_sees_manual_review_banner_when_policy_manual(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create([
            'desk_booking_policy' => CourtClient::DESK_BOOKING_POLICY_MANUAL,
        ]);
        $admin = User::factory()->courtAdmin()->create();
        $client->update(['admin_user_id' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(VenueBookingApprovals::class)
            ->assertSee('Manual review')
            ->assertSee('Desk staff submit manual booking requests');
    }

    public function test_court_admin_sees_auto_confirm_banner_when_policy_auto_approve(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create([
            'desk_booking_policy' => CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE,
        ]);
        $admin = User::factory()->courtAdmin()->create();
        $client->update(['admin_user_id' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(VenueBookingApprovals::class)
            ->assertSee('Auto-confirm')
            ->assertSee('confirmed automatically');
    }

    public function test_approving_one_pending_line_confirms_all_rows_in_same_booking_request(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create([
            'desk_booking_policy' => CourtClient::DESK_BOOKING_POLICY_MANUAL,
        ]);
        $admin = User::factory()->courtAdmin()->create();
        $client->update(['admin_user_id' => $admin->id]);
        $player = User::factory()->player()->create();

        $courtA = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $courtB = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court B',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $requestId = (string) Str::uuid();
        $tz = config('app.timezone');
        $starts = Carbon::parse('2026-06-10 09:00:00', $tz);

        $b1 = Booking::query()->create([
            'court_client_id' => $client->id,
            'booking_request_id' => $requestId,
            'court_id' => $courtA->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_PENDING_APPROVAL,
            'amount_cents' => 5_000,
            'currency' => 'PHP',
        ]);
        $b2 = Booking::query()->create([
            'court_client_id' => $client->id,
            'booking_request_id' => $requestId,
            'court_id' => $courtB->id,
            'user_id' => $player->id,
            'starts_at' => $starts->copy()->addHours(2),
            'ends_at' => $starts->copy()->addHours(3),
            'status' => Booking::STATUS_PENDING_APPROVAL,
            'amount_cents' => 5_000,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($admin)
            ->test(VenueBookingApprovals::class)
            ->assertSee('One request · 2 courts')
            ->assertSee('Court A')
            ->assertSee('Court B')
            ->call('approve', $b1->id);

        $this->assertSame(Booking::STATUS_CONFIRMED, $b1->fresh()->status);
        $this->assertSame(Booking::STATUS_CONFIRMED, $b2->fresh()->status);
    }
}
