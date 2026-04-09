<?php

namespace App\Livewire;

use App\GameQ\Engine;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OpenPlayPlayerH2h extends Component
{
    public string $playerId = '';

    /** @var array<string, mixed> */
    public array $state = [];

    public bool $sessionActive = false;

    public bool $playerFound = false;

    public function mount(string $playerId): void
    {
        $this->playerId = $playerId;
        $raw = session('gameq_organizer_v2', []);
        $this->state = is_array($raw) ? $raw : [];
        $phase = (string) ($this->state['uiPhase'] ?? '');
        $this->sessionActive = $phase === 'session';
        if (! $this->sessionActive) {
            return;
        }
        $e = new Engine($this->state);
        $p = $e->playerById($playerId);
        $this->playerFound = $p !== null && is_array($p);
    }

    public function render(): View
    {
        $title = 'GameQ · Player';
        if ($this->playerFound) {
            $e = new Engine($this->state);
            $p = $e->playerById($this->playerId);
            $name = is_array($p) ? trim((string) ($p['name'] ?? '')) : '';
            if ($name !== '') {
                $title = $name.' · Head-to-head';
            }
        }

        return view('livewire.open-play-player-h2h')
            ->layout('layouts::tool-focus', [
                'title' => $title,
            ]);
    }
}
