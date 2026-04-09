<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEnginePlayerH2hTest extends TestCase
{
    public function test_head_to_head_rows_for_player_singles(): void
    {
        $state = Engine::defaultState();
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['courts'] = [['sideA' => ['a'], 'sideB' => ['b'], 'startedAt' => 1, 'timerRunState' => 'stopped']];
        $state['scoreDraft'][0] = ['a' => 11, 'b' => 9];

        $e = new Engine($state);
        $e->completeMatch(0, 1_700_000_000_000);

        $rowsA = $e->headToHeadRowsForPlayer('a');
        $this->assertCount(1, $rowsA);
        $this->assertSame('B', $rowsA[0]['opponentLabel']);
        $this->assertSame(1, $rowsA[0]['winsSelf']);
        $this->assertSame(0, $rowsA[0]['winsOpp']);

        $rowsB = $e->headToHeadRowsForPlayer('b');
        $this->assertSame('A', $rowsB[0]['opponentLabel']);
        $this->assertSame(0, $rowsB[0]['winsSelf']);
        $this->assertSame(1, $rowsB[0]['winsOpp']);
    }

    public function test_player_opponent_game_breakdown_lists_each_game(): void
    {
        $state = Engine::defaultState();
        $state['courtsCount'] = 1;
        $state['courts'] = [null];
        $state['scoreDraft'] = [['a' => 0, 'b' => 0]];
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];

        $e = new Engine($state);
        $s = $e->toArray();
        $s['courts'][0] = ['sideA' => ['a'], 'sideB' => ['b'], 'startedAt' => 1, 'timerRunState' => 'stopped'];
        $s['scoreDraft'][0] = ['a' => 11, 'b' => 9];
        $e = new Engine($s);
        $e->completeMatch(0, 1_700_000_000_000);

        $this->assertSame(1, $e->countCompletedMatchesInvolvingPlayer('a'));

        $s = $e->toArray();
        $s['courts'][0] = ['sideA' => ['a'], 'sideB' => ['b'], 'startedAt' => 1, 'timerRunState' => 'stopped'];
        $s['scoreDraft'][0] = ['a' => 8, 'b' => 11];
        $e = new Engine($s);
        $e->completeMatch(0, 1_700_000_001_000);

        $this->assertSame(2, $e->countCompletedMatchesInvolvingPlayer('a'));

        $bd = $e->playerOpponentGameBreakdown('a');
        $this->assertCount(1, $bd);
        $this->assertSame('B', $bd[0]['opponentLabel']);
        $this->assertSame(1, $bd[0]['winsSelf']);
        $this->assertSame(1, $bd[0]['winsOpp']);
        $this->assertCount(2, $bd[0]['lines']);
        $this->assertFalse($bd[0]['lines'][0]['won']);
        $this->assertTrue($bd[0]['lines'][1]['won']);

        $per = $e->perOpponentPlayerBreakdown('a');
        $this->assertCount(1, $per);
        $this->assertSame('B', $per[0]['displayName']);
        $this->assertSame(1, $per[0]['winsSelf']);
        $this->assertSame(1, $per[0]['winsOpp']);
    }

    public function test_per_opponent_player_breakdown_doubles_lists_each_rival_separately(): void
    {
        $base = Engine::defaultState();
        $base['mode'] = 'doubles';
        $base['courtsCount'] = 1;
        $base['courts'] = [null];
        $base['scoreDraft'] = [['a' => 0, 'b' => 0]];
        $base['players'] = [
            ['id' => 'me', 'name' => 'Alex', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'p', 'name' => 'Pat', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'x', 'name' => 'Chris', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'y', 'name' => 'Sam', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
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

        $e = new Engine($base);
        $per = $e->perOpponentPlayerBreakdown('me');
        $this->assertCount(2, $per);
        $names = array_column($per, 'displayName');
        sort($names);
        $this->assertSame(['Chris', 'Sam'], $names);
        $byName = [];
        foreach ($per as $row) {
            $byName[$row['displayName']] = $row;
        }
        $this->assertSame(1, $byName['Chris']['winsSelf']);
        $this->assertSame(0, $byName['Chris']['winsOpp']);
        $this->assertSame(1, $byName['Sam']['winsSelf']);
        $this->assertSame(0, $byName['Sam']['winsOpp']);
        $this->assertCount(1, $byName['Chris']['lines']);
        $this->assertSame(11.0, $byName['Chris']['lines'][0]['scoreSelf']);
        $this->assertSame(5.0, $byName['Chris']['lines'][0]['scoreOpp']);
    }
}
