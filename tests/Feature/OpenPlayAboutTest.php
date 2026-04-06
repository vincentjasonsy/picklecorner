<?php

namespace Tests\Feature;

use App\Models\OpenPlaySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenPlayAboutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_open_play_about_page(): void
    {
        $this->get(route('open-play.about'))
            ->assertOk()
            ->assertSee('PickleGameQ', false)
            ->assertSee('Log in to use PickleGameQ', false)
            ->assertSee('Your hosted sessions', false);
    }

    public function test_authenticated_user_sees_saved_sessions_list(): void
    {
        $user = User::factory()->create();
        OpenPlaySession::query()->create([
            'user_id' => $user->getKey(),
            'title' => 'Friday ladder',
            'payload' => ['mode' => 'singles', 'players' => []],
        ]);

        $this->actingAs($user)
            ->get(route('open-play.about'))
            ->assertOk()
            ->assertSee('Friday ladder', false)
            ->assertSee('Open in PickleGameQ', false)
            ->assertSee('of '.OpenPlaySession::MONTHLY_SAVE_LIMIT, false);
    }
}
