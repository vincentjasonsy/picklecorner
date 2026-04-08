<?php

namespace Tests\Feature;

use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpenPlaySessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function samplePayload(): array
    {
        return [
            'mode' => 'singles',
            'shuffleMethod' => 'random',
            'courtsCount' => 2,
            'timeLimitMinutes' => 0,
            'players' => [['id' => 'a', 'name' => 'Alex', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'teamId' => '']],
            'queue' => [],
            'courts' => [null, null],
            'completedMatches' => [],
            'h2h' => [],
        ];
    }

    public function test_guest_cannot_access_sessions_api(): void
    {
        $this->getJson(route('account.open-play.sessions.index'))
            ->assertUnauthorized();
    }

    public function test_user_can_list_store_show_and_delete_sessions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.index'))
            ->assertOk()
            ->assertJsonPath('sessions', [])
            ->assertJsonPath('quota.limit', OpenPlaySession::MONTHLY_SAVE_LIMIT)
            ->assertJsonPath('quota.used', 0)
            ->assertJsonPath('quota.remaining', OpenPlaySession::MONTHLY_SAVE_LIMIT);

        $create = $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
            'title' => 'Evening hit',
            'data' => $this->samplePayload(),
        ]);

        $create->assertCreated()
            ->assertJsonPath('session.title', 'Evening hit');

        $id = $create->json('session.id');
        $this->assertNotNull($id);

        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.index'))
            ->assertOk()
            ->assertJsonCount(1, 'sessions')
            ->assertJsonPath('sessions.0.title', 'Evening hit');

        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.show', $id))
            ->assertOk()
            ->assertJsonPath('session.payload.players.0.name', 'Alex');

        $this->actingAs($user)
            ->deleteJson(route('account.open-play.sessions.destroy', $id))
            ->assertOk();

        $this->assertDatabaseCount('open_play_sessions', 0);
    }

    public function test_user_cannot_view_another_users_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $session = OpenPlaySession::query()->create([
            'user_id' => $owner->getKey(),
            'title' => 'Private',
            'payload' => $this->samplePayload(),
        ]);

        $this->actingAs($other)
            ->getJson(route('account.open-play.sessions.show', $session))
            ->assertForbidden();
    }

    public function test_user_can_rename_session(): void
    {
        $user = User::factory()->create();
        $session = OpenPlaySession::query()->create([
            'user_id' => $user->getKey(),
            'title' => 'Old',
            'payload' => $this->samplePayload(),
        ]);

        $this->actingAs($user)
            ->patchJson(route('account.open-play.sessions.update', $session), [
                'title' => 'New label',
            ])
            ->assertOk()
            ->assertJsonPath('session.title', 'New label');
    }

    public function test_user_cannot_create_more_than_five_sessions_per_calendar_month(): void
    {
        $this->travelTo(Carbon::parse('2026-08-12 10:00:00', config('app.timezone')));

        $user = User::factory()->create();

        for ($i = 0; $i < OpenPlaySession::MONTHLY_SAVE_LIMIT; $i++) {
            $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
                'title' => "Session {$i}",
                'data' => $this->samplePayload(),
            ])->assertCreated();
        }

        $this->actingAs($user)
            ->postJson(route('account.open-play.sessions.store'), [
                'title' => 'Too many',
                'data' => $this->samplePayload(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('quota.remaining', 0);

        $this->assertDatabaseCount('open_play_sessions', OpenPlaySession::MONTHLY_SAVE_LIMIT);
    }

    public function test_monthly_save_limit_resets_at_start_of_next_month(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 12:00:00', config('app.timezone')));

        $user = User::factory()->create();

        for ($i = 0; $i < OpenPlaySession::MONTHLY_SAVE_LIMIT; $i++) {
            $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
                'data' => $this->samplePayload(),
            ])->assertCreated();
        }

        $this->actingAs($user)
            ->postJson(route('account.open-play.sessions.store'), [
                'data' => $this->samplePayload(),
            ])
            ->assertUnprocessable();

        $this->travelTo(Carbon::parse('2026-10-01 08:00:00', config('app.timezone')));

        $this->actingAs($user)
            ->postJson(route('account.open-play.sessions.store'), [
                'title' => 'October first',
                'data' => $this->samplePayload(),
            ])
            ->assertCreated();

        $this->assertDatabaseCount('open_play_sessions', OpenPlaySession::MONTHLY_SAVE_LIMIT + 1);
    }

    public function test_index_filters_by_hosted_month(): void
    {
        $this->travelTo(Carbon::parse('2026-04-15 12:00:00', config('app.timezone')));
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
            'title' => 'April',
            'data' => $this->samplePayload(),
        ])->assertCreated();

        $this->travelTo(Carbon::parse('2026-05-10 12:00:00', config('app.timezone')));
        $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
            'title' => 'May',
            'data' => $this->samplePayload(),
        ])->assertCreated();

        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.index', ['month' => '2026-04']))
            ->assertOk()
            ->assertJsonCount(1, 'sessions')
            ->assertJsonPath('sessions.0.title', 'April');

        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.index', ['month' => '2026-05']))
            ->assertOk()
            ->assertJsonCount(1, 'sessions')
            ->assertJsonPath('sessions.0.title', 'May');
    }

    public function test_index_search_matches_player_name_in_payload(): void
    {
        $user = User::factory()->create();
        $payload = $this->samplePayload();
        $payload['players'][0]['name'] = 'Jordan Lee';
        $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
            'title' => 'Night hit',
            'data' => $payload,
        ])->assertCreated();

        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.index', ['q' => 'Jordan']))
            ->assertOk()
            ->assertJsonCount(1, 'sessions');

        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.index', ['q' => 'nobody']))
            ->assertOk()
            ->assertJsonCount(0, 'sessions');
    }

    public function test_store_generates_title_when_blank(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
            'title' => '',
            'data' => $this->samplePayload(),
        ]);
        $response->assertCreated();
        $this->assertStringStartsWith('Hosted ·', (string) $response->json('session.title'));
    }

    public function test_index_rejects_invalid_month_format(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->getJson(route('account.open-play.sessions.index', ['month' => '2026-ab']))
            ->assertUnprocessable();
    }

    public function test_store_rejects_payload_with_more_than_max_players(): void
    {
        $user = User::factory()->create();
        $players = [];
        for ($i = 0; $i < OpenPlaySession::MAX_PLAYERS_PER_SESSION + 1; $i++) {
            $players[] = [
                'id' => 'p'.$i,
                'name' => 'Player '.$i,
                'level' => 3,
                'wins' => 0,
                'losses' => 0,
                'disabled' => false,
                'teamId' => '',
            ];
        }
        $data = $this->samplePayload();
        $data['players'] = $players;

        $this->actingAs($user)
            ->postJson(route('account.open-play.sessions.store'), [
                'title' => 'Too big',
                'data' => $data,
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'GameQ allows at most '.OpenPlaySession::MAX_PLAYERS_PER_SESSION.' players per session.',
            );
    }

    public function test_deleting_saved_session_cascades_linked_live_shares(): void
    {
        $user = User::factory()->create();
        $session = $user->openPlaySessions()->create([
            'title' => 'S',
            'payload' => $this->samplePayload(),
        ]);
        $share = OpenPlayShare::query()->create([
            'open_play_session_id' => $session->id,
            'uuid' => (string) Str::uuid(),
            'secret_hash' => Hash::make('x'),
            'payload' => ['mode' => 'singles', 'players' => []],
        ]);

        $this->actingAs($user)
            ->deleteJson(route('account.open-play.sessions.destroy', $session))
            ->assertOk();

        $this->assertDatabaseMissing('open_play_shares', ['id' => $share->id]);
    }

    public function test_store_can_link_existing_share_with_secret(): void
    {
        $user = User::factory()->create();
        $plain = 'plain-secret-abc';
        $share = OpenPlayShare::query()->create([
            'open_play_session_id' => null,
            'uuid' => (string) Str::uuid(),
            'secret_hash' => Hash::make($plain),
            'payload' => ['mode' => 'singles', 'players' => []],
        ]);

        $r = $this->actingAs($user)->postJson(route('account.open-play.sessions.store'), [
            'title' => 'Linked',
            'data' => $this->samplePayload(),
            'link_share_uuid' => $share->uuid,
            'link_share_secret' => $plain,
        ]);

        $r->assertCreated();
        $share->refresh();
        $this->assertSame((int) $r->json('session.id'), (int) $share->open_play_session_id);
    }
}
