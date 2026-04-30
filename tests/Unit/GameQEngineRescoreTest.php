<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineRescoreTest extends TestCase
{
    public function test_rebuild_stats_from_completed_matches_matches_incremental_complete(): void
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

        $after = $e->toArray();
        $this->assertSame(1, (int) ($after['players'][0]['wins'] ?? 0));
        $this->assertSame(1, (int) ($after['players'][1]['losses'] ?? 0));

        $e2 = new Engine($after);
        $e2->rebuildStatsFromCompletedMatches();
        $replay = $e2->toArray();
        $this->assertSame($after['players'][0]['wins'], $replay['players'][0]['wins']);
        $this->assertSame($after['players'][0]['losses'], $replay['players'][0]['losses']);
        $this->assertSame($after['h2h'], $replay['h2h']);
    }

    public function test_editing_completed_log_and_rebuild_flips_standings(): void
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
        $after = $e->toArray();
        $this->assertSame(1, (int) $after['players'][0]['wins']);

        $after['completedMatches'][0]['scoreA'] = 8;
        $after['completedMatches'][0]['scoreB'] = 11;

        $e3 = new Engine($after);
        $e3->rebuildStatsFromCompletedMatches();
        $fixed = $e3->toArray();

        $this->assertSame(0, (int) $fixed['players'][0]['wins']);
        $this->assertSame(1, (int) $fixed['players'][0]['losses']);
        $this->assertSame(1, (int) $fixed['players'][1]['wins']);
        $this->assertSame(0, (int) $fixed['players'][1]['losses']);
    }

    public function test_remove_completed_match_drops_it_from_standings(): void
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
        $after = $e->toArray();
        $this->assertCount(1, $after['completedMatches']);
        $this->assertSame(1, (int) $after['players'][0]['wins']);

        $e2 = new Engine($after);
        $e2->removeCompletedMatchAtIndex(0);
        $cleared = $e2->toArray();

        $this->assertSame([], $cleared['completedMatches']);
        $this->assertSame(0, (int) $cleared['players'][0]['wins']);
        $this->assertSame(0, (int) $cleared['players'][0]['losses']);
        $this->assertSame(0, (int) $cleared['players'][1]['wins']);
        $this->assertSame(0, (int) $cleared['players'][1]['losses']);
        $this->assertSame([], $cleared['h2h']);
    }

    public function test_complete_match_with_winner_matches_manual_score_draft(): void
    {
        $state = Engine::defaultState();
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['courts'] = [['sideA' => ['a'], 'sideB' => ['b'], 'startedAt' => 1, 'timerRunState' => 'stopped']];

        $e = new Engine($state);
        $e->completeMatchWithWinner(0, true, 1_700_000_000_000);
        $out = $e->toArray();

        $this->assertSame(1, (int) $out['players'][0]['wins']);
        $this->assertSame(1, (int) $out['players'][1]['losses']);
        $this->assertEqualsWithDelta(11.0, (float) $out['completedMatches'][0]['scoreA'], 0.001);
        $this->assertEqualsWithDelta(9.0, (float) $out['completedMatches'][0]['scoreB'], 0.001);
    }

    public function test_set_completed_match_winner_updates_standings(): void
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
        $after = $e->toArray();
        $this->assertSame(1, (int) $after['players'][0]['wins']);

        $e2 = new Engine($after);
        $e2->setCompletedMatchWinner(0, false);
        $flipped = $e2->toArray();

        $this->assertEqualsWithDelta(9.0, (float) $flipped['completedMatches'][0]['scoreA'], 0.001);
        $this->assertEqualsWithDelta(11.0, (float) $flipped['completedMatches'][0]['scoreB'], 0.001);
        $this->assertSame(0, (int) $flipped['players'][0]['wins']);
        $this->assertSame(1, (int) $flipped['players'][0]['losses']);
        $this->assertSame(1, (int) $flipped['players'][1]['wins']);
        $this->assertSame(0, (int) $flipped['players'][1]['losses']);
    }
}
