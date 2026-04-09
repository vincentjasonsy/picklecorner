<?php

namespace App\Livewire;

use App\GameQ\Engine;
use App\Models\OpenPlaySession;
use App\Models\OpenPlayShare;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

    public bool $peopleModalOpen = false;

    public string $activeTab = 'play';

    /** Tab inside Standings & log modal only (avoid clashing with session Play / Share tabs). */
    public string $modalTab = 'standings';

    public bool $shareBusy = false;

    public bool $shareCopied = false;

    /** Roster add/edit modal (same pattern as Standings & log). */
    public bool $rosterModalOpen = false;

    /** Which court’s “Edit lineup” panel is open (keeps open across Livewire updates; raw details/ summary alone does not). */
    public ?int $courtLineupEditorOpen = null;

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
        $this->hydrateCanonicalShareUuid();
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
            $json = json_encode($e->sharePayload(), JSON_THROW_ON_ERROR);
            if (in_array('xxh128', hash_algos(), true)) {
                return hash('xxh128', $json);
            }

            return hash('sha256', $json);
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

    protected function accountSessionCacheKey(string|int $userId, ?int $linkedId): string
    {
        $suffix = $linkedId !== null ? (string) $linkedId : 'new';

        return 'gameq_account_payload_hash_'.$userId.'_'.$suffix;
    }

    protected function rememberAccountSessionPayloadHash(string $hash): void
    {
        if (! Auth::check()) {
            return;
        }
        /** @var User $user */
        $user = Auth::user();
        $linked = $this->state['linkedOpenPlaySessionId'] ?? null;
        $lid = is_numeric($linked) ? (int) $linked : null;
        session([$this->accountSessionCacheKey($user->getKey(), $lid) => $hash]);
    }

    protected function lastSavedAccountSessionHash(): ?string
    {
        if (! Auth::check()) {
            return null;
        }
        /** @var User $user */
        $user = Auth::user();
        $linked = $this->state['linkedOpenPlaySessionId'] ?? null;
        $lid = is_numeric($linked) ? (int) $linked : null;

        $v = session($this->accountSessionCacheKey($user->getKey(), $lid));

        return is_string($v) ? $v : null;
    }

    protected function sessionHasPersistableActivity(): bool
    {
        $players = $this->state['players'] ?? [];
        if (is_array($players) && count($players) > 0) {
            return true;
        }
        $queue = $this->state['queue'] ?? [];
        if (is_array($queue) && count($queue) > 0) {
            return true;
        }
        $completed = $this->state['completedMatches'] ?? [];
        if (is_array($completed) && count($completed) > 0) {
            return true;
        }
        foreach ($this->state['courts'] ?? [] as $c) {
            if ($c !== null && is_array($c)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Persist GameQ snapshot to open_play_sessions (create or update), deduped like live-share sync.
     */
    protected function syncAccountSessionIfNeeded(): void
    {
        if (! Auth::check()) {
            return;
        }
        if (($this->state['uiPhase'] ?? '') !== 'session') {
            return;
        }

        $hash = $this->sharePayloadHashForCurrentState();
        if ($hash === null) {
            return;
        }

        $linked = $this->state['linkedOpenPlaySessionId'] ?? null;
        $linkedId = is_numeric($linked) ? (int) $linked : null;

        if ($linkedId === null && ! $this->sessionHasPersistableActivity()) {
            return;
        }

        if ($hash === $this->lastSavedAccountSessionHash()) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        try {
            $e = new Engine($this->state);
            $data = $e->sharePayload();
            $players = $data['players'] ?? [];
            if (! is_array($players) || count($players) > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
                return;
            }
            $encoded = json_encode($data);
            if ($encoded === false || strlen($encoded) > 512000) {
                return;
            }

            if ($linkedId !== null) {
                $session = $user->openPlaySessions()->whereKey($linkedId)->first();
                if (! $session instanceof OpenPlaySession) {
                    $this->state['linkedOpenPlaySessionId'] = null;
                    $this->persist();

                    return;
                }
                $titleFromState = mb_substr(trim((string) ($this->state['sessionTitle'] ?? '')), 0, 120);
                if ($titleFromState !== '') {
                    $session->title = $titleFromState;
                }
                $session->payload = $data;
                $session->save();
                $this->rememberAccountSessionPayloadHash($hash);
                $this->refreshHistorySessions();

                return;
            }

            $quota = OpenPlaySession::quotaForUser($user);
            if ($quota['remaining'] <= 0) {
                Log::info('GameQ account autosave skipped: monthly quota exhausted', ['user_id' => $user->getKey()]);

                return;
            }

            $fromSession = mb_substr(trim((string) ($this->state['sessionTitle'] ?? '')), 0, 120);
            $title = $fromSession !== '' ? $fromSession : 'Hosted · '.now()->timezone(config('app.timezone'))->format('M j, Y g:i a');
            $session = $user->openPlaySessions()->create([
                'title' => $title,
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
            $this->historyQuota = OpenPlaySession::quotaForUser($user);
            $this->persist();
            $this->rememberAccountSessionPayloadHash($hash);
            $this->refreshHistorySessions();
        } catch (\Throwable $ex) {
            Log::warning('GameQ account autosave failed', [
                'user_id' => $user->getKey(),
                'message' => $ex->getMessage(),
            ]);
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
        $this->rosterModalOpen = false;
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

    public function toggleCourtLineupEditor(int $courtIndex): void
    {
        if ($this->courtLineupEditorOpen === $courtIndex) {
            $this->courtLineupEditorOpen = null;

            return;
        }
        $this->courtLineupEditorOpen = $courtIndex;
        $this->initCourtLineupDraft($courtIndex);
    }

    public function applyCourtLineupDraft(int $courtIndex): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->applyCourtLineupDraft($courtIndex, $now));
        if (($this->state['lineupEditError'] ?? '') === '' && $this->courtLineupEditorOpen === $courtIndex) {
            $this->courtLineupEditorOpen = null;
        }
    }

    public function completeMatch(int $i): void
    {
        $now = (int) round(microtime(true) * 1000);
        $this->withEngine(fn (Engine $e) => $e->completeMatch($i, $now));
    }

    /** Recompute W–L and head-to-head after editing scores in the completed log. */
    public function syncStandingsFromCompletedLog(): void
    {
        $this->withEngine(fn (Engine $e) => $e->rebuildStatsFromCompletedMatches());
    }

    /** Remove a completed match from the log so it no longer affects standings. */
    public function removeCompletedMatch(int $index): void
    {
        $this->withEngine(fn (Engine $e) => $e->removeCompletedMatchAtIndex($index));
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
        $this->syncAccountSessionIfNeeded();
    }

    public function resetSession(): void
    {
        $this->withEngine(fn (Engine $e) => $e->resetSession());
    }

    public function fullReset(): void
    {
        $this->withEngine(fn (Engine $e) => $e->fullResetState());
        $this->peopleModalOpen = false;
        $this->rosterModalOpen = false;
    }

    public function endHostingSession(): void
    {
        $this->peopleModalOpen = false;
        $this->rosterModalOpen = false;
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
        if ($uuid === '') {
            return;
        }
        $share = OpenPlayShare::query()->where('uuid', $uuid)->first();
        $secret = (string) ($this->state['shareSecret'] ?? '');
        $userId = Auth::id();
        $secretOk = $secret !== '' && $share && Hash::check($secret, $share->secret_hash);
        $ownerOk = $userId && $share && $share->user_id !== null
            && (string) $share->user_id === (string) $userId;
        if ($share && ($secretOk || $ownerOk)) {
            $share->delete();
        }
        $this->forgetSentSharePayloadHash($uuid);
        $this->state['shareUuid'] = '';
        $this->state['shareSecret'] = '';
        $this->state['shareSyncEnabled'] = false;
        $this->state['shareError'] = '';
        $this->persist();
    }

    /** Restore public watch URL from the signed-in user’s single live share (host key may still be empty). */
    protected function hydrateCanonicalShareUuid(): void
    {
        if (! Auth::check()) {
            return;
        }
        if (! empty($this->state['shareUuid'])) {
            return;
        }
        $row = OpenPlayShare::query()->where('user_id', Auth::id())->orderBy('id')->first();
        if ($row) {
            $this->state['shareUuid'] = $row->uuid;
            $this->persist();
        }
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
                $this->state['shareError'] = 'Share key was rejected. Reconnect as host (watch URL stays the same).';
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
            if (! Auth::check()) {
                $this->state['shareError'] = 'Sign in to enable live watch.';
                $this->persist();

                return;
            }
            /** @var User $user */
            $user = Auth::user();
            if (! $user instanceof User) {
                $this->state['shareError'] = 'Sign in to enable live watch.';
                $this->persist();

                return;
            }
            $e = new Engine($this->state);
            $payload = $e->sharePayload();
            $players = $payload['players'] ?? [];
            if (! is_array($players) || count($players) > OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
                $this->state['shareError'] = 'Invalid roster.';

                return;
            }
            $sid = $this->state['linkedOpenPlaySessionId'] ?? null;
            $sessionFk = null;
            if ($sid !== null && is_numeric($sid)) {
                $sid = (int) $sid;
                if ($user->openPlaySessions()->whereKey($sid)->exists()) {
                    $sessionFk = $sid;
                }
            }

            $uuid = (string) ($this->state['shareUuid'] ?? '');
            $clientSecret = (string) ($this->state['shareSecret'] ?? '');

            if ($uuid !== '' && $clientSecret !== '') {
                $share = OpenPlayShare::query()->where('uuid', $uuid)->first();
                if ($share && Hash::check($clientSecret, $share->secret_hash)) {
                    $legacyNoOwner = $share->user_id === null;
                    $ownerOk = $legacyNoOwner || (string) $share->user_id === (string) $user->getKey();
                    if ($ownerOk) {
                        $share->forceFill([
                            'payload' => $payload,
                            'open_play_session_id' => $sessionFk ?? $share->open_play_session_id,
                            'user_id' => $share->user_id ?? $user->getKey(),
                        ])->save();
                        $this->state['shareUuid'] = $share->uuid;
                        $this->state['shareSecret'] = $clientSecret;
                        $this->state['shareSyncEnabled'] = true;
                        $this->persist();
                        $this->rememberSentSharePayloadHash();

                        return;
                    }
                }
            }

            $canonical = OpenPlayShare::query()
                ->where('user_id', $user->getKey())
                ->orderBy('id')
                ->first();
            if ($canonical) {
                $newSecret = Str::random(48);
                $canonical->forceFill([
                    'payload' => $payload,
                    'secret_hash' => Hash::make($newSecret),
                    'open_play_session_id' => $sessionFk ?? $canonical->open_play_session_id,
                ])->save();
                $this->state['shareUuid'] = $canonical->uuid;
                $this->state['shareSecret'] = $newSecret;
                $this->state['shareSyncEnabled'] = true;
                $this->persist();
                $this->rememberSentSharePayloadHash();

                return;
            }

            $newSecret = Str::random(48);
            $share = OpenPlayShare::query()->create([
                'user_id' => $user->getKey(),
                'open_play_session_id' => $sessionFk,
                'uuid' => (string) Str::uuid(),
                'secret_hash' => Hash::make($newSecret),
                'payload' => $payload,
            ]);
            $this->state['shareUuid'] = $share->uuid;
            $this->state['shareSecret'] = $newSecret;
            $this->state['shareSyncEnabled'] = true;
            $this->persist();
            $this->rememberSentSharePayloadHash();
        } catch (\Throwable $ex) {
            Log::warning('OpenPlayOrganizer::startSharing failed', [
                'message' => $ex->getMessage(),
                'exception' => $ex::class,
            ]);
            $this->state['shareError'] = 'Could not enable live share.';
            if (config('app.debug')) {
                $this->state['shareError'] .= ' '.$ex->getMessage();
            }
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
            $this->state['sessionTitle'] = mb_substr(trim((string) $session->title), 0, 120);
            $live = OpenPlayShare::query()
                ->where('user_id', $user->getKey())
                ->where('open_play_session_id', $id)
                ->orderByDesc('id')
                ->first()
                ?? OpenPlayShare::query()->where('user_id', $user->getKey())->orderBy('id')->first();
            if ($live) {
                $this->state['shareUuid'] = $live->uuid;
            }
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

    /**
     * Pull take-a-break + queue from the stored live share when players update from the watch page.
     */
    public function pullLiveShareBreakSync(): void
    {
        if (
            ($this->state['uiPhase'] ?? '') !== 'session'
            || empty($this->state['shareSyncEnabled'])
            || empty($this->state['shareUuid'])
            || empty($this->state['shareSecret'])
        ) {
            return;
        }
        $uuid = (string) $this->state['shareUuid'];
        $secret = (string) $this->state['shareSecret'];
        $share = OpenPlayShare::query()->where('uuid', $uuid)->first();
        if (! $share || ! Hash::check($secret, $share->secret_hash)) {
            return;
        }
        $payload = $share->payload;
        if (! is_array($payload)) {
            return;
        }
        $e = new Engine($this->state);
        if (! $e->applyRemoteWatchBreakSync($payload)) {
            return;
        }
        $this->state = $e->toArray();
        $this->takeBreakNotice = 'Take a break and queue were updated from the live page.';
        $this->persist();
        $this->rememberSentSharePayloadHash();
    }

    public function render(): View
    {
        return view('livewire.open-play-organizer');
    }
}
