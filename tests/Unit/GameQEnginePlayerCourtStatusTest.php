<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEnginePlayerCourtStatusTest extends TestCase
{
    public function test_player_active_court_label_returns_custom_court_name(): void
    {
        $state = Engine::defaultState();
        $state['courtLabels'] = ['#3'];
        $state['courts'] = [[
            'sideA' => ['a'],
            'sideB' => ['b'],
            'timerRunState' => 'running',
        ]];

        $e = new Engine($state);

        $this->assertSame('#3', $e->playerActiveCourtLabel('a'));
        $this->assertSame('#3', $e->playerActiveCourtLabel('b'));
        $this->assertNull($e->playerActiveCourtLabel('c'));
    }

    public function test_player_active_court_label_uses_court_index_for_default_name(): void
    {
        $state = Engine::defaultState();
        $state['courts'] = [null, ['sideA' => ['a'], 'sideB' => ['b'], 'timerRunState' => 'running']];

        $e = new Engine($state);

        $this->assertNull($e->playerActiveCourtLabel('c'));
        $this->assertSame('Court 2', $e->playerActiveCourtLabel('a'));
        $this->assertSame('Court 2', $e->playerActiveCourtLabel('b'));
    }
}
