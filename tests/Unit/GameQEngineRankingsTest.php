<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineRankingsTest extends TestCase
{
    /**
     * @param  list<array{name: string, wins: int, losses: int}>  $players
     * @return list<string>
     */
    private function rankingNames(array $players): array
    {
        $state = Engine::defaultState();
        $state['uiPhase'] = 'session';
        foreach ($players as $i => $row) {
            $state['players'][] = [
                'id' => 'id-'.$i,
                'name' => $row['name'],
                'level' => 3,
                'wins' => $row['wins'],
                'losses' => $row['losses'],
                'disabled' => false,
                'skipShuffle' => false,
                'teamId' => '',
            ];
        }
        $engine = new Engine($state);
        $ranked = $engine->rankings();

        return array_map(fn (array $r) => (string) ($r['name'] ?? ''), $ranked);
    }

    public function test_more_wins_still_ranks_above_fewer_wins(): void
    {
        $names = $this->rankingNames([
            ['name' => 'Busy', 'wins' => 6, 'losses' => 2],
            ['name' => 'Short', 'wins' => 2, 'losses' => 1],
        ]);
        $this->assertSame(['Busy', 'Short'], $names);
    }

    public function test_same_wins_prefers_more_games_played_over_win_percentage(): void
    {
        $names = $this->rankingNames([
            ['name' => 'Hot', 'wins' => 2, 'losses' => 0],
            ['name' => 'Grind', 'wins' => 2, 'losses' => 2],
        ]);
        $this->assertSame(['Grind', 'Hot'], $names);
    }

    public function test_same_wins_more_games_edges_same_record_depth(): void
    {
        $names = $this->rankingNames([
            ['name' => 'ThreeZero', 'wins' => 3, 'losses' => 0],
            ['name' => 'ThreeOne', 'wins' => 3, 'losses' => 1],
        ]);
        $this->assertSame(['ThreeOne', 'ThreeZero'], $names);
    }

    public function test_identical_record_breaks_tie_by_name(): void
    {
        $names = $this->rankingNames([
            ['name' => 'Zed', 'wins' => 2, 'losses' => 2],
            ['name' => 'Ann', 'wins' => 2, 'losses' => 2],
        ]);
        $this->assertSame(['Ann', 'Zed'], $names);
    }

    public function test_zero_wins_prefers_fewer_losses(): void
    {
        $names = $this->rankingNames([
            ['name' => 'Rough', 'wins' => 0, 'losses' => 5],
            ['name' => 'New', 'wins' => 0, 'losses' => 1],
        ]);
        $this->assertSame(['New', 'Rough'], $names);
    }
}
