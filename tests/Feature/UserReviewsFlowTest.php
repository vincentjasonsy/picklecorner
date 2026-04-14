<?php

namespace Tests\Feature;

use App\Livewire\Admin\ReviewApprovals;
use App\Livewire\Reviews\UserReviewsPanel;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserReview;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserReviewsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_player_can_submit_pending_venue_review(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-06-15 15:00:00', $tz));

        $venue = CourtClient::factory()->create([
            'public_rating_average' => null,
            'public_rating_count' => 0,
        ]);
        $court = Court::query()->create([
            'court_client_id' => $venue->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $player = User::factory()->player()->create();

        Booking::query()->create([
            'court_client_id' => $venue->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => Carbon::parse('2026-06-15 10:00:00', $tz),
            'ends_at' => Carbon::parse('2026-06-15 12:00:00', $tz),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(UserReviewsPanel::class, [
                'targetType' => UserReview::TARGET_VENUE,
                'targetId' => $venue->id,
            ])
            ->set('rating', 4)
            ->set('body', 'Nice courts.')
            ->call('submitReview')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_reviews', [
            'user_id' => $player->id,
            'target_type' => UserReview::TARGET_VENUE,
            'target_id' => $venue->id,
            'rating' => 4,
            'status' => UserReview::STATUS_PENDING,
            'profanity_flag' => false,
        ]);
    }

    public function test_profanity_flag_is_set_when_body_matches_blocklist(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-06-15 15:00:00', $tz));

        $venue = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $venue->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $player = User::factory()->player()->create();

        Booking::query()->create([
            'court_client_id' => $venue->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => Carbon::parse('2026-06-15 10:00:00', $tz),
            'ends_at' => Carbon::parse('2026-06-15 12:00:00', $tz),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(UserReviewsPanel::class, [
                'targetType' => UserReview::TARGET_VENUE,
                'targetId' => $venue->id,
            ])
            ->set('rating', 3)
            ->set('body', 'This place is shit.')
            ->call('submitReview')
            ->assertHasNoErrors();

        $this->assertTrue(
            (bool) UserReview::query()
                ->where('user_id', $player->id)
                ->where('target_id', $venue->id)
                ->value('profanity_flag')
        );
    }

    public function test_super_admin_approve_syncs_venue_public_rating(): void
    {
        $this->seed(UserTypeSeeder::class);

        $venue = CourtClient::factory()->create([
            'public_rating_average' => null,
            'public_rating_count' => 0,
        ]);
        $player = User::factory()->player()->create();
        $review = UserReview::query()->create([
            'user_id' => $player->id,
            'target_type' => UserReview::TARGET_VENUE,
            'target_id' => $venue->id,
            'rating' => 5,
            'body' => 'Superb',
            'status' => UserReview::STATUS_PENDING,
            'profanity_flag' => false,
        ]);
        $super = User::factory()->superAdmin()->create();

        Livewire::actingAs($super)
            ->test(ReviewApprovals::class)
            ->call('approve', $review->id)
            ->assertHasNoErrors();

        $venue->refresh();
        $this->assertEquals(5.0, (float) $venue->public_rating_average);
        $this->assertSame(1, (int) $venue->public_rating_count);

        $review->refresh();
        $this->assertSame(UserReview::STATUS_APPROVED, $review->status);
        $this->assertEquals($super->id, $review->moderated_by_user_id);
    }

    public function test_super_admin_approve_syncs_coach_profile_public_rating(): void
    {
        $this->seed(UserTypeSeeder::class);

        $coach = User::factory()->coach()->create();
        $player = User::factory()->player()->create();
        $review = UserReview::query()->create([
            'user_id' => $player->id,
            'target_type' => UserReview::TARGET_COACH,
            'target_id' => $coach->id,
            'rating' => 4,
            'body' => null,
            'status' => UserReview::STATUS_PENDING,
            'profanity_flag' => false,
        ]);
        $super = User::factory()->superAdmin()->create();

        Livewire::actingAs($super)
            ->test(ReviewApprovals::class)
            ->call('approve', $review->id)
            ->assertHasNoErrors();

        $profile = $coach->fresh()->coachProfile;
        $this->assertNotNull($profile);
        $this->assertEquals(4.0, (float) $profile->public_rating_average);
        $this->assertSame(1, $profile->public_rating_count);
    }

    public function test_player_cannot_submit_venue_review_without_booking(): void
    {
        $this->seed(UserTypeSeeder::class);

        $venue = CourtClient::factory()->create();
        $player = User::factory()->player()->create();

        Livewire::actingAs($player)
            ->test(UserReviewsPanel::class, [
                'targetType' => UserReview::TARGET_VENUE,
                'targetId' => $venue->id,
            ])
            ->set('rating', 4)
            ->set('body', 'Nice.')
            ->call('submitReview')
            ->assertHasErrors('review');

        $this->assertSame(0, UserReview::query()->where('user_id', $player->id)->count());
    }

    public function test_player_cannot_submit_venue_review_before_booking_ends(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-06-15 11:00:00', $tz));

        $venue = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $venue->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $player = User::factory()->player()->create();

        Booking::query()->create([
            'court_client_id' => $venue->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => Carbon::parse('2026-06-15 10:00:00', $tz),
            'ends_at' => Carbon::parse('2026-06-15 12:00:00', $tz),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(UserReviewsPanel::class, [
                'targetType' => UserReview::TARGET_VENUE,
                'targetId' => $venue->id,
            ])
            ->set('rating', 4)
            ->set('body', 'Soon.')
            ->call('submitReview')
            ->assertHasErrors('review');
    }

    public function test_player_can_submit_pending_coach_review_when_booking_includes_coach(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-07-01 18:00:00', $tz));

        $venue = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $venue->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $coach = User::factory()->coach()->create();
        $player = User::factory()->player()->create();

        Booking::query()->create([
            'court_client_id' => $venue->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'coach_user_id' => $coach->id,
            'starts_at' => Carbon::parse('2026-07-01 14:00:00', $tz),
            'ends_at' => Carbon::parse('2026-07-01 16:00:00', $tz),
            'status' => Booking::STATUS_COMPLETED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(UserReviewsPanel::class, [
                'targetType' => UserReview::TARGET_COACH,
                'targetId' => $coach->id,
            ])
            ->set('rating', 5)
            ->set('body', 'Great session.')
            ->call('submitReview')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_reviews', [
            'user_id' => $player->id,
            'target_type' => UserReview::TARGET_COACH,
            'target_id' => $coach->id,
            'status' => UserReview::STATUS_PENDING,
        ]);
    }

    public function test_player_cannot_submit_venue_review_after_window_closes(): void
    {
        $this->seed(UserTypeSeeder::class);

        $tz = config('app.timezone', 'UTC');
        Carbon::setTestNow(Carbon::parse('2026-06-20 12:00:00', $tz));

        $venue = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $venue->id,
            'name' => 'Court A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
        ]);
        $player = User::factory()->player()->create();

        Booking::query()->create([
            'court_client_id' => $venue->id,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => Carbon::parse('2026-06-15 10:00:00', $tz),
            'ends_at' => Carbon::parse('2026-06-15 12:00:00', $tz),
            'status' => Booking::STATUS_CONFIRMED,
            'currency' => 'PHP',
        ]);

        Livewire::actingAs($player)
            ->test(UserReviewsPanel::class, [
                'targetType' => UserReview::TARGET_VENUE,
                'targetId' => $venue->id,
            ])
            ->set('rating', 4)
            ->set('body', 'Late.')
            ->call('submitReview')
            ->assertHasErrors('review');
    }

    public function test_guest_sees_published_venue_reviews_without_signing_in(): void
    {
        $this->seed(UserTypeSeeder::class);

        $venue = CourtClient::factory()->create();
        $author = User::factory()->player()->create(['name' => 'Jordan Public']);
        UserReview::query()->create([
            'user_id' => $author->id,
            'target_type' => UserReview::TARGET_VENUE,
            'target_id' => $venue->id,
            'rating' => 5,
            'body' => 'Excellent lights and surface.',
            'status' => UserReview::STATUS_APPROVED,
            'profanity_flag' => false,
            'moderated_at' => now(),
        ]);

        Livewire::test(UserReviewsPanel::class, [
            'targetType' => UserReview::TARGET_VENUE,
            'targetId' => $venue->id,
        ])
            ->assertSee('Excellent lights and surface.', escape: false)
            ->assertSee('What players say', escape: false)
            ->assertSee('Read what other players say', escape: false)
            ->assertSee('Sign in', escape: false);
    }
}
