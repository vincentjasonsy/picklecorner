<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineQueueOrderTest extends TestCase
{
    public function test_fill_courts_doubles_keeps_ordered_pool_even_when_shuffle_method_is_random(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'doubles';
        $state['shuffleMethod'] = 'random';
        $state['courtsCount'] = 1;
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'c', 'name' => 'C', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'd', 'name' => 'D', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['d', 'c', 'b', 'a'];
        $state['courts'] = [null];

        $e = new Engine($state);
        $e->fillCourts();

        $court = $e->toArray()['courts'][0];
        $this->assertIsArray($court);
        $this->assertSame(['d', 'c'], array_map('strval', $court['sideA'] ?? []));
        $this->assertSame(['b', 'a'], array_map('strval', $court['sideB'] ?? []));
        $this->assertArrayNotHasKey('startedAt', $court);
        $this->assertSame('stopped', $court['timerRunState'] ?? null);
    }

    public function test_fill_courts_singles_keeps_queue_order_with_random_shuffle_method(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'singles';
        $state['shuffleMethod'] = 'random';
        $state['courtsCount'] = 2;
        $state['players'] = [
            ['id' => 'w', 'name' => 'W', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'x', 'name' => 'X', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'y', 'name' => 'Y', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'z', 'name' => 'Z', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['z', 'y', 'x', 'w'];
        $state['courts'] = [null, null];

        $e = new Engine($state);
        $e->fillCourts();

        $courts = $e->toArray()['courts'];
        $this->assertIsArray($courts[0]);
        $this->assertIsArray($courts[1]);
        $this->assertSame(['z'], array_map('strval', $courts[0]['sideA'] ?? []));
        $this->assertSame(['y'], array_map('strval', $courts[0]['sideB'] ?? []));
        $this->assertSame(['x'], array_map('strval', $courts[1]['sideA'] ?? []));
        $this->assertSame(['w'], array_map('strval', $courts[1]['sideB'] ?? []));
    }

    public function test_sync_queue_prioritizes_players_with_fewer_games_stable_within_ties(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'singles';
        $state['courtsCount'] = 1;
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 2, 'losses' => 1, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'c', 'name' => 'C', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['a', 'c', 'b'];
        $state['courts'] = [null];

        $e = new Engine($state);
        $e->syncQueueFromIdle();

        $this->assertSame(['c', 'b', 'a'], array_map('strval', $e->toArray()['queue']));
    }

    public function test_fill_courts_singles_orders_by_fewer_games_before_pairing(): void
    {
        $state = Engine::defaultState();
        $state['mode'] = 'singles';
        $state['shuffleMethod'] = 'random';
        $state['courtsCount'] = 2;
        $state['players'] = [
            ['id' => 'w', 'name' => 'W', 'level' => 3, 'wins' => 5, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'x', 'name' => 'X', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'y', 'name' => 'Y', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'z', 'name' => 'Z', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['w', 'z', 'y', 'x'];
        $state['courts'] = [null, null];

        $e = new Engine($state);
        $e->fillCourts();

        $courts = $e->toArray()['courts'];
        $this->assertIsArray($courts[0]);
        $this->assertIsArray($courts[1]);
        $this->assertSame(['z'], array_map('strval', $courts[0]['sideA'] ?? []));
        $this->assertSame(['y'], array_map('strval', $courts[0]['sideB'] ?? []));
        $this->assertSame(['x'], array_map('strval', $courts[1]['sideA'] ?? []));
        $this->assertSame(['w'], array_map('strval', $courts[1]['sideB'] ?? []));
    }

    public function test_sync_queue_orders_ties_by_skill_when_shuffle_method_is_levels(): void
    {
        $state = Engine::defaultState();
        $state['shuffleMethod'] = 'levels';
        $state['mode'] = 'singles';
        $state['courtsCount'] = 1;
        $state['players'] = [
            ['id' => 'hi', 'name' => 'Hi', 'level' => 8, 'wins' => 1, 'losses' => 1, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'lo', 'name' => 'Lo', 'level' => 2, 'wins' => 1, 'losses' => 1, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'mid', 'name' => 'Mid', 'level' => 5, 'wins' => 1, 'losses' => 1, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['hi', 'mid', 'lo'];
        $state['courts'] = [null];

        $e = new Engine($state);
        $e->syncQueueFromIdle();

        $this->assertSame(['lo', 'mid', 'hi'], array_map('strval', $e->toArray()['queue']));
    }

    public function test_sync_queue_levels_rotate_interleaves_skill_bands_within_same_games(): void
    {
        $state = Engine::defaultState();
        $state['shuffleMethod'] = 'levels_rotate';
        $state['mode'] = 'singles';
        $state['courtsCount'] = 1;
        $state['players'] = [
            ['id' => 'd2', 'name' => 'D', 'level' => 2, 'wins' => 0, 'losses' => 2, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b3', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 2, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'c4', 'name' => 'C', 'level' => 4, 'wins' => 0, 'losses' => 2, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'a2', 'name' => 'A', 'level' => 2, 'wins' => 0, 'losses' => 2, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['d2', 'c4', 'b3', 'a2'];
        $state['courts'] = [null];

        $e = new Engine($state);
        $e->syncQueueFromIdle();

        $this->assertSame(['a2', 'b3', 'c4', 'd2'], array_map('strval', $e->toArray()['queue']));
    }
}
