<?php

namespace Tests\Feature;

use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GiftCardStaffModuleConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_cannot_open_gift_card_routes_when_module_disabled_for_non_super_admins(): void
    {
        $this->seed(UserTypeSeeder::class);
        config(['booking.gift_card_module_for_non_super_admins' => false]);

        $coach = User::factory()->coach()->create();
        $client = CourtClient::factory()->create();
        $card = GiftCard::query()->create([
            'court_client_id' => $client->id,
            'code' => 'TST'.Str::upper(Str::random(8)),
            'value_type' => GiftCard::VALUE_FIXED,
            'face_value_cents' => 1000_00,
            'balance_cents' => 1000_00,
            'created_by' => $coach->id,
        ]);

        $this->actingAs($coach)->get(route('account.coach.gift-cards.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('account.coach.gift-cards.show', $card))->assertForbidden();
    }

    public function test_venue_admin_cannot_open_gift_card_routes_when_module_disabled_for_non_super_admins(): void
    {
        $this->seed(UserTypeSeeder::class);
        config(['booking.gift_card_module_for_non_super_admins' => false]);

        $admin = User::factory()->courtAdmin()->create();
        CourtClient::factory()->forAdmin($admin)->premiumTier()->create();

        $this->actingAs($admin)->get(route('venue.gift-cards.index'))->assertForbidden();
    }

    public function test_super_admin_still_reaches_admin_gift_cards_when_module_disabled_for_non_super_admins(): void
    {
        $this->seed(UserTypeSeeder::class);
        config(['booking.gift_card_module_for_non_super_admins' => false]);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.gift-cards.index'))->assertOk();
    }
}
