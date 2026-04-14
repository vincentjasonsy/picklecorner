<?php

namespace Tests\Feature;

use App\Livewire\BookNowPage;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        $this->get(route('book-now.court', $court))
            ->assertOk()
            ->assertSee('Court Alpha')
            ->assertSee('Proceed to book', false);

        $this->get(route('book-now'))->assertOk()->assertSee('Recently viewed', false);
    }

    public function test_logged_in_user_can_open_book_now(): void
    {
        $this->seed(UserTypeSeeder::class);

        $user = User::factory()->player()->create();

        $this->actingAs($user)->get(route('book-now'))->assertOk();
    }

    public function test_member_account_book_uses_same_browse_ui(): void
    {
        $this->seed(UserTypeSeeder::class);

        $user = User::factory()->player()->create();

        $this->actingAs($user)->get(route('account.book'))->assertOk()->assertSee('All venues', false);
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

    public function test_book_now_prioritizes_venues_matching_member_home_city(): void
    {
        $this->seed(UserTypeSeeder::class);

        $alphaCity = 'AlphaCity';
        $betaCity = 'BetaCity';

        $clientAlpha = CourtClient::factory()->create([
            'is_active' => true,
            'name' => 'A Club',
            'city' => $alphaCity,
            'public_rating_average' => 5.0,
            'public_rating_count' => 200,
        ]);
        $clientBeta = CourtClient::factory()->create([
            'is_active' => true,
            'name' => 'B Club',
            'city' => $betaCity,
            'public_rating_average' => 3.0,
            'public_rating_count' => 10,
        ]);

        foreach ([$clientAlpha, $clientBeta] as $client) {
            Court::query()->create([
                'court_client_id' => $client->id,
                'name' => 'Court '.$client->name,
                'sort_order' => 0,
                'environment' => Court::ENV_OUTDOOR,
                'is_available' => true,
            ]);
        }

        $user = User::factory()->player()->create(['home_city' => $betaCity]);

        $component = Livewire::actingAs($user)->test(BookNowPage::class);
        $rows = $component->instance()->browseVenueRows();

        $this->assertSame(2, $rows->count());
        $this->assertSame($betaCity, $rows->first()['venue']->city);
        $this->assertSame($betaCity, session('book_now_nearby_city'));
    }

    public function test_book_now_infers_preferred_city_from_recent_bookings(): void
    {
        $this->seed(UserTypeSeeder::class);

        $alphaCity = 'AlphaCity';
        $betaCity = 'BetaCity';

        $clientAlpha = CourtClient::factory()->create([
            'is_active' => true,
            'city' => $alphaCity,
        ]);
        $clientBeta = CourtClient::factory()->create([
            'is_active' => true,
            'city' => $betaCity,
        ]);

        $courtBeta = Court::query()->create([
            'court_client_id' => $clientBeta->id,
            'name' => 'Beta Court',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        Court::query()->create([
            'court_client_id' => $clientAlpha->id,
            'name' => 'Alpha Court',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $user = User::factory()->player()->create(['home_city' => null]);

        $starts = Carbon::parse('2026-05-01 10:00:00', config('app.timezone'));
        Booking::query()->create([
            'court_client_id' => $clientBeta->id,
            'court_id' => $courtBeta->id,
            'user_id' => $user->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 10_000,
            'currency' => 'PHP',
        ]);

        $component = Livewire::actingAs($user)->test(BookNowPage::class);

        $this->assertSame($betaCity, $component->instance()->userPreferredCity());
        $this->assertSame($betaCity, $component->instance()->browseVenueRows()->first()['venue']->city);
    }

    public function test_book_now_orders_same_area_venues_by_guest_rating(): void
    {
        $this->seed(UserTypeSeeder::class);

        $city = 'SharedCity';

        $lowerRated = CourtClient::factory()->create([
            'is_active' => true,
            'name' => 'Zebra Club',
            'city' => $city,
            'public_rating_average' => 3.2,
            'public_rating_count' => 5,
        ]);
        $higherRated = CourtClient::factory()->create([
            'is_active' => true,
            'name' => 'Acme Club',
            'city' => $city,
            'public_rating_average' => 4.9,
            'public_rating_count' => 80,
        ]);

        foreach ([$lowerRated, $higherRated] as $client) {
            Court::query()->create([
                'court_client_id' => $client->id,
                'name' => 'Court '.$client->name,
                'sort_order' => 0,
                'environment' => Court::ENV_OUTDOOR,
                'is_available' => true,
            ]);
        }

        $user = User::factory()->player()->create(['home_city' => $city]);

        $rows = Livewire::actingAs($user)->test(BookNowPage::class)->instance()->browseVenueRows();

        $this->assertSame(2, $rows->count());
        $this->assertSame($higherRated->id, $rows->first()['venue']->id);
        $this->assertSame($lowerRated->id, $rows->last()['venue']->id);
    }
}
