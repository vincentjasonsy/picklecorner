<?php

namespace App\Livewire;

use App\GameQ\Engine;
use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use App\Services\GameQShareToggleBreakPayload;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('GameQ · Live (Beta)')]
class OpenPlayWatch extends Component
{
    private const MAX_PAYLOAD_BYTES = 512000;

    public OpenPlayShare $openPlayShare;

    /** @var array<string, mixed> */
    public array $game = [];

    public bool $loadFailed = false;

    public string $h2hPlayerA = '';

    public string $h2hPlayerB = '';

    public string $toggleBreakError = '';

    /** Shown after a successful take-a-break toggle (survives wire:poll; cleared on next toggle attempt). */
    public string $toggleBreakSuccess = '';

    public ?string $toggleBreakBusyId = null;

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
        $this->primeH2hPicks();
    }

    public function updatedH2hPlayerA(): void
    {
        $this->primeH2hPicks();
    }

    public function updatedH2hPlayerB(): void
    {
        $this->primeH2hPicks();
    }

    /**
     * @return list<array{key: string, left: string, right: string, winsLeft: int, winsRight: int}>
     */
    public function h2hBreakdownRows(): array
    {
        $rows = $this->engine()->h2hRows();
        usort($rows, function (array $a, array $b): int {
            $ga = (int) ($a['winsLeft'] ?? 0) + (int) ($a['winsRight'] ?? 0);
            $gb = (int) ($b['winsLeft'] ?? 0) + (int) ($b['winsRight'] ?? 0);
            if ($gb !== $ga) {
                return $gb <=> $ga;
            }

            return strcmp((string) ($a['left'] ?? ''), (string) ($b['left'] ?? ''));
        });

        return $rows;
    }

    protected function primeH2hPicks(): void
    {
        $players = $this->game['players'] ?? [];
        if (! is_array($players)) {
            return;
        }
        $ids = [];
        foreach ($players as $p) {
            if (is_array($p) && ! empty($p['id'])) {
                $ids[] = (string) $p['id'];
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            $this->h2hPlayerA = '';
            $this->h2hPlayerB = '';

            return;
        }
        if ($this->h2hPlayerA === '' || ! in_array($this->h2hPlayerA, $ids, true)) {
            $this->h2hPlayerA = $ids[0];
        }
        if (
            count($ids) >= 2
            && (
                $this->h2hPlayerB === ''
                || ! in_array($this->h2hPlayerB, $ids, true)
                || $this->h2hPlayerB === $this->h2hPlayerA
            )
        ) {
            $other = null;
            foreach ($ids as $id) {
                if ($id !== $this->h2hPlayerA) {
                    $other = $id;
                    break;
                }
            }
            $this->h2hPlayerB = $other ?? $ids[1];
        }
        if (count($ids) === 1) {
            $this->h2hPlayerB = '';
        }
    }

    public function engine(): Engine
    {
        return new Engine($this->game);
    }

    public function requestTogglePlayerBreak(string $playerId): void
    {
        $p = $this->engine()->playerById($playerId);
        if (! $p) {
            return;
        }
        $wantSkip = empty($p['skipShuffle']);
        $this->applyToggleBreakPayload($playerId, $wantSkip);
    }

    protected function applyToggleBreakPayload(string $playerId, bool $wantSkip): void
    {
        $this->toggleBreakError = '';
        $this->toggleBreakSuccess = '';
        $this->toggleBreakBusyId = $playerId;
        try {
            $this->openPlayShare->refresh();
            $payload = $this->openPlayShare->payload;
            if (! is_array($payload)) {
                $this->toggleBreakError = 'Invalid session.';

                return;
            }
            [$next, $err] = GameQShareToggleBreakPayload::apply($payload, $playerId, $wantSkip);
            if ($err !== null) {
                $this->toggleBreakError = $err;

                return;
            }
            $playerCount = is_array($next['players'] ?? null)
                ? count($next['players'])
                : 0;
            if ($playerCount > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
                $this->toggleBreakError = sprintf(
                    'GameQ allows at most %d players per session.',
                    OpenPlaySession::MAX_PLAYERS_PER_SESSION,
                );

                return;
            }
            $encoded = json_encode($next);
            if ($encoded === false || strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
                $this->toggleBreakError = 'Payload too large.';

                return;
            }
            $this->openPlayShare->update(['payload' => $next]);
            $this->refreshWatch();
            $this->toggleBreakSuccess = $wantSkip
                ? 'Take a break is on — the host queue is updated.'
                : 'You’re back in rotation — the host queue is updated.';
        } catch (\Throwable) {
            $this->toggleBreakError = 'Could not update. Try again.';
        } finally {
            $this->toggleBreakBusyId = null;
        }
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

    /**
     * @return list<array<string, mixed>>
     */
    public function rosterPlayers(): array
    {
        $out = [];
        foreach ($this->game['players'] ?? [] as $p) {
            if (is_array($p) && empty($p['disabled'])) {
                $out[] = $p;
            }
        }

        return $out;
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
