<?php

namespace App\Livewire;

use App\GameQ\Engine;
use App\Models\OpenPlayShare;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('GameQ · Live (Beta)')]
class OpenPlayWatch extends Component
{
    public OpenPlayShare $openPlayShare;

    /** @var array<string, mixed> */
    public array $game = [];

    public bool $loadFailed = false;

    public ?string $updatedAtIso = null;

    public function mount(OpenPlayShare $openPlayShare): void
    {
        $this->openPlayShare = $openPlayShare;
        $this->refreshWatch();
    }

    public function refreshWatch(): void
    {
        $this->openPlayShare->refresh();
        $raw = $this->openPlayShare->payload;
        if (! is_array($raw)) {
            $this->loadFailed = true;

            return;
        }
        $this->game = (new Engine($raw))->toArray();
        $this->updatedAtIso = $this->openPlayShare->updated_at?->toIso8601String();
        $this->loadFailed = false;
    }

    public function syncedRelativeLabel(): string
    {
        if ($this->updatedAtIso === null || $this->updatedAtIso === '') {
            return '';
        }
        try {
            $d = Carbon::parse($this->updatedAtIso);
            $sec = max(0, (int) round(now()->getTimestamp() - $d->getTimestamp()));
            if ($sec < 10) {
                return 'just now';
            }
            if ($sec < 60) {
                return $sec.'s ago';
            }
            $m = intdiv($sec, 60);
            if ($m < 60) {
                return $m.' min ago';
            }
            $h = intdiv($m, 60);
            if ($h < 24) {
                return $h.' h ago';
            }

            return $d->timezone(config('app.timezone'))->format('M j, g:i a');
        } catch (\Throwable) {
            return '';
        }
    }

    public function render(): View
    {
        $nowMs = (int) round(microtime(true) * 1000);
        $eq = new Engine($this->game);

        return view('livewire.open-play-watch', [
            'eq' => $eq,
            'nowMs' => $nowMs,
        ]);
    }
}
