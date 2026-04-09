<?php

namespace Tests\Unit;

use App\GameQ\Engine;
use PHPUnit\Framework\TestCase;

class GameQEngineRemoteWatchSyncTest extends TestCase
{
    public function test_apply_remote_watch_break_sync_updates_skip_shuffle_and_queue(): void
    {
        $state = Engine::defaultState();
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
            ['id' => 'b', 'name' => 'B', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['a', 'b'];

        $e = new Engine($state);
        $remote = $e->sharePayload();
        $remote['players'][0]['skipShuffle'] = true;
        $remote['queue'] = ['b'];

        $this->assertTrue($e->applyRemoteWatchBreakSync($remote));

        $out = $e->toArray();
        $this->assertTrue($out['players'][0]['skipShuffle']);
        $this->assertFalse($out['players'][1]['skipShuffle']);
        $this->assertSame(['b'], array_map('strval', $out['queue']));
    }

    public function test_apply_remote_watch_break_sync_returns_false_when_nothing_changes(): void
    {
        $state = Engine::defaultState();
        $state['players'] = [
            ['id' => 'a', 'name' => 'A', 'level' => 3, 'wins' => 0, 'losses' => 0, 'disabled' => false, 'skipShuffle' => false, 'teamId' => ''],
        ];
        $state['queue'] = ['a'];

        $e = new Engine($state);
        $remote = $e->sharePayload();

        $this->assertFalse($e->applyRemoteWatchBreakSync($remote));
    }
}
