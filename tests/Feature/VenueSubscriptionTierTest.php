<?php

namespace Tests\Feature;

use App\Models\CourtClient;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueSubscriptionTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_tier_redirects_gift_cards_to_plan_page_but_allows_customer_list(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        CourtClient::factory()->forAdmin($admin)->basicTier()->create();

        $this->actingAs($admin)->get(route('venue.crm.index'))->assertOk();
        $this->actingAs($admin)->get(route('venue.gift-cards.index'))->assertRedirect(route('venue.plan'));
    }

    public function test_premium_tier_allows_crm_and_gift_card_routes(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        CourtClient::factory()->forAdmin($admin)->premiumTier()->create();

        $this->actingAs($admin)->get(route('venue.crm.index'))->assertOk();
        $this->actingAs($admin)->get(route('venue.gift-cards.index'))->assertOk();
    }

    public function test_plan_page_is_reachable_for_basic_tier(): void
    {
        $this->seed(UserTypeSeeder::class);

        $admin = User::factory()->courtAdmin()->create();
        CourtClient::factory()->forAdmin($admin)->basicTier()->create();

        $this->actingAs($admin)->get(route('venue.plan'))->assertOk();
    }
}
