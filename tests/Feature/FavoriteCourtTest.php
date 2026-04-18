<?php

namespace Tests\Feature;

use App\Livewire\PublicCourtShow;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FavoriteCourtTest extends TestCase
{
    use RefreshDatabase;

    protected function createBrowseableCourt(): Court
    {
        $client = CourtClient::factory()->create([
            'is_active' => true,
            'city' => 'Testville',
        ]);

        return Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court Alpha',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
    }

    public function test_member_can_toggle_favorite_on_public_court_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $court = $this->createBrowseableCourt();
        $player = User::factory()->player()->create();

        Livewire::actingAs($player)
            ->test(PublicCourtShow::class, ['court' => $court])
            ->assertSet('courtIsFavorite', false)
            ->call('toggleFavorite')
            ->assertSet('courtIsFavorite', true);

        $this->assertDatabaseHas('favorite_courts', [
            'user_id' => $player->id,
            'court_id' => $court->id,
        ]);

        Livewire::actingAs($player)
            ->test(PublicCourtShow::class, ['court' => $court])
            ->assertSet('courtIsFavorite', true)
            ->call('toggleFavorite')
            ->assertSet('courtIsFavorite', false);

        $this->assertDatabaseMissing('favorite_courts', [
            'user_id' => $player->id,
            'court_id' => $court->id,
        ]);
    }

    public function test_guest_toggle_favorite_redirects_to_login_with_intended_url(): void
    {
        $this->seed(UserTypeSeeder::class);

        $court = $this->createBrowseableCourt();

        Livewire::test(PublicCourtShow::class, ['court' => $court])
            ->call('toggleFavorite')
            ->assertRedirect(route('login'));

        $this->assertSame(
            route('book-now.court', $court),
            session('url.intended')
        );
    }
}
