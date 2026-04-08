<?php

namespace App\Livewire;

use App\GameQ\Engine;
use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts::tool-focus')]
#[Title('GameQ (Beta)')]
class OpenPlayOrganizer extends Component
{
    use WithFileUploads;

    /** @var array<string, mixed> */
    public array $state = [];

    /** @var list<array<string, mixed>> */
    public array $historySessions = [];

    /** @var array<string, mixed>|null */
    public ?array $historyQuota = null;

    public string $historyError = '';

    public bool $historyBusy = false;

    public string $historySaveTitle = '';

    public bool $peopleModalOpen = false;

    public string $activeTab = 'play';

    /** Tab inside Standings & log modal only (avoid clashing with session Play / Share tabs). */
    public string $modalTab = 'standings';

    public bool $shareBusy = false;

    public bool $shareCopied = false;

    /** Show roster add/edit UI (Livewire state survives wire:poll). */
    public bool $rosterSettingsOpen = false;

    /** Shown after take-a-break changes from the roster or queue (cleared on next toggle). */
    public string $takeBreakNotice = '';

    public $importFile = null;

    public function mount(): void
    {
        $raw = session('gameq_organizer_v2', []);
        $engine = new Engine(is_array($raw) ? $raw : []);
        $this->state = $engine->toArray();

        if (! in_array($this->state['uiPhase'] ?? '', ['list', 'setup', 'session'], true)) {
            $hasActivity = count($this->state['players'] ?? []) > 0
                || count(array_filter($this->state['courts'] ?? [])) > 0
                || count($this->state['queue'] ?? []) > 0
                || count($this->state['completedMatches'] ?? []) > 0;
            $this->state['uiPhase'] = $hasActivity ? 'session' : 'list';
        }
        $step = (int) ($this->state['setupStep'] ?? 1);
        $this->state['setupStep'] = $step >= 1 && $step <= 3 ? $step : 1;

        $e = new Engine($this->state);
        $e->ensureCourtSlots();
        $this->state = $e->toArray();
        $this->persist();

        $this->refreshHistorySessions();
        $this->maybeLoadFromQueryParam();
    }

    protected function persist(): void
    {
        session(['gameq_organizer_v2' => $this->state]);
    }

    /** Stable hash of the JSON we expose to the live watch page (not full session state). */
    protected function sharePayloadHashForCurrentState(): ?string
    {
        try {
            $e = new Engine($this->state);

            return hash('xxh128', json_encode($e->sharePayload(), JSON_THROW_ON_ERROR));
        } catch (\Throwable) {
            return null;
        }
    }

    protected function rememberSentSharePayloadHash(): void
    {
        $uuid = (string) ($this->state['shareUuid'] ?? '');
        if ($uuid === '') {
            return;
        }
        $hash = $this->sharePayloadHashForCurrentState();
        if ($hash !== null) {
            session(['gameq_share_sent_hash_'.$uuid => $hash]);
        }
    }

    protected function forgetSentSharePayloadHash(?string $uuid = null): void
    {
        $uuid ??= (string) ($this->state['shareUuid'] ?? '');
        if ($uuid !== '') {
            session()->forget('gameq_share_sent_hash_'.$uuid);
        }
    }

    /**
     * Keep OpenPlayShare.payload aligned with the host whenever the public snapshot changes.
     * Hash-guard skips redundant writes (e.g. wire:poll refreshTimers with unchanged state).
     */
    protected function syncSharePayloadToServerIfNeeded(): void
    {
        if (
            empty($this->state['shareSyncEnabled'])
            || empty($this->state['shareUuid'])
            || empty($this->state['shareSecret'])
        ) {
            return;
        }
        $uuid = (string) $this->state['shareUuid'];
        $hash = $this->sharePayloadHashForCurrentState();
        if ($hash === null) {
            return;
        }
        if (session('gameq_share_sent_hash_'.$uuid) === $hash) {
            return;
        }
        $this->pushShareToServer();
    }

