<?php

namespace App\Livewire;

use App\GameQ\Engine;
use App\Models\OpenPlayShare;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OpenPlayWatchPlayerH2h extends Component
{
    public OpenPlayShare $openPlayShare;

    public string $playerId = '';

    /** @var array<string, mixed> */
    public array $game = [];

    public bool $loadFailed = false;

    public bool $playerFound = false;

    public ?string $updatedAtIso = null;

    public function mount(OpenPlayShare $openPlayShare, string $playerId): void
    {
        $this->openPlayShare = $openPlayShare;
        $this->playerId = $playerId;
        $this->refreshGame();
    }

    public function refreshGame(): void
    {
        $this->openPlayShare->refresh();
        $raw = $this->openPlayShare->payload;
        if (! is_array($raw)) {
            $this->loadFailed = true;
            $this->playerFound = false;

            return;
        }
        $this->game = (new Engine($raw))->toArray();
        $this->updatedAtIso = $this->openPlayShare->updated_at?->toIso8601String();
        $this->loadFailed = false;
        $e = new Engine($this->game);
        $p = $e->playerById($this->playerId);
        $this->playerFound = $p !== null && is_array($p);
    }

    public function render(): View
    {
        $title = 'GameQ · Live';
        if ($this->playerFound) {
            $e = new Engine($this->game);
            $p = $e->playerById($this->playerId);
            $name = is_array($p) ? trim((string) ($p['name'] ?? '')) : '';
            if ($name !== '') {
                $title = $name.' · Head-to-head · Live';
            }
        }

        return view('livewire.open-play-watch-player-h2h')
            ->layout('layouts::guest')
            ->title($title.' — '.config('app.name'));
    }
}
