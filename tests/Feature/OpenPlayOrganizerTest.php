<?php

namespace Tests\Feature;

use App\GameQ\Engine;
use App\Livewire\OpenPlayOrganizer;
use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
            ->assertSee('Open a saved session', false);
    }

    public function test_start_sharing_creates_open_play_share_with_user_id(): void
    {
        $user = User::factory()->create();
        $state = (new Engine([]))->toArray();
        $state['uiPhase'] = 'session';

        $this->actingAs($user);
        session(['gameq_organizer_v2' => $state]);

        $lw = Livewire::test(OpenPlayOrganizer::class)
            ->call('startSharing')
            ->assertSet('state.shareError', '');

        $stateAfter = $lw->get('state');
        $this->assertNotSame('', (string) ($stateAfter['shareUuid'] ?? ''), 'shareUuid should be set');
        $this->assertNotSame('', (string) ($stateAfter['shareSecret'] ?? ''), 'shareSecret should be set');

        $this->assertSame(1, OpenPlayShare::query()->count());
        $this->assertDatabaseHas('open_play_shares', [
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_auto_save_creates_open_play_session_when_hosting_with_roster(): void
    {
        $user = User::factory()->create();
        $state = (new Engine([]))->toArray();
        $state['uiPhase'] = 'session';
        $state['linkedOpenPlaySessionId'] = null;
        $state['sessionTitle'] = 'Club night';
        $state['players'] = [
            [
                'id' => 'p-test-1',
                'name' => 'Alex',
                'level' => 3,
                'wins' => 0,
                'losses' => 0,
                'disabled' => false,
                'skipShuffle' => false,
                'teamId' => '',
            ],
        ];

        $this->actingAs($user);
        session(['gameq_organizer_v2' => $state]);

        Livewire::test(OpenPlayOrganizer::class)
            ->call('refreshTimers')
            ->call('refreshTimers');

        $this->assertSame(1, OpenPlaySession::query()->where('user_id', $user->getKey())->count());
        $row = OpenPlaySession::query()->where('user_id', $user->getKey())->first();
        $this->assertNotNull($row);
        $this->assertSame('Club night', $row->title);
        $this->assertIsArray($row->payload);
        $this->assertCount(1, $row->payload['players'] ?? []);
    }

    public function test_auto_save_updates_linked_session_without_second_create(): void
    {
        $user = User::factory()->create();
        $state = (new Engine([]))->toArray();
        $state['uiPhase'] = 'session';
        $state['linkedOpenPlaySessionId'] = null;
        $state['sessionTitle'] = 'First';
        $state['players'] = [
            [
                'id' => 'p-test-1',
                'name' => 'Alex',
                'level' => 3,
                'wins' => 0,
                'losses' => 0,
                'disabled' => false,
                'skipShuffle' => false,
                'teamId' => '',
            ],
        ];

        $this->actingAs($user);
        session(['gameq_organizer_v2' => $state]);

        $lw = Livewire::test(OpenPlayOrganizer::class)->call('refreshTimers');
        $sid = (int) $lw->get('state')['linkedOpenPlaySessionId'];
        $this->assertGreaterThan(0, $sid);

        $lw->set('state.sessionTitle', 'Renamed')
            ->call('refreshTimers');

        $this->assertSame(1, OpenPlaySession::query()->where('user_id', $user->getKey())->count());
        $this->assertSame('Renamed', OpenPlaySession::query()->find($sid)?->title);
    }

    public function test_player_head_to_head_page_loads_for_rostered_player(): void
    {
        $user = User::factory()->create();
        $state = (new Engine([]))->toArray();
        $state['uiPhase'] = 'session';
        $state['sessionTitle'] = 'Test night';
        $state['players'] = [
            [
                'id' => 'p-h2h-page',
                'name' => 'Jamie',
                'level' => 3,
                'wins' => 0,
                'losses' => 0,
                'disabled' => false,
                'skipShuffle' => false,
                'teamId' => '',
            ],
        ];

        $this->actingAs($user);
        session(['gameq_organizer_v2' => $state]);

        $this->get(route('account.open-play.player', ['playerId' => 'p-h2h-page']))
            ->assertOk()
            ->assertSee('Jamie', false)
            ->assertSee('Test night', false)
            ->assertSee('Head-to-head', false);
    }
}
