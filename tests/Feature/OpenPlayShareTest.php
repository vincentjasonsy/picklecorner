<?php

namespace Tests\Feature;

use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenPlayShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_guest_cannot_create_open_play_share(): void
    {
        $this->postJson(route('open-play.share.store'), [
            'data' => ['mode' => 'singles', 'players' => []],
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_open_play_share(): void
    {
        $user = User::factory()->create();
        $payload = [
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

        $response = $this->actingAs($user)->postJson(route('open-play.share.store'), [
            'data' => $payload,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['uuid', 'secret', 'watch_url']);

        $this->assertDatabaseCount('open_play_shares', 1);
    }

    public function test_share_store_can_attach_saved_session_id(): void
    {
        $user = User::factory()->create();
        $session = $user->openPlaySessions()->create([
            'title' => 'H',
            'payload' => [
                'mode' => 'singles',
                'shuffleMethod' => 'random',
                'courtsCount' => 1,
                'timeLimitMinutes' => 0,
                'players' => [],
                'queue' => [],
                'courts' => [null],
                'completedMatches' => [],
                'h2h' => [],
            ],
        ]);
        $payload = [
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

        $response = $this->actingAs($user)->postJson(route('open-play.share.store'), [
            'data' => $payload,
            'open_play_session_id' => $session->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('open_play_shares', [
            'uuid' => $response->json('uuid'),
            'open_play_session_id' => $session->id,
        ]);
    }

    public function test_share_store_rejects_other_users_saved_session_id(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $session = $owner->openPlaySessions()->create([
            'title' => 'Mine',
            'payload' => [
                'mode' => 'singles',
                'shuffleMethod' => 'random',
                'courtsCount' => 1,
                'timeLimitMinutes' => 0,
                'players' => [],
                'queue' => [],
                'courts' => [null],
                'completedMatches' => [],
                'h2h' => [],
            ],
        ]);
        $payload = [
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

        $this->actingAs($other)->postJson(route('open-play.share.store'), [
            'data' => $payload,
            'open_play_session_id' => $session->id,
        ])->assertUnprocessable();
    }

    public function test_share_store_rejects_more_than_max_players(): void
    {
        $user = User::factory()->create();
        $players = [];
        for ($i = 0; $i < OpenPlaySession::MAX_PLAYERS_PER_SESSION + 1; $i++) {
            $players[] = [
                'id' => 'p'.$i,
                'name' => 'P'.$i,
                'level' => 3,
                'wins' => 0,
                'losses' => 0,
                'disabled' => false,
                'teamId' => '',
            ];
        }

        $this->actingAs($user)->postJson(route('open-play.share.store'), [
            'data' => [
                'mode' => 'singles',
                'shuffleMethod' => 'random',
                'courtsCount' => 1,
                'timeLimitMinutes' => 0,
                'players' => $players,
                'queue' => [],
                'courts' => [null],
                'completedMatches' => [],
                'h2h' => [],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'GameQ allows at most '.OpenPlaySession::MAX_PLAYERS_PER_SESSION.' players per session.',
            );
    }

    public function test_guest_can_view_live_watch_page(): void
    {
        $share = OpenPlayShare::query()->create([
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'secret_hash' => bcrypt('test-secret'),
            'payload' => [
                'mode' => 'singles',
                'players' => [['id' => '1', 'name' => 'Sam', 'level' => 4, 'wins' => 1, 'losses' => 0, 'disabled' => false, 'teamId' => '']],
                'queue' => ['1'],
                'courts' => [null],
                'shuffleMethod' => 'random',
                'courtsCount' => 1,
                'timeLimitMinutes' => 0,
                'completedMatches' => [],
                'h2h' => [],
            ],
        ]);

        $this->get(route('open-play.watch', $share))
            ->assertOk()
            ->assertSee('GameQ · Live', false)
            ->assertSee('Sam', false)
            ->assertSee('Playing today', false)
            ->assertSee('wire:poll.1s="refreshWatch"', false);
    }

    public function test_host_can_update_share_payload_with_secret(): void
    {
        $secret = 'host-secret-abc';
        $share = OpenPlayShare::query()->create([
            'uuid' => '660e8400-e29b-41d4-a716-446655440001',
            'secret_hash' => bcrypt($secret),
            'payload' => ['mode' => 'singles', 'players' => []],
        ]);

        $this->actingAs(User::factory()->create())->putJson(route('open-play.share.update', $share), [
            'secret' => $secret,
            'data' => ['mode' => 'doubles', 'players' => [['id' => 'x', 'name' => 'Pat', 'level' => 2, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'teamId' => '']]],
        ])->assertOk();

        $share->refresh();
        $this->assertSame('doubles', $share->payload['mode']);
        $this->assertSame('Pat', $share->payload['players'][0]['name']);
    }

    public function test_share_update_rejects_more_than_max_players(): void
    {
        $secret = 'secret-upd-max';
        $share = OpenPlayShare::query()->create([
            'uuid' => '990e8400-e29b-41d4-a716-446655440009',
            'secret_hash' => bcrypt($secret),
            'payload' => ['mode' => 'singles', 'players' => []],
        ]);
        $players = [];
        for ($i = 0; $i < OpenPlaySession::MAX_PLAYERS_PER_SESSION + 1; $i++) {
            $players[] = [
                'id' => 'u'.$i,
                'name' => 'U'.$i,
                'level' => 3,
                'wins' => 0,
                'losses' => 0,
                'disabled' => false,
                'teamId' => '',
            ];
        }

        $this->actingAs(User::factory()->create())->putJson(route('open-play.share.update', $share), [
            'secret' => $secret,
            'data' => [
                'mode' => 'singles',
                'players' => $players,
                'queue' => [],
                'courts' => [],
                'completedMatches' => [],
                'h2h' => [],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'GameQ allows at most '.OpenPlaySession::MAX_PLAYERS_PER_SESSION.' players per session.',
            );
    }

    public function test_update_rejects_wrong_secret(): void
    {
        $share = OpenPlayShare::query()->create([
            'uuid' => '770e8400-e29b-41d4-a716-446655440002',
            'secret_hash' => bcrypt('good'),
            'payload' => ['mode' => 'singles'],
        ]);

        $this->actingAs(User::factory()->create())->putJson(route('open-play.share.update', $share), [
            'secret' => 'wrong',
            'data' => ['mode' => 'doubles'],
        ])->assertForbidden();
    }

    public function test_public_json_data_endpoint_returns_payload(): void
    {
        $share = OpenPlayShare::query()->create([
            'uuid' => '880e8400-e29b-41d4-a716-446655440003',
            'secret_hash' => bcrypt('x'),
            'payload' => ['mode' => 'singles', 'queue' => ['a', 'b']],
        ]);

        $res = $this->getJson(route('open-play.watch.data', $share));
        $res->assertOk()
            ->assertJsonPath('data.mode', 'singles')
            ->assertJsonPath('data.queue', ['a', 'b']);
        $this->assertIsInt($res->json('server_at_ms'));
    }

    public function test_guest_can_toggle_take_a_break_from_live_watch(): void
    {
        $share = OpenPlayShare::query()->create([
            'uuid' => '990e8400-e29b-41d4-a716-446655440004',
            'secret_hash' => bcrypt('x'),
            'payload' => [
                'mode' => 'singles',
                'shuffleMethod' => 'random',
                'courtsCount' => 1,
                'timeLimitMinutes' => 0,
                'players' => [
                    [
                        'id' => 'p1',
                        'name' => 'Sam',
                        'level' => 3,
                        'wins' => 0,
                        'losses' => 0,
                        'disabled' => false,
                        'skipShuffle' => false,
                        'teamId' => '',
                    ],
                ],
                'queue' => ['p1'],
                'courts' => [null],
                'completedMatches' => [],
                'h2h' => [],
            ],
        ]);

        $this->postJson(route('open-play.watch.toggle-break', $share), [
            'player_id' => 'p1',
            'skip_shuffle' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.players.0.skipShuffle', true)
            ->assertJsonPath('data.queue', []);

        $share->refresh();
        $this->assertTrue($share->payload['players'][0]['skipShuffle']);
        $this->assertSame([], $share->payload['queue']);
    }

    public function test_toggle_take_a_break_rejects_inactive_player(): void
    {
        $share = OpenPlayShare::query()->create([
            'uuid' => 'aa0e8400-e29b-41d4-a716-446655440005',
            'secret_hash' => bcrypt('x'),
            'payload' => [
                'mode' => 'singles',
                'players' => [
                    [
                        'id' => 'p1',
                        'name' => 'Sam',
                        'disabled' => true,
                        'skipShuffle' => false,
                    ],
                ],
                'queue' => [],
                'courts' => [],
                'completedMatches' => [],
                'h2h' => [],
            ],
        ]);

        $this->postJson(route('open-play.watch.toggle-break', $share), [
            'player_id' => 'p1',
            'skip_shuffle' => true,
        ])->assertStatus(422);
    }
}
