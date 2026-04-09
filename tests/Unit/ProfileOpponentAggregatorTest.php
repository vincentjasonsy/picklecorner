<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use App\GameQ\ProfileOpponentAggregator;
use App\Models\OpenPlaySession;
use App\Models\User;
use Tests\TestCase;

class ProfileOpponentAggregatorTest extends TestCase
{
    public function test_aggregates_wins_and_losses_against_opponents_by_display_name(): void
    {
        $base = Engine::defaultState();
        $base['courtsCount'] = 1;
        $base['courts'] = [null];
        $base['scoreDraft'] = [['a' => 0, 'b' => 0]];
        $base['players'] = [
            ['id' => 'me', 'name' => 'Hero Host', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 's', 'name' => 'Sam', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $base['completedMatches'] = [
            [
                'sideA' => ['me'],
                'sideB' => ['s'],
                'scoreA' => 11,
                'scoreB' => 9,
                'at' => 1,
                'courtIndex' => 0,
            ],
            [
                'sideA' => ['s'],
                'sideB' => ['me'],
                'scoreA' => 11,
                'scoreB' => 7,
                'at' => 2,
                'courtIndex' => 0,
            ],
        ];

        $session = OpenPlaySession::make(['payload' => $base]);
        $user = new User(['name' => 'Hero Host']);

        $out = ProfileOpponentAggregator::forUser($user, [$session]);

        $this->assertSame(1, $out['sessions_matched']);
        $this->assertSame(2, $out['matches_counted']);
        $this->assertCount(1, $out['opponents']);
        $this->assertSame('Sam', $out['opponents'][0]['displayName']);
        $this->assertSame(1, $out['opponents'][0]['wins']);
        $this->assertSame(1, $out['opponents'][0]['losses']);
        $this->assertSame(0, $out['opponents'][0]['ties']);
    }

    public function test_partner_row_for_doubles_teammate(): void
    {
        $base = Engine::defaultState();
        $base['mode'] = 'doubles';
        $base['courtsCount'] = 1;
        $base['courts'] = [null];
        $base['scoreDraft'] = [['a' => 0, 'b' => 0]];
        $base['players'] = [
            ['id' => 'me', 'name' => 'Alex', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'p', 'name' => 'Pat', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'x', 'name' => 'X', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'y', 'name' => 'Y', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $base['completedMatches'] = [
            [
                'sideA' => ['me', 'p'],
                'sideB' => ['x', 'y'],
                'scoreA' => 11,
                'scoreB' => 5,
                'at' => 1,
                'courtIndex' => 0,
            ],
        ];

        $session = OpenPlaySession::make(['payload' => $base]);
        $user = new User(['name' => 'Alex']);

        $out = ProfileOpponentAggregator::forUser($user, [$session]);

        $this->assertSame(1, $out['matches_counted']);
        $names = array_column($out['opponents'], 'displayName');
        sort($names);
        $this->assertSame(['X', 'Y'], $names);
        $this->assertCount(1, $out['partners']);
        $this->assertSame('Pat', $out['partners'][0]['displayName']);
        $this->assertSame(1, $out['partners'][0]['games']);
    }
}
