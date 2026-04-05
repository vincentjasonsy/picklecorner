<?php

namespace Tests\Feature;

use App\Livewire\Venue\VenueCourts;
use App\Models\Court;
use App\Models\CourtChangeRequest;
use App\Models\CourtClient;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VenueCourtChangeWithdrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_court_admin_can_withdraw_pending_add_court_request(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $admin = User::factory()->courtAdmin()->create();
        $client->update(['admin_user_id' => $admin->id]);

        $request = CourtChangeRequest::query()->create([
            'court_client_id' => $client->id,
            'requested_by_user_id' => $admin->id,
            'action' => CourtChangeRequest::ACTION_ADD_COURT,
            'environment' => Court::ENV_OUTDOOR,
            'court_id' => null,
            'status' => CourtChangeRequest::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(VenueCourts::class)
            ->call('withdrawPendingRequest', $request->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('court_change_requests', ['id' => $request->id]);
    }
}
