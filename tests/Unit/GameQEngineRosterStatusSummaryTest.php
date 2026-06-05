<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineRosterStatusSummaryTest extends TestCase
{
    public function test_roster_status_summary_counts_on_break_and_off(): void
    {
        $state = Engine::defaultState();
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => true, 'teamId' => ''],
            ['id' => 'c', 'name' => 'C', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => true, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['courts'] = [['sideA' => ['a'], 'sideB' => ['x'], 'timerRunState' => 'running']];

        $summary = (new Engine($state))->rosterStatusSummary();

        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['on']);
        $this->assertSame(1, $summary['break']);
        $this->assertSame(1, $summary['off']);
        $this->assertSame(1, $summary['playing']);
    }
}
