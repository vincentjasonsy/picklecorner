<?php

namespace Tests\Feature;

use App\Livewire\Admin\FeaturedVenuesManage;
use App\Livewire\BookNowPage;
use App\Models\CityFeaturedCourtClient;
use App\Models\CourtClient;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeaturedVenuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_save_between_five_and_ten_featured_venues(): void
    {
        $this->seed(UserTypeSeeder::class);

        $city = 'FeatCity';
        $venues = collect(range(1, 6))->map(fn (int $n) => CourtClient::factory()->create([
            'is_active' => true,
            'city' => $city,
            'name' => "Venue {$n}",
        ]));

        $super = User::factory()->superAdmin()->create();

        Livewire::actingAs($super)
            ->test(FeaturedVenuesManage::class)
            ->set('selectedCity', $city)
            ->set('orderedIds', $venues->take(5)->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(5, CityFeaturedCourtClient::query()->where('city', $city)->count());
    }

    public function test_super_admin_cannot_save_four_featured_venues(): void
    {
        $this->seed(UserTypeSeeder::class);

        $city = 'FeatCity';
        $venues = collect(range(1, 5))->map(fn (int $n) => CourtClient::factory()->create([
            'is_active' => true,
            'city' => $city,
            'name' => "Venue {$n}",
        ]));

        $super = User::factory()->superAdmin()->create();

        Livewire::actingAs($super)
            ->test(FeaturedVenuesManage::class)
            ->set('selectedCity', $city)
            ->set('orderedIds', $venues->take(4)->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->call('save')
            ->assertHasErrors(['orderedIds']);
    }

    public function test_book_now_shows_featured_strip_for_member_home_city(): void
    {
        $this->seed(UserTypeSeeder::class);

        $city = 'FeatCity';
        $names = ['Alpha Featured', 'Beta Featured', 'Gamma Featured', 'Delta Featured', 'Epsilon Featured'];
        $ids = [];
        foreach ($names as $name) {
            $ids[] = CourtClient::factory()->create([
                'is_active' => true,
                'city' => $city,
                'name' => $name,
            ])->id;
        }

        foreach ($ids as $i => $courtClientId) {
            CityFeaturedCourtClient::query()->create([
                'city' => $city,
                'court_client_id' => $courtClientId,
                'sort_order' => $i,
            ]);
        }

        $user = User::factory()->player()->create(['home_city' => $city]);

        $html = Livewire::actingAs($user)
            ->test(BookNowPage::class)
            ->html();

        $this->assertStringContainsString('Featured', $html);
        $this->assertStringContainsString('Alpha Featured', $html);
    }

    public function test_player_cannot_open_featured_venues_admin(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('admin.featured-venues'))->assertForbidden();
    }
}
