<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\CourtClient;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookNowBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_book_now_and_court_detail(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create([
            'is_active' => true,
            'city' => 'Testville',
            'public_rating_average' => 4.5,
            'public_rating_count' => 40,
        ]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court Alpha',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $this->get(route('book-now'))->assertOk()->assertSee('Book now', false)->assertSee('Court Alpha');

        $this->get(route('book-now.court', $court))->assertOk()->assertSee('Court Alpha');

        $this->get(route('book-now'))->assertOk()->assertSee('Recently viewed', false);
    }

    public function test_logged_in_user_can_open_book_now(): void
    {
        $this->seed(UserTypeSeeder::class);

        $user = \App\Models\User::factory()->player()->create();

        $this->actingAs($user)->get(route('book-now'))->assertOk();
    }

    public function test_inactive_venue_court_not_listed_and_detail_404(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create(['is_active' => false]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Hidden',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $this->get(route('book-now'))->assertOk()->assertDontSee('Hidden');
        $this->get(route('book-now.court', $court))->assertNotFound();
    }
}