    protected function withEngine(callable $fn): void
    {
        $engine = new Engine($this->state);
        $fn($engine);
        $this->state = $engine->toArray();
        $this->persist();
    }

    public function refreshHistorySessions(): void
    {
        $this->historyError = '';
        try {
            /** @var User $user */
            $user = Auth::user();
            $rows = $user->openPlaySessions()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'title', 'created_at', 'updated_at']);
            $this->historySessions = $rows->map(fn (OpenPlaySession $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'created_at' => $s->created_at->toIso8601String(),
                'updated_at' => $s->updated_at->toIso8601String(),
            ])->all();
            $this->historyQuota = OpenPlaySession::quotaForUser($user);
        } catch (\Throwable) {
            $this->historyError = 'Could not load your session history.';
        }
    }

    public function maybeLoadFromQueryParam(): void
    {
        $loadId = request()->query('load');
        if (! is_string($loadId) || ! preg_match('/^\d+$/', $loadId)) {
            return;
        }
        $this->loadHistorySession((int) $loadId);
        $this->dispatch('gameq-strip-load-query');
    }

    public function startOpenPlayWizard(): void
    {
        $this->withEngine(fn (Engine $e) => $e->startOpenPlayWizardState([]));
    }

    public function goToSessionList(): void
    {
        $this->peopleModalOpen = false;
        $this->modalTab = 'standings';
        $this->withEngine(fn (Engine $e) => $e->goToSessionListState());
        $this->refreshHistorySessions();
    }

    public function setupGoBack(): void
    {
        $this->withEngine(function (Engine $e) {
            $e->setupGoBack();
        });
    }

    public function setupGoNext(): void
    {
        $this->withEngine(fn (Engine $e) => $e->setupGoNext());
    }

    public function finishSetup(): void
    {
        $this->withEngine(fn (Engine $e) => $e->finishSetup());
    }

    public function fillCourts(): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->fillCourts($now));
    }

    public function pauseCourtTimer(int $i): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->pauseCourtTimer($i, $now));
    }

    public function resumeCourtTimer(int $i): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->resumeCourtTimer($i, $now));
    }

    public function stopCourtTimer(int $i): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->stopCourtTimer($i, $now));
    }

    public function startCourtTimer(int $i): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->startCourtTimer($i, $now));
    }

    public function bumpCourtRemainingMinutes(int $i, int|float $delta): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->bumpCourtRemainingMinutes($i, $delta, $now));
    }

    public function applyCourtRemainingMinutes(int $i): void
    {
        $this->withEngine(fn (Engine $e) => $e->applyCourtRemainingMinutes($i));
    }

    public function initCourtLineupDraft(int $courtIndex): void
    {
        $this->withEngine(fn (Engine $e) => $e->initCourtLineupDraft($courtIndex));
    }

    public function applyCourtLineupDraft(int $courtIndex): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->applyCourtLineupDraft($courtIndex, $now));
    }

    public function completeMatch(int $i): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->completeMatch($i, $now));
    }

    public function clearCourt(int $i): void
    {
        $this->withEngine(fn (Engine $e) => $e->clearCourt($i));
    }

    public function addPlayer(): void
    {
        $this->withEngine(fn (Engine $e) => $e->addPlayer());
    }

    public function cleanupBulkPlayerList(): void
    {
        $this->withEngine(fn (Engine $e) => $e->cleanupBulkPlayerList());
    }

    public function addPlayersFromBulk(): void
    {
        $this->withEngine(fn (Engine $e) => $e->addPlayersFromBulk());
    }

    public function removePlayer(string $id): void
    {
        $this->withEngine(fn (Engine $e) => $e->removePlayer($id));
    }

    public function toggleDisabled(string $id): void
    {
        $this->withEngine(fn (Engine $e) => $e->toggleDisabled($id));
    }

    public function toggleSkipShuffle(string $id): void
    {
        $this->takeBreakNotice = '';
        $playerName = '';
        $wasSkipping = false;
        foreach ($this->state['players'] ?? [] as $p) {
            if (! is_array($p) || (string) ($p['id'] ?? '') !== $id) {
                continue;
            }
            $wasSkipping = ! empty($p['skipShuffle']);
            $playerName = trim((string) ($p['name'] ?? ''));
            break;
        }
        $this->withEngine(fn (Engine $e) => $e->toggleSkipShuffle($id));
        $nowSkipping = ! $wasSkipping;
        $this->takeBreakNotice = $this->takeBreakNoticeMessage($playerName, $nowSkipping);
    }

    public function setSkipShuffleFromInput(string $id, bool $checked): void
    {
        $this->takeBreakNotice = '';
        $playerName = '';
        foreach ($this->state['players'] ?? [] as $p) {
            if (! is_array($p) || (string) ($p['id'] ?? '') !== $id) {
                continue;
            }
            $playerName = trim((string) ($p['name'] ?? ''));
            break;
        }
        $this->withEngine(fn (Engine $e) => $e->setSkipShuffleForPlayer($id, $checked));
        $this->takeBreakNotice = $this->takeBreakNoticeMessage($playerName, $checked);
    }

    protected function takeBreakNoticeMessage(string $playerName, bool $onBreak): string
    {
        $who = $playerName !== '' ? $playerName : 'Player';

        return $onBreak
            ? "{$who} is on a break — skipped for Fill courts until cleared."
            : "{$who} is back in rotation for Fill courts.";
    }

    public function moveQueueUp(int $i): void
    {
        $this->withEngine(fn (Engine $e) => $e->moveQueueUp($i));
    }

    public function moveQueueDown(int $i): void
    {
        $this->withEngine(fn (Engine $e) => $e->moveQueueDown($i));
    }

    public function removeFromQueue(int $i): void
    {
        $this->withEngine(fn (Engine $e) => $e->removeFromQueue($i));
    }

    public function courtsCountChanged(): void
    {
        $this->withEngine(fn (Engine $e) => $e->courtsCountChanged());
    }

    public function syncQueueFromIdle(): void
    {
        $this->withEngine(fn (Engine $e) => $e->syncQueueFromIdle());
    }

    public function dehydrate(): void
    {
        $this->persist();
        $this->syncSharePayloadToServerIfNeeded();
    }

    public function resetSession(): void
    {
        $this->withEngine(fn (Engine $e) => $e->resetSession());
    }

    public function fullReset(): void
    {
        $this->withEngine(fn (Engine $e) => $e->fullResetState());
        $this->peopleModalOpen = false;
    }

    public function endHostingSession(): void
    {
        $this->peopleModalOpen = false;
        $this->modalTab = 'standings';
        $this->performShareRevoke();
        $this->withEngine(function (Engine $e) {
            $e->clearPlayState();
            $e->goToSessionListState();
        });
        $this->refreshHistorySessions();
    }

    protected function performShareRevoke(): void
    {
        $uuid = (string) ($this->state['shareUuid'] ?? '');
        $secret = (string) ($this->state['shareSecret'] ?? '');
        if ($uuid === '' || $secret === '') {
            return;
        }
        $share = OpenPlayShare::query()->where('uuid', $uuid)->first();
        if ($share && Hash::check($secret, $share->secret_hash)) {
            $share->delete();
        }
        $this->forgetSentSharePayloadHash($uuid);
        $this->state['shareUuid'] = '';
        $this->state['shareSecret'] = '';
        $this->state['shareSyncEnabled'] = false;
        $this->state['shareError'] = '';
        $this->persist();
    }

    public function revokeSharing(): void
    {
        $this->performShareRevoke();
    }

    public function pauseSharing(): void
    {
        $this->state['shareSyncEnabled'] = false;
        $this->persist();
    }

    public function resumeSharing(): void
    {
        if (! empty($this->state['shareUuid']) && ! empty($this->state['shareSecret'])) {
            $this->state['shareSyncEnabled'] = true;
            $this->persist();
        }
    }

    public function pushShareToServer(): void
    {
        if (
            empty($this->state['shareSyncEnabled'])
            || empty($this->state['shareUuid'])
            || empty($this->state['shareSecret'])
        ) {
            return;
        }
        $e = new Engine($this->state);
        $payload = $e->sharePayload();
        $uuid = (string) $this->state['shareUuid'];
        $secret = (string) $this->state['shareSecret'];
        try {
            $share = OpenPlayShare::query()->where('uuid', $uuid)->first();
            if (! $share || ! Hash::check($secret, $share->secret_hash)) {
                $this->state['shareError'] = 'Share key was rejected. Create a new link.';
                $this->state['shareSyncEnabled'] = false;
                $this->persist();

                return;
            }
            $encoded = json_encode($payload);
            if ($encoded === false || strlen($encoded) > 512000) {
                $this->state['shareError'] = 'Payload too large.';

                return;
            }
            $share->update(['payload' => $payload]);
            $this->state['shareError'] = '';
            $this->rememberSentSharePayloadHash();
        } catch (\Throwable) {
            $this->state['shareError'] = 'Could not sync (try again when online).';
        }
        $this->persist();
    }

    public function saveRoster(): void
    {
        $this->persist();
        $this->dispatch('gameq-roster-saved');
    }

    public function startSharing(): void
    {
        $this->shareBusy = true;
        $this->state['shareError'] = '';
        try {
            $e = new Engine($this->state);
            $payload = $e->sharePayload();
            $players = $payload['players'] ?? [];
            if (! is_array($players) || count($players) > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
                $this->state['shareError'] = 'Invalid roster.';

                return;
            }
            $secret = Str::random(48);
            $sid = $this->state['linkedOpenPlaySessionId'] ?? null;
            $sessionFk = null;
            if ($sid !== null && is_numeric($sid)) {
                $sid = (int) $sid;
                if (Auth::user()->openPlaySessions()->whereKey($sid)->exists()) {
                    $sessionFk = $sid;
                }
            }
            $share = OpenPlayShare::query()->create([
                'open_play_session_id' => $sessionFk,
                'uuid' => (string) Str::uuid(),
                'secret_hash' => Hash::make($secret),
                'payload' => $payload,
            ]);
            $this->state['shareUuid'] = $share->uuid;
            $this->state['shareSecret'] = $secret;
            $this->state['shareSyncEnabled'] = true;
            $this->persist();
            $this->rememberSentSharePayloadHash();
        } catch (\Throwable $ex) {
            $this->state['shareError'] = 'Could not create share link.';
            $this->persist();
        } finally {
            $this->shareBusy = false;
        }
    }

    public function copyShareLink(): void
    {
        $url = $this->shareWatchUrl();
        if ($url === '') {
            return;
        }
        $this->js('navigator.clipboard.writeText('.json_encode($url, JSON_THROW_ON_ERROR).')');
        $this->shareCopied = true;
    }

    public function shareWatchUrl(): string
    {
        $uuid = (string) ($this->state['shareUuid'] ?? '');
        if ($uuid === '') {
            return '';
        }

        return rtrim((string) url('/open-play/watch'), '/').'/'.$uuid;
    }

    public function saveToHistory(): void
    {
        $this->historyBusy = true;
        $this->historyError = '';
        try {
            /** @var User $user */
            $user = Auth::user();
            $e = new Engine($this->state);
            $data = $e->sharePayload();
            $quota = OpenPlaySession::quotaForUser($user);
            if ($quota['remaining'] <= 0) {
                $this->historyError = sprintf(
                    'You can save up to %d GameQ sessions to your account each calendar month.',
                    OpenPlaySession::MONTHLY_SAVE_LIMIT
                );
                $this->historyQuota = $quota;

                return;
            }
            $title = trim($this->historySaveTitle) !== '' ? trim($this->historySaveTitle) : null;
            $session = $user->openPlaySessions()->create([
                'title' => $title ?? 'Hosted · '.now()->timezone(config('app.timezone'))->format('M j, Y g:i a'),
                'payload' => $data,
            ]);
            $uuid = (string) ($this->state['shareUuid'] ?? '');
            $secret = (string) ($this->state['shareSecret'] ?? '');
            if ($uuid !== '' && $secret !== '') {
                $share = OpenPlayShare::query()->where('uuid', $uuid)->first();
                if ($share && Hash::check($secret, $share->secret_hash)) {
                    $share->forceFill(['open_play_session_id' => $session->id])->save();
                }
            }
            $this->state['linkedOpenPlaySessionId'] = $session->id;
            $this->historySaveTitle = '';
            $this->historyQuota = OpenPlaySession::quotaForUser($user);
            $this->persist();
            $this->refreshHistorySessions();
        } catch (\Throwable) {
            $this->historyError = 'Could not save (check your connection and try again).';
        } finally {
            $this->historyBusy = false;
        }
    }

    public function loadHistorySession(int $id): void
    {
        $this->historyError = '';
        try {
            /** @var User $user */
            $user = Auth::user();
            $session = $user->openPlaySessions()->whereKey($id)->firstOrFail();
            $payload = $session->payload;
            if (! is_array($payload)) {
                $this->historyError = 'Invalid session data.';

                return;
            }
            $this->withEngine(fn (Engine $e) => $e->applyImportedPayload($payload, ['clearShare' => true]));
            $this->state['linkedOpenPlaySessionId'] = $id;
            $this->state['importError'] = '';
            $this->activeTab = 'play';
            $this->state['uiPhase'] = 'session';
            $this->persist();
        } catch (\Throwable) {
            $this->historyError = 'Could not load that session.';
        }
    }

    public function deleteHistorySession(int $id): void
    {
        $this->historyError = '';
        try {
            /** @var User $user */
            $user = Auth::user();
            $session = $user->openPlaySessions()->whereKey($id)->firstOrFail();
            $session->delete();
            if (
                isset($this->state['linkedOpenPlaySessionId'])
                && (int) $this->state['linkedOpenPlaySessionId'] === $id
            ) {
                $this->forgetSentSharePayloadHash((string) ($this->state['shareUuid'] ?? ''));
                $this->state['linkedOpenPlaySessionId'] = null;
                $this->state['shareUuid'] = '';
                $this->state['shareSecret'] = '';
                $this->state['shareSyncEnabled'] = false;
                $this->state['shareError'] = '';
                $this->persist();
            }
            $this->refreshHistorySessions();
        } catch (\Throwable) {
            $this->historyError = 'Could not remove that session.';
        }
    }

    public function exportJson()
    {
        $e = new Engine($this->state);

        return response()->streamDownload(function () use ($e) {
            echo json_encode($e->sharePayload(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }, 'gameq-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    public function importJson(): void
    {
        $this->validate([
            'importFile' => 'required|file|max:512|mimes:json,txt',
        ]);
        /** @var TemporaryUploadedFile $file */
        $file = $this->importFile;
        try {
            $raw = $file->get();
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                $this->state['importError'] = 'Invalid JSON file.';
                $this->persist();

                return;
            }
            $this->withEngine(fn (Engine $e) => $e->applyImportedPayload($data, ['clearShare' => true]));
            $this->state['importError'] = '';
            $this->persist();
        } catch (\Throwable) {
            $this->state['importError'] = 'Invalid JSON file.';
            $this->persist();
        }
        $this->importFile = null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pairH2hSummary(?string $a, ?string $b): ?array
    {
        $e = new Engine($this->state);

        return $e->pairH2hSummary($a, $b);
    }

    public function engine(): Engine
    {
        return new Engine($this->state);
    }

    public function courtRemainingDisplay(?array $court): string
    {
        $e = new Engine($this->state);
        $sec = $e->remainingSeconds($court);

        return $e->formatCountdown($sec);
    }

    public function refreshTimers(): void
    {
        // no-op: triggers re-render for countdown
    }

    public function render(): View
    {
        return view('livewire.open-play-organizer');
    }
}
