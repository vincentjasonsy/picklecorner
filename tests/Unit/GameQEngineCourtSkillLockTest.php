<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineCourtSkillLockTest extends TestCase
{
    /** @return array<string, mixed> */
    private function basePlayer(string $id, int $level): array
    {
        return [
            'id' => $id,
            'name' => strtoupper($id),
            'level' => $level,
            'wins' => 0,
            'losses' => 0,
            'disabled' => false,
            'skipShuffle' => false,
            'teamId' => '',
        ];
    }

    public function test_fill_courts_assigns_level_locked_slot_before_open_slots(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'doubles';
        $state['shuffleMethod'] = 'random';
        $state['courtsCount'] = 2;
        $state['courtSkillLocks'] = [5, 0];
        $state['players'] = [
            $this->basePlayer('a', 5),
            $this->basePlayer('b', 5),
            $this->basePlayer('c', 5),
            $this->basePlayer('d', 5),
            $this->basePlayer('w', 4),
            $this->basePlayer('x', 4),
            $this->basePlayer('y', 4),
            $this->basePlayer('z', 4),
        ];
        $state['queue'] = ['a', 'b', 'c', 'd', 'w', 'x', 'y', 'z'];
        $state['courts'] = [null, null];

        $e = new Engine($state);
        $e->fillCourts();
        $courts = $e->toArray()['courts'];

        $this->assertIsArray($courts[0]);
        $this->assertIsArray($courts[1]);
        $all0 = array_map('strval', array_merge($courts[0]['sideA'] ?? [], $courts[0]['sideB'] ?? []));
        $all1 = array_map('strval', array_merge($courts[1]['sideA'] ?? [], $courts[1]['sideB'] ?? []));
        sort($all0);
        sort($all1);
        $this->assertSame(['a', 'b', 'c', 'd'], $all0);
        $this->assertSame(['w', 'x', 'y', 'z'], $all1);
    }

    public function test_fill_courts_leaves_locked_slot_empty_when_no_pure_level_match(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'doubles';
        $state['shuffleMethod'] = 'random';
        $state['courtsCount'] = 2;
        $state['courtSkillLocks'] = [5, 0];
        $state['players'] = [
            $this->basePlayer('a', 3),
            $this->basePlayer('b', 3),
            $this->basePlayer('c', 3),
            $this->basePlayer('d', 3),
            $this->basePlayer('w', 3),
            $this->basePlayer('x', 3),
            $this->basePlayer('y', 3),
            $this->basePlayer('z', 3),
        ];
        $state['queue'] = ['a', 'b', 'c', 'd', 'w', 'x', 'y', 'z'];
        $state['courts'] = [null, null];

        $e = new Engine($state);
        $e->fillCourts();
        $courts = $e->toArray()['courts'];

        $this->assertNull($courts[0]);
        $this->assertIsArray($courts[1]);
    }

    public function test_apply_court_lineup_rejects_wrong_level_for_locked_slot(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'singles';
        $state['courtsCount'] = 1;
        $state['courtSkillLocks'] = [4];
        $state['players'] = [
            $this->basePlayer('a', 4),
            $this->basePlayer('b', 3),
        ];
        $state['queue'] = ['a', 'b'];
        $state['courts'] = [null];

        $e = new Engine($state);
        $e->applyCourtLineup(0, ['a'], ['b']);

        $this->assertSame('This court is limited to skill level 4 only (every player on court).', $e->toArray()['lineupEditError']);
        $this->assertNull($e->toArray()['courts'][0]);
    }

    public function test_fill_courts_locked_slot_pulls_same_level_players_even_when_quartets_would_mix_levels(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'doubles';
        $state['shuffleMethod'] = 'random';
        $state['courtsCount'] = 2;
        $state['courtSkillLocks'] = [5, 0];
        $state['players'] = [
            $this->basePlayer('a5', 5),
            $this->basePlayer('b3', 3),
            $this->basePlayer('c5', 5),
            $this->basePlayer('d3', 3),
            $this->basePlayer('e5', 5),
            $this->basePlayer('f3', 3),
            $this->basePlayer('g5', 5),
            $this->basePlayer('h3', 3),
        ];
        $state['queue'] = ['a5', 'b3', 'c5', 'd3', 'e5', 'f3', 'g5', 'h3'];
        $state['courts'] = [null, null];

        $e = new Engine($state);
        $e->fillCourts();
        $courts = $e->toArray()['courts'];

        $this->assertIsArray($courts[0]);
        $this->assertIsArray($courts[1]);
        $ids0 = array_map('strval', array_merge($courts[0]['sideA'] ?? [], $courts[0]['sideB'] ?? []));
        $ids1 = array_map('strval', array_merge($courts[1]['sideA'] ?? [], $courts[1]['sideB'] ?? []));
        sort($ids0);
        sort($ids1);
        $this->assertSame(['a5', 'c5', 'e5', 'g5'], $ids0);
        $this->assertSame(['b3', 'd3', 'f3', 'h3'], $ids1);
    }

    public function test_share_payload_includes_court_skill_locks(): void
    {
        $state = Engine::defaultState();
        $state['courtSkillLocks'] = [0, 5];

        $e = new Engine($state);
        $payload = $e->sharePayload();

        $this->assertSame([0, 5], $payload['courtSkillLocks']);
    }
}
