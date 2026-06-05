<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineLineupSuggestTest extends TestCase
{
    public function test_suggest_lineup_pick_prefers_fewest_games_and_respects_skill_lock(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'singles';
        $state['shuffleMethod'] = 'wins';
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 2, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'c', 'name' => 'C', 'level' => 4, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['courts'] = [['sideA' => ['a'], 'sideB' => ['x'], 'timerRunState' => 'stopped']];
        $state['courtSkillLocks'] = [4];
        $state['courtLineupDraft'] = [
            0 => ['a' => ['a'], 'b' => ['']],
        ];

        $e = new Engine($state);
        $this->assertSame('c', $e->suggestLineupDraftPick(0, 'b', 0));
    }

    public function test_randomize_lineup_slot_writes_draft(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'singles';
        $state['shuffleMethod'] = 'random';
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['courts'] = [['sideA' => ['a'], 'sideB' => ['b'], 'timerRunState' => 'stopped']];
        $state['courtLineupDraft'] = [
            0 => ['a' => ['a'], 'b' => ['']],
        ];

        $e = new Engine($state);
        $e->randomizeLineupDraftSlot(0, 'b', 0);
        $draft = $e->toArray()['courtLineupDraft'][0]['b'][0] ?? '';

        $this->assertSame('b', $draft);
    }

    public function test_doubles_suggest_pairs_similar_skill_on_same_side(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'doubles';
        $state['shuffleMethod'] = 'levels';
        $state['players'] = [
            ['id' => 'p1', 'name' => 'P1', 'level' => 4, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'p2', 'name' => 'P2', 'level' => 4, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'p3', 'name' => 'P3', 'level' => 2, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'p4', 'name' => 'P4', 'level' => 2, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['courts'] = [[
            'sideA' => ['p1', ''],
            'sideB' => ['p3', 'p4'],
            'timerRunState' => 'stopped',
        ]];
        $state['courtLineupDraft'] = [
            0 => ['a' => ['p1', ''], 'b' => ['p3', 'p4']],
        ];

        $e = new Engine($state);
        $this->assertSame('p2', $e->suggestLineupDraftPick(0, 'a', 1));
    }
}
