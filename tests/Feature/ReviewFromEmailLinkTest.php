<?php

namespace Tests\Feature;

use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserReview;
use App\Support\UserReviewMailLink;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ReviewFromEmailLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_with_intended_url(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $venue = CourtClient::factory()->create();

        $url = UserReviewMailLink::signedUrl(
            $player,
            UserReview::TARGET_VENUE,
            (string) $venue->id,
        );

        $response = $this->get($url);

        $response->assertRedirect(route('login'));
        $this->assertSame($url, session('url.intended'));
    }

    public function test_authenticated_member_matching_user_can_view_review_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $venue = CourtClient::factory()->create();

        $url = UserReviewMailLink::signedUrl(
            $player,
            UserReview::TARGET_VENUE,
            (string) $venue->id,
        );

        $this->actingAs($player)
            ->get($url)
            ->assertOk()
            ->assertSee('Venue reviews', escape: false);
    }

    public function test_wrong_user_cannot_use_another_members_link(): void
    {
        $this->seed(UserTypeSeeder::class);

        $owner = User::factory()->player()->create();
        $other = User::factory()->player()->create();
        $venue = CourtClient::factory()->create();

        $url = UserReviewMailLink::signedUrl(
            $owner,
            UserReview::TARGET_VENUE,
            (string) $venue->id,
        );

        $this->actingAs($other)
            ->get($url)
            ->assertForbidden();
    }

    public function test_tampered_link_returns_forbidden(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $venue = CourtClient::factory()->create();

        $url = UserReviewMailLink::signedUrl(
            $player,
            UserReview::TARGET_VENUE,
            (string) $venue->id,
        );

        $badUrl = $url.'x';

        $this->actingAs($player)
            ->get($badUrl)
            ->assertForbidden();
    }

    public function test_expired_signed_link_returns_forbidden(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $venue = CourtClient::factory()->create();

        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00', config('app.timezone', 'UTC')));
        try {
            $url = URL::temporarySignedRoute(
                'reviews.write-signed',
                now()->addHour(),
                [
                    'user' => $player->getKey(),
                    'target_type' => UserReview::TARGET_VENUE,
                    'target_id' => (string) $venue->id,
                ],
            );

            Carbon::setTestNow(Carbon::parse('2026-09-01 12:00:00', config('app.timezone', 'UTC')));

            $this->actingAs($player)
                ->get($url)
                ->assertForbidden();
        } finally {
            Carbon::setTestNow();
        }
    }
}
