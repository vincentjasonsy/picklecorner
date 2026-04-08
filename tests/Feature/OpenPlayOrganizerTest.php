<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenPlayOrganizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_open_play_organizer(): void
    {
        $this->get(route('account.open-play'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_gameq_organizer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.open-play'))
            ->assertOk()
            ->assertSee('wire:click="startOpenPlayWizard"', false)
            ->assertSee('Start GameQ', false)
            ->assertSee('Open a saved session', false)
            ->assertSee('wire:poll.1s="refreshTimers"', false);
    }
}
