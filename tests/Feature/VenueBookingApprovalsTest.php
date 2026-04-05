<?php

namespace Tests\Feature;

use App\Livewire\Venue\VenueBookingApprovals;
use App\Models\CourtClient;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
