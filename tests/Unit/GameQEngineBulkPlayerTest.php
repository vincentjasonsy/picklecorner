<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineBulkPlayerTest extends TestCase
{
    public function test_parse_bulk_entries_extracts_skill_after_dash(): void
    {
        $raw = "LeBron James - 5\nStephen Curry - 5\nJayson Tatum - 4\n";
        $entries = Engine::parseBulkPlayerEntries($raw, 3);
        $this->assertCount(3, $entries);
        $this->assertSame('LeBron James', $entries[0]['name']);
        $this->assertSame(5, $entries[0]['level']);
        $this->assertSame('Stephen Curry', $entries[1]['name']);
        $this->assertSame(5, $entries[1]['level']);
        $this->assertSame('Jayson Tatum', $entries[2]['name']);
        $this->assertSame(4, $entries[2]['level']);
    }

    public function test_hyphenated_surname_with_skill(): void
    {
        $entries = Engine::parseBulkPlayerEntries('Shai Gilgeous-Alexander - 5', 3);
        $this->assertCount(1, $entries);
        $this->assertSame('Shai Gilgeous-Alexander', $entries[0]['name']);
        $this->assertSame(5, $entries[0]['level']);
    }

    public function test_line_without_skill_uses_default_level(): void
    {
        $entries = Engine::parseBulkPlayerEntries("Sam\n2. Jordan", 4);
        $this->assertSame('Sam', $entries[0]['name']);
        $this->assertSame(4, $entries[0]['level']);
        $this->assertSame('Jordan', $entries[1]['name']);
        $this->assertSame(4, $entries[1]['level']);
    }

    public function test_skill_clamped_to_1_5(): void
    {
        $entries = Engine::parseBulkPlayerEntries("Big - 0\nTall - 15", 5);
        $this->assertSame(1, $entries[0]['level']);
        $this->assertSame(5, $entries[1]['level']);
    }

    public function test_normalize_state_clamps_legacy_player_levels_above_five(): void
    {
        $raw = array_merge(Engine::defaultState(), [
            'players' => [
                [
                    'id' => 'x',
                    'name' => 'X',
                    'level' => 10,
                    'wins' => 0,
                    'losses' => 0,
                    'disabled' => false,
                    'skipShuffle' => false,
                    'teamId' => '',
                ],
            ],
        ]);
        $e = new Engine($raw);
        $this->assertSame(5, $e->toArray()['players'][0]['level']);
    }

    public function test_add_players_from_bulk_applies_per_line_level(): void
    {
        $state = Engine::defaultState();
        $state['uiPhase'] = 'session';
        $state['newLevel'] = 3;
        $state['bulkPlayerList'] = "A - 9\nB\n";
        $e = new Engine($state);
        $e->addPlayersFromBulk();
        $players = $e->toArray()['players'];
        $this->assertCount(2, $players);
        $this->assertSame('A', $players[0]['name']);
        $this->assertSame(5, $players[0]['level']);
        $this->assertSame('B', $players[1]['name']);
        $this->assertSame(3, $players[1]['level']);
    }
}
