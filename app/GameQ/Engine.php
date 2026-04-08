<?php

namespace App\GameQ;

use App\Models\OpenPlaySession;

/**
 * GameQ session engine — all matching / queue / court rules live here (host + share payload).
 *
 * @phpstan-type PlayerRow array{id: string, name: string, level: int, wins: int, losses: int, disabled: bool, skipShuffle: bool, teamId: string}
 */
class Engine
{
    /** @var array<string, mixed> */
    private array $state;

    /**
     * @param  array<string, mixed>  $state
     */
    public function __construct(array $state)
    {
        $this->state = self::normalizeState($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->cloneState($this->state);
    }

    /**
     * Default snapshot matching JS defaultState() (including linkedOpenPlaySessionId).
     *
     * @return array<string, mixed>
     */
    public static function defaultState(): array
    {
        return [
            'mode' => 'singles',
            'shuffleMethod' => 'random',
            'courtsCount' => 2,
            'timeLimitMinutes' => 0,
            'players' => [],
            'queue' => [],
            'courts' => [],
            'completedMatches' => [],
            'h2h' => [],
            'shareUuid' => '',
            'shareSecret' => '',
            'shareSyncEnabled' => false,
            'linkedOpenPlaySessionId' => null,
            'uiPhase' => 'list',
            'setupStep' => 1,
            'scoreDraft' => [],
            'courtRemainingInput' => [],
            'courtLineupDraft' => [],
            'lineupEditError' => '',
            'importError' => '',
            'newName' => '',
            'newLevel' => 3,
            'newTeamId' => '',
            'bulkPlayerList' => '',
            'bulkAddFeedback' => '',
            'h2hPlayerA' => '',
            'h2hPlayerB' => '',
            'shareError' => '',
        ];
    }

    /**
     * Merge and normalize arbitrary input like loadState / applyImportedPayload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeState(array $data): array
    {
        $merged = array_merge(self::defaultState(), $data);

        $merged['players'] = isset($merged['players']) && is_array($merged['players']) ? array_values($merged['players']) : [];
        $merged['queue'] = isset($merged['queue']) && is_array($merged['queue']) ? array_values($merged['queue']) : [];
        $merged['courts'] = isset($merged['courts']) && is_array($merged['courts']) ? array_values($merged['courts']) : [];
        $merged['completedMatches'] = isset($merged['completedMatches']) && is_array($merged['completedMatches'])
            ? array_values($merged['completedMatches'])
            : [];
        $merged['h2h'] = isset($merged['h2h']) && is_array($merged['h2h']) ? $merged['h2h'] : [];
        $merged['shareUuid'] = isset($merged['shareUuid']) && is_string($merged['shareUuid']) ? $merged['shareUuid'] : '';
        $merged['shareSecret'] = isset($merged['shareSecret']) && is_string($merged['shareSecret']) ? $merged['shareSecret'] : '';
        $merged['shareSyncEnabled'] = ! empty($merged['shareSyncEnabled']);
        $v = $merged['linkedOpenPlaySessionId'] ?? null;
        if ($v === null || $v === '') {
            $merged['linkedOpenPlaySessionId'] = null;
        } else {
            $n = is_numeric($v) ? (int) $v : null;
            $merged['linkedOpenPlaySessionId'] = ($n !== null && (string) $n === (string) $v) ? $n : null;
        }
        $merged['uiPhase'] = isset($merged['uiPhase']) && is_string($merged['uiPhase']) ? $merged['uiPhase'] : 'list';
        $setupStep = $merged['setupStep'] ?? 1;
        $merged['setupStep'] = is_numeric($setupStep) && (int) $setupStep >= 1 ? (int) $setupStep : 1;

        if (! isset($merged['scoreDraft']) || ! is_array($merged['scoreDraft'])) {
            $merged['scoreDraft'] = [];
        }
        if (! isset($merged['courtRemainingInput']) || ! is_array($merged['courtRemainingInput'])) {
            $merged['courtRemainingInput'] = [];
        }
        if (! isset($merged['courtLineupDraft']) || ! is_array($merged['courtLineupDraft'])) {
            $merged['courtLineupDraft'] = [];
        }

        foreach (['lineupEditError', 'importError', 'newName', 'newTeamId', 'bulkPlayerList', 'bulkAddFeedback', 'h2hPlayerA', 'h2hPlayerB', 'shareError'] as $sk) {
            if (! isset($merged[$sk]) || ! is_string($merged[$sk])) {
                $merged[$sk] = is_scalar($merged[$sk] ?? null) ? (string) ($merged[$sk] ?? '') : '';
            }
        }
        $nl = $merged['newLevel'] ?? 3;
        $merged['newLevel'] = is_numeric($nl) ? (int) $nl : 3;

        return self::cloneState($merged);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{clearShare?: bool}  $options
     */
    public function applyImportedPayload(array $data, array $options = []): void
    {
        $clearShare = ($options['clearShare'] ?? true) !== false;
        $merged = array_merge(self::defaultState(), $data);
        $this->state['mode'] = $merged['mode'] ?? 'singles';
        $this->state['shuffleMethod'] = $merged['shuffleMethod'] ?? 'random';
        $this->state['courtsCount'] = $merged['courtsCount'] ?? 2;
        $this->state['timeLimitMinutes'] = $merged['timeLimitMinutes'] ?? 0;
        $this->state['players'] = is_array($merged['players'] ?? null) ? array_values($merged['players']) : [];
        $this->normalizePlayers();
        $this->state['queue'] = is_array($merged['queue'] ?? null) ? array_values($merged['queue']) : [];
        $this->state['courts'] = is_array($merged['courts'] ?? null) ? array_values($merged['courts']) : [];
        $this->state['completedMatches'] = is_array($merged['completedMatches'] ?? null) ? array_values($merged['completedMatches']) : [];
        $this->state['h2h'] = is_array($merged['h2h'] ?? null) ? $merged['h2h'] : [];
        if ($clearShare) {
            $this->state['shareUuid'] = '';
            $this->state['shareSecret'] = '';
            $this->state['shareSyncEnabled'] = false;
            $this->state['linkedOpenPlaySessionId'] = null;
        }
        $this->ensureCourtSlots();
        $this->normalizePlayerCap();
        $this->state['scoreDraft'] = [];
        $this->state['uiPhase'] = 'session';
        $this->primeH2hPicks();
    }

    /**
     * Game payload only (matches JS sharePayload).
     *
     * @return array<string, mixed>
     */
    public function sharePayload(): array
    {
        return [
            'mode' => $this->state['mode'],
            'shuffleMethod' => $this->state['shuffleMethod'],
            'courtsCount' => $this->state['courtsCount'],
            'timeLimitMinutes' => $this->state['timeLimitMinutes'],
            'players' => $this->cloneState($this->state['players']),
            'queue' => $this->cloneState($this->state['queue']),
            'courts' => $this->cloneState($this->state['courts']),
            'completedMatches' => $this->cloneState($this->state['completedMatches']),
            'h2h' => $this->cloneState($this->state['h2h']),
        ];
    }

    /**
     * Full serialized state for persistence (matches JS serialize()).
     *
     * @return array<string, mixed>
     */
    public function serializeState(): array
    {
        return array_merge($this->sharePayload(), [
            'shareUuid' => $this->state['shareUuid'],
            'shareSecret' => $this->state['shareSecret'],
            'shareSyncEnabled' => $this->state['shareSyncEnabled'],
            'linkedOpenPlaySessionId' => $this->state['linkedOpenPlaySessionId'],
            'uiPhase' => $this->state['uiPhase'],
            'setupStep' => $this->state['setupStep'],
            'scoreDraft' => $this->cloneState($this->state['scoreDraft']),
            'courtRemainingInput' => $this->cloneState($this->state['courtRemainingInput']),
            'courtLineupDraft' => $this->cloneState($this->state['courtLineupDraft']),
            'lineupEditError' => $this->state['lineupEditError'],
            'importError' => $this->state['importError'],
            'newName' => $this->state['newName'],
            'newLevel' => $this->state['newLevel'],
            'newTeamId' => $this->state['newTeamId'],
            'bulkPlayerList' => $this->state['bulkPlayerList'],
            'bulkAddFeedback' => $this->state['bulkAddFeedback'],
            'h2hPlayerA' => $this->state['h2hPlayerA'],
            'h2hPlayerB' => $this->state['h2hPlayerB'],
            'shareError' => $this->state['shareError'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $preserve  Keys merged on top of default (e.g. history metadata).
     */
    public function startOpenPlayWizardState(array $preserve = []): void
    {
        $next = array_merge(self::defaultState(), $preserve);
        $next['importError'] = '';
        $next['shareError'] = '';
        $next['scoreDraft'] = [];
        $next['uiPhase'] = 'setup';
        $next['setupStep'] = 1;
        $next['bulkPlayerList'] = '';
        $next['bulkAddFeedback'] = '';
        $next['courtRemainingInput'] = [];
        $next['courtLineupDraft'] = [];
        $next['lineupEditError'] = '';
        $this->state = self::normalizeState($next);
        $this->ensureCourtSlots();
    }

    public function goToSessionListState(): void
    {
        $this->state['uiPhase'] = 'list';
        $this->state['setupStep'] = 1;
    }

    public function finishSetup(): void
    {
        $this->syncQueueFromIdle();
        $this->state['uiPhase'] = 'session';
        $this->state['setupStep'] = 1;
        $this->primeH2hPicks();
    }

    public function setupGoBack(): void
    {
        $step = (int) $this->state['setupStep'];
        if ($step > 1) {
            $this->state['setupStep'] = $step - 1;

            return;
        }
        $this->goToSessionListState();
    }

    public function setupGoNext(): void
    {
        $step = (int) $this->state['setupStep'];
        if ($step < 3) {
            $this->state['setupStep'] = $step + 1;
        }
    }

    public function shuffleMethodLabel(): string
    {
        $m = [
            'random' => 'Random order',
            'wins' => 'Fewest wins first',
            'levels' => 'By skill level',
            'teams' => 'Fixed pairs (team codes)',
        ];
        $method = (string) $this->state['shuffleMethod'];

        return $m[$method] ?? $method;
    }

    /**
     * @return array{ideal: int, min: int, max: int}
     */
    public function setupSuggestedCourtsCount(): array
    {
        $n = (int) (is_countable($this->state['players']) ? count($this->state['players']) : 0);
        $cap = 8;
        if ($n < 2) {
            return ['ideal' => 1, 'min' => 1, 'max' => $cap];
        }
        if ($this->state['mode'] === 'doubles') {
            $ideal = min($cap, max(1, (int) floor($n / 4)));

            return ['ideal' => $ideal, 'min' => 1, 'max' => $cap];
        }
        $ideal = min($cap, max(1, (int) round($n / 4)));

        return ['ideal' => $ideal, 'min' => 1, 'max' => $cap];
    }

    public function setupMinutesPerMatchFallback(): int
    {
        return 15;
    }

    public function setupMinutesPerMatchEstimate(): int
    {
        $t = (int) ($this->state['timeLimitMinutes'] ?? 0);

        return $t > 0 ? $t : $this->setupMinutesPerMatchFallback();
    }

    public function setupEstimatedRotationMinutes(): ?int
    {
        $n = (int) count($this->state['players']);
        $c = max(1, min(8, (int) ($this->state['courtsCount'] ?? 1) ?: 1));
        $perMatch = $this->setupMinutesPerMatchEstimate();
        if ($n < 2) {
            return null;
        }
        $onCourt = $this->state['mode'] === 'doubles' ? 4 * $c : 2 * $c;
        $waves = (int) ceil($n / max(1, $onCourt));

        return max(1, $waves) * $perMatch;
    }

    public function setupPlayerParityHint(): string
    {
        $n = (int) count($this->state['players']);
        if ($n < 2) {
            return '';
        }
        if ($this->state['mode'] === 'singles') {
            return $n % 2 === 1
                ? 'Odd player count: expect a bit more sit-out time in rotation.'
                : 'Even player count balances well for singles.';
        }
        if ($n % 4 === 0) {
            return 'Player count is a multiple of 4 — natural doubles lineups per court.';
        }
        if ($n % 2 === 1) {
            return 'Odd player count: doubles may need a spare or shared slot.';
        }

        return 'Not a multiple of 4: you may run short courts until the queue balances.';
    }

    public function setupCourtCountHint(): string
    {
        $n = (int) count($this->state['players']);
        if ($n < 2) {
            return '';
        }
        $ideal = $this->setupSuggestedCourtsCount()['ideal'];
        $cur = max(1, min(8, (int) ($this->state['courtsCount'] ?? 1) ?: 1));
        if ($cur === $ideal) {
            return "Your {$cur} court(s) match the usual target for this roster.";
        }

        $modeLabel = $this->state['mode'] === 'doubles' ? 'doubles' : 'singles';

        return "Typical target: about {$ideal} court(s) for {$n} {$modeLabel} players (you have {$cur}).";
    }

    /** Same as JS resetSession inner body (no confirm). */
    public function resetSession(): void
    {
        $this->clearPlayState();
    }

    public function fullResetState(): void
    {
        $this->state = self::defaultState();
        $this->ensureCourtSlots();
        $this->state['scoreDraft'] = [];
        $this->state['shareError'] = '';
        $this->state['uiPhase'] = 'list';
        $this->state['setupStep'] = 1;
        $this->state['bulkPlayerList'] = '';
        $this->state['bulkAddFeedback'] = '';
        $this->state['courtRemainingInput'] = [];
        $this->state['courtLineupDraft'] = [];
        $this->state['lineupEditError'] = '';
        $this->state['linkedOpenPlaySessionId'] = null;
    }

    public function courtsCountChanged(): void
    {
        $this->ensureCourtSlots();
    }

    /**
     * @param  list<string|int>  $ids
     */
    public function sideLabels(array $ids): string
    {
        return implode(' · ', array_map(fn ($id) => $this->playerLabel($id), $ids));
    }

    /**
     * @param  list<string|int>  $ids
     */
    public function sideLabelsWithStandings(array $ids): string
    {
        return implode(' · ', array_map(fn ($id) => $this->playerStandingsLabel($id), $ids));
    }

    /**
     * @param  array<string, mixed>|null  $court
     */
    public function remainingSeconds(?array $court, ?int $nowMs = null): ?int
    {
        $t = Timer::courtRemainingSeconds($court, (int) ($this->state['timeLimitMinutes'] ?? 0), $this->nowMs($nowMs));

        return $t;
    }

    public function formatCountdown(?int $sec): string
    {
        if ($sec === null) {
            return '';
        }
        $m = intdiv($sec, 60);
        $s = $sec % 60;

        return $m.':'.str_pad((string) $s, 2, '0', STR_PAD_LEFT);
    }

    public function normalizePlayers(): void
    {
        foreach ($this->state['players'] as $i => $p) {
            if (! is_array($p)) {
                continue;
            }
            $this->state['players'][$i]['skipShuffle'] = ! empty($p['skipShuffle']);
        }
    }

    public function primeH2hPicks(): void
    {
        $ids = array_map(fn ($p) => $p['id'] ?? null, $this->state['players']);
        $ids = array_values(array_filter($ids, fn ($id) => $id !== null && $id !== ''));
        if ($ids === []) {
            $this->state['h2hPlayerA'] = '';
            $this->state['h2hPlayerB'] = '';

            return;
        }
        $hasId = fn ($needle) => $this->someIdEqual($ids, $needle);
        if (! $this->state['h2hPlayerA'] || ! $hasId($this->state['h2hPlayerA'])) {
            $this->state['h2hPlayerA'] = $ids[0];
        }
        $ha = $this->state['h2hPlayerA'];
        if (
            count($ids) >= 2
            && (
                ! $this->state['h2hPlayerB']
                || ! $hasId($this->state['h2hPlayerB'])
                || self::idEqual($this->state['h2hPlayerB'], $ha)
            )
        ) {
            $other = null;
            foreach ($ids as $id) {
                if (! self::idEqual($id, $ha)) {
                    $other = $id;
                    break;
                }
            }
            $this->state['h2hPlayerB'] = $other ?? $ids[1];
        }
        if (count($ids) === 1) {
            $this->state['h2hPlayerB'] = '';
        }
    }

    public function normalizePlayerCap(): void
    {
        $max = OpenPlaySession::MAX_PLAYERS_PER_SESSION;
        $players = $this->state['players'];
        $before = count($players);
        if ($before <= $max) {
            return;
        }
        $kept = array_slice($players, 0, $max);
        $keepIds = [];
        foreach ($kept as $p) {
            $keepIds[(string) ($p['id'] ?? '')] = true;
        }
        $this->state['players'] = $kept;
        $this->state['queue'] = array_values(array_filter(
            $this->state['queue'],
            fn ($id) => isset($keepIds[(string) $id])
        ));
        $courts = $this->state['courts'];
        foreach ($courts as $i => $c) {
            if (! $c) {
                continue;
            }
            $sideA = $c['sideA'] ?? [];
            $sideB = $c['sideB'] ?? [];
            $all = array_merge($sideA, $sideB);
            $invalid = false;
            foreach ($all as $id) {
                if (! isset($keepIds[(string) $id])) {
                    $invalid = true;
                    break;
                }
            }
            if (! $invalid) {
                continue;
            }
            foreach ($all as $x) {
                if (isset($keepIds[(string) $x]) && ! $this->queueHas($x)) {
                    $this->state['queue'][] = $x;
                }
            }
            $courts[$i] = null;
        }
        $this->state['courts'] = $courts;
        $this->state['importError'] = "Only the first {$max} players are kept (session limit).";
    }

    public function playerCapReached(): bool
    {
        return count($this->state['players']) >= OpenPlaySession::MAX_PLAYERS_PER_SESSION;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function playerById(string|int|null $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }
        $k = (string) $id;
        foreach ($this->state['players'] as $p) {
            if (! is_array($p)) {
                continue;
            }
            if ((string) ($p['id'] ?? '') === $k) {
                return $p;
            }
        }

        return null;
    }

    public function playerLabel(string|int|null $id): string
    {
        $p = $this->playerById($id);

        return $p ? (string) ($p['name'] ?? '?') : '?';
    }

    public function playerStandingsLabel(string|int|null $id): string
    {
        $p = $this->playerById($id);
        if (! $p) {
            return '?';
        }
        $name = (string) ($p['name'] ?? '?');
        $w = (int) ($p['wins'] ?? 0);
        $l = (int) ($p['losses'] ?? 0);

        return "{$name} ({$w}–{$l})";
    }

    public function isOnCourt(string|int|null $playerId): bool
    {
        foreach ($this->state['courts'] as $c) {
            if (! $c || ! is_array($c)) {
                continue;
            }
            $sideA = $c['sideA'] ?? [];
            $sideB = $c['sideB'] ?? [];
            foreach (array_merge($sideA, $sideB) as $oid) {
                if (self::idEqual($oid, $playerId)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function eligiblePool(): array
    {
        $out = [];
        foreach ($this->state['players'] as $p) {
            if (! is_array($p)) {
                continue;
            }
            if (empty($p['disabled']) && empty($p['skipShuffle'])) {
                $out[] = $p;
            }
        }

        return $out;
    }

    /**
     * @return list<string|int>
     */
    public function idleEligibleIds(): array
    {
        $ids = array_map(fn ($p) => $p['id'], $this->eligiblePool());

        return array_values(array_filter($ids, fn ($id) => ! $this->isOnCourt($id)));
    }

    /**
     * @param  list<array<string, mixed>>  $players
     * @return list<array<string, mixed>>
     */
    public function sortPlayersForMethod(array $players, string $method): array
    {
        $arr = array_values($players);
        if ($method === 'random') {
            self::shuffleInPlace($arr);

            return $arr;
        }
        if ($method === 'wins') {
            usort($arr, function ($a, $b) {
                $aw = (int) ($a['wins'] ?? 0);
                $bw = (int) ($b['wins'] ?? 0);
                if ($aw !== $bw) {
                    return $aw <=> $bw;
                }
                $al = (int) ($a['losses'] ?? 0);
                $bl = (int) ($b['losses'] ?? 0);
                if ($bl !== $al) {
                    return $bl <=> $al;
                }

                return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });

            return $arr;
        }
        if ($method === 'levels') {
            usort($arr, function ($a, $b) {
                $al = (int) ($a['level'] ?? 0);
                $bl = (int) ($b['level'] ?? 0);
                if ($al !== $bl) {
                    return $al <=> $bl;
                }

                return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });

            return $arr;
        }
        if ($method === 'teams') {
            usort($arr, function ($a, $b) {
                $ta = trim((string) ($a['teamId'] ?? ''));
                $tb = trim((string) ($b['teamId'] ?? ''));
                if ($ta !== $tb) {
                    return strcmp($ta, $tb);
                }
                $al = (int) ($a['level'] ?? 0);
                $bl = (int) ($b['level'] ?? 0);
                if ($al !== $bl) {
                    return $al <=> $bl;
                }

                return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });

            return $arr;
        }

        return $arr;
    }

    /**
     * Total completed matches (wins + losses) for fair queue / fill ordering.
     *
     * @param  array<string, mixed>  $p
     */
    private static function totalGamesPlayed(array $p): int
    {
        return (int) ($p['wins'] ?? 0) + (int) ($p['losses'] ?? 0);
    }

    /**
     * @param  list<array<string, mixed>>  $players
     * @return list<array<string, mixed>>
     */
    private function sortIdlePlayersForFillByGamesThenMethod(array $players, string $method): array
    {
        $byGames = [];
        foreach (array_values($players) as $p) {
            $g = self::totalGamesPlayed($p);
            if (! isset($byGames[$g])) {
                $byGames[$g] = [];
            }
            $byGames[$g][] = $p;
        }
        ksort($byGames, SORT_NUMERIC);
        $out = [];
        foreach ($byGames as $group) {
            foreach ($this->sortPlayersForMethod($group, $method) as $row) {
                $out[] = $row;
            }
        }

        return $out;
    }

    private function totalGamesForQueueId(string|int $id): int
    {
        $p = $this->playerById($id);
        if (! $p) {
            return PHP_INT_MAX;
        }

        return self::totalGamesPlayed($p);
    }

    /**
     * @param  list<array<string, mixed>>  $poolPlayers
     * @return list<list<string|int>>
     */
    public function buildSides(array $poolPlayers): array
    {
        $method = (string) $this->state['shuffleMethod'];
        if ($this->state['mode'] === 'singles') {
            $sides = [];
            foreach ($poolPlayers as $p) {
                $sides[] = [$p['id']];
            }

            return $sides;
        }
        if ($method === 'teams') {
            $byTeam = [];
            foreach ($poolPlayers as $p) {
                $t = trim((string) ($p['teamId'] ?? '')) ?: ('_none_'.$p['id']);
                if (! isset($byTeam[$t])) {
                    $byTeam[$t] = [];
                }
                $byTeam[$t][] = $p;
            }
            $teamKeys = array_keys($byTeam);
            sort($teamKeys, SORT_STRING);
            $sides = [];
            foreach ($teamKeys as $t) {
                $g = $this->sortPlayersForMethod($byTeam[$t], 'levels');
                for ($i = 0; $i + 1 < count($g); $i += 2) {
                    $sides[] = [$g[$i]['id'], $g[$i + 1]['id']];
                }
            }

            return $sides;
        }
        /*
         * Non-team doubles: $poolPlayers order already comes from orderedPoolForFill() —
         * queue members first (fewest games first, stable ties), then idle-not-in-queue sorted by
         * games played then shuffleMethod. Pair consecutively; do not shuffle/sort again or Fill courts ignores the queue.
         */
        $arr = array_values($poolPlayers);
        $sides = [];
        for ($i = 0; $i + 1 < count($arr); $i += 2) {
            $sides[] = [$arr[$i]['id'], $arr[$i + 1]['id']];
        }

        return $sides;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function orderedPoolForFill(): array
    {
        $idle = $this->idleEligibleIds();
        $inQueue = array_values(array_filter(
            $this->state['queue'],
            fn ($id) => $this->someIdEqual($idle, $id)
        ));
        $rest = array_values(array_filter(
            $idle,
            fn ($id) => ! $this->queueHas($id)
        ));
        $restPlayers = [];
        foreach ($rest as $id) {
            $pl = $this->playerById($id);
            if ($pl) {
                $restPlayers[] = $pl;
            }
        }
        $sortedRest = array_map(
            fn ($p) => $p['id'],
            $this->sortIdlePlayersForFillByGamesThenMethod($restPlayers, (string) $this->state['shuffleMethod'])
        );
        $orderedIds = array_merge($inQueue, $sortedRest);
        $out = [];
        foreach ($orderedIds as $id) {
            $pl = $this->playerById($id);
            if ($pl) {
                $out[] = $pl;
            }
        }

        return $out;
    }

    public function fillCourts(?int $nowMs = null): void
    {
        $this->ensureCourtSlots();
        $this->syncQueueFromIdle();
        $pool = $this->orderedPoolForFill();
        $sides = $this->buildSides($pool);
        $emptyIdx = [];
        foreach ($this->state['courts'] as $i => $c) {
            if (! $c) {
                $emptyIdx[] = $i;
            }
        }
        $ts = $this->nowMs($nowMs);
        $si = 0;
        foreach ($emptyIdx as $idx) {
            if ($si + 1 >= count($sides)) {
                break;
            }
            $sideA = $sides[$si++];
            $sideB = $sides[$si++];
            $this->state['courts'][$idx] = [
                'courtIndex' => $idx,
                'sideA' => $sideA,
                'sideB' => $sideB,
                'startedAt' => $ts,
                'timerRunState' => 'running',
                'totalPausedMs' => 0,
                'pausedAt' => null,
            ];
        }
        $this->syncQueueFromIdle();
    }

    /**
     * @param  array<string, mixed>|null  $c
     * @return array<string, mixed>|null
     */
    public function extractCourtTimer(?array $c): ?array
    {
        if (! $c) {
            return null;
        }

        return [
            'startedAt' => $c['startedAt'] ?? null,
            'timerRunState' => $c['timerRunState'] ?? 'running',
            'totalPausedMs' => $c['totalPausedMs'] ?? 0,
            'pausedAt' => $c['pausedAt'] ?? null,
            'frozenElapsedMs' => $c['frozenElapsedMs'] ?? null,
        ];
    }

    public function enqueuePlayingPlayerToQueue(string|int $id): void
    {
        $pl = $this->playerById($id);
        if (! $pl || ! empty($pl['disabled']) || ! empty($pl['skipShuffle'])) {
            return;
        }
        if (! $this->queueHas($id)) {
            $this->state['queue'][] = $id;
        }
    }

    public function voidCourtIfInvalid(int $courtIndex): void
    {
        $ct = $this->state['courts'][$courtIndex] ?? null;
        if (! $ct) {
            return;
        }
        $need = $this->state['mode'] === 'singles' ? 1 : 2;
        $sideA = $ct['sideA'] ?? [];
        $sideB = $ct['sideB'] ?? [];
        $ok = count($sideA) === $need && count($sideB) === $need;
        if ($ok) {
            return;
        }
        foreach (array_merge($sideA, $sideB) as $id) {
            $this->enqueuePlayingPlayerToQueue($id);
        }
        $this->state['courts'][$courtIndex] = null;
    }

    public function initCourtLineupDraft(int $courtIndex): void
    {
        $c = $this->state['courts'][$courtIndex] ?? null;
        if (! $c) {
            return;
        }
        $need = $this->state['mode'] === 'singles' ? 1 : 2;
        $pad = function (array $arr) use ($need): array {
            $s = array_map('strval', $arr);
            while (count($s) < $need) {
                $s[] = '';
            }

            return array_slice($s, 0, $need);
        };
        if (! isset($this->state['courtLineupDraft']) || ! is_array($this->state['courtLineupDraft'])) {
            $this->state['courtLineupDraft'] = [];
        }
        $this->state['courtLineupDraft'][$courtIndex] = [
            'a' => $pad($c['sideA'] ?? []),
            'b' => $pad($c['sideB'] ?? []),
        ];
        $this->state['lineupEditError'] = '';
    }

    /**
     * @param  list<string|int>  $nextA
     * @param  list<string|int>  $nextB
     */
    public function applyCourtLineup(int $courtIndex, array $nextA, array $nextB, ?int $nowMs = null): void
    {
        $need = $this->state['mode'] === 'singles' ? 1 : 2;
        $na = array_values(array_filter(array_slice($nextA, 0, $need), fn ($x) => $x !== '' && $x !== null));
        $nb = array_values(array_filter(array_slice($nextB, 0, $need), fn ($x) => $x !== '' && $x !== null));
        if (count($na) !== $need || count($nb) !== $need) {
            $this->state['lineupEditError'] = 'Choose a player for each slot.';

            return;
        }
        $allNew = array_merge($na, $nb);
        $uniq = array_map('strval', $allNew);
        if (count(array_unique($uniq)) !== count($allNew)) {
            $this->state['lineupEditError'] = 'Each player can only be on the court once.';

            return;
        }
        foreach ($allNew as $id) {
            $pl = $this->playerById($id);
            if (! $pl || ! empty($pl['disabled'])) {
                $this->state['lineupEditError'] = 'Pick active roster players only.';

                return;
            }
        }

        $prev = $this->state['courts'][$courtIndex] ?? null;
        $oldLineup = $prev ? array_merge($prev['sideA'] ?? [], $prev['sideB'] ?? []) : [];
        $timer = $this->extractCourtTimer($prev) ?? [
            'startedAt' => $this->nowMs($nowMs),
            'timerRunState' => 'running',
            'totalPausedMs' => 0,
            'pausedAt' => null,
        ];

        foreach ($oldLineup as $id) {
            if (! $this->someIdEqual($allNew, $id)) {
                $this->enqueuePlayingPlayerToQueue($id);
            }
        }

        for ($j = 0; $j < count($this->state['courts']); $j++) {
            if ($j === $courtIndex) {
                continue;
            }
            $ct = $this->state['courts'][$j] ?? null;
            if (! $ct) {
                continue;
            }
            $before = array_merge($ct['sideA'] ?? [], $ct['sideB'] ?? []);
            $ct['sideA'] = array_values(array_filter(
                $ct['sideA'] ?? [],
                fn ($x) => ! $this->someIdEqual($allNew, $x)
            ));
            $ct['sideB'] = array_values(array_filter(
                $ct['sideB'] ?? [],
                fn ($x) => ! $this->someIdEqual($allNew, $x)
            ));
            $after = array_merge($ct['sideA'], $ct['sideB']);
            foreach ($before as $id) {
                if (! $this->someIdEqual($after, $id)) {
                    if (! $this->someIdEqual($allNew, $id)) {
                        $this->enqueuePlayingPlayerToQueue($id);
                    }
                }
            }
            $this->state['courts'][$j] = $ct;
        }

        for ($j = 0; $j < count($this->state['courts']); $j++) {
            if ($j === $courtIndex) {
                continue;
            }
            $this->voidCourtIfInvalid($j);
        }

        $this->state['courts'][$courtIndex] = array_merge([
            'courtIndex' => $courtIndex,
            'sideA' => $na,
            'sideB' => $nb,
        ], $timer);
        $this->state['lineupEditError'] = '';
        $this->syncQueueFromIdle();
    }

    public function applyCourtLineupDraft(int $courtIndex, ?int $nowMs = null): void
    {
        $d = $this->state['courtLineupDraft'][$courtIndex] ?? null;
        if (! $d) {
            $this->initCourtLineupDraft($courtIndex);
        }
        $draft = $this->state['courtLineupDraft'][$courtIndex] ?? null;
        if (! $draft) {
            return;
        }
        $this->applyCourtLineup($courtIndex, $draft['a'] ?? [], $draft['b'] ?? [], $nowMs);
    }

    public function pauseCourtTimer(int $i, ?int $nowMs = null): void
    {
        $c = $this->state['courts'][$i] ?? null;
        if (
            ! $c
            || empty($c['startedAt'])
            || ($c['timerRunState'] ?? '') === 'paused'
            || ($c['timerRunState'] ?? '') === 'stopped'
        ) {
            return;
        }
        $c['pausedAt'] = $this->nowMs($nowMs);
        $c['timerRunState'] = 'paused';
        $this->state['courts'][$i] = $c;
    }

    public function resumeCourtTimer(int $i, ?int $nowMs = null): void
    {
        $c = $this->state['courts'][$i] ?? null;
        if (! $c || ($c['timerRunState'] ?? '') !== 'paused' || empty($c['pausedAt'])) {
            return;
        }
        $pausedAt = (int) $c['pausedAt'];
        $c['totalPausedMs'] = (int) ($c['totalPausedMs'] ?? 0) + ($this->nowMs($nowMs) - $pausedAt);
        $c['pausedAt'] = null;
        $c['timerRunState'] = 'running';
        $this->state['courts'][$i] = $c;
    }

    public function stopCourtTimer(int $i, ?int $nowMs = null): void
    {
        $c = $this->state['courts'][$i] ?? null;
        if (! $c || empty($c['startedAt'])) {
            return;
        }
        $c['frozenElapsedMs'] = Timer::courtTimerElapsedMs($c, $this->nowMs($nowMs));
        $c['timerRunState'] = 'stopped';
        $c['pausedAt'] = null;
        $this->state['courts'][$i] = $c;
    }

    public function startCourtTimer(int $i, ?int $nowMs = null): void
    {
        $c = $this->state['courts'][$i] ?? null;
        if (! $c) {
            return;
        }
        $ts = $this->nowMs($nowMs);
        $c['startedAt'] = $ts;
        $c['totalPausedMs'] = 0;
        $c['pausedAt'] = null;
        unset($c['frozenElapsedMs']);
        $c['timerRunState'] = 'running';
        $this->state['courts'][$i] = $c;
    }

    public function setCourtRemainingSeconds(int $i, int|float $wantRemainingSec, ?int $nowMs = null): void
    {
        $c = $this->state['courts'][$i] ?? null;
        if (! $c || empty($c['startedAt'])) {
            return;
        }
        $limSec = max(0, (int) ($this->state['timeLimitMinutes'] ?? 0)) * 60;
        if ($limSec <= 0) {
            return;
        }
        $w = max(0, min($limSec, (int) floor((float) $wantRemainingSec)));
        $elapsedSec = $limSec - $w;
        $c['startedAt'] = $this->nowMs($nowMs) - $elapsedSec * 1000;
        $c['totalPausedMs'] = 0;
        $c['pausedAt'] = null;
        unset($c['frozenElapsedMs']);
        $c['timerRunState'] = 'running';
        $this->state['courts'][$i] = $c;
    }

    public function bumpCourtRemainingMinutes(int $i, int|float $deltaMin, ?int $nowMs = null): void
    {
        $c = $this->state['courts'][$i] ?? null;
        $limSec = max(0, (int) ($this->state['timeLimitMinutes'] ?? 0)) * 60;
        if (! $c || empty($c['startedAt']) || $limSec <= 0) {
            return;
        }
        $cur = $this->remainingSeconds($c, $nowMs);
        if ($cur === null) {
            return;
        }
        $next = max(0, min($limSec, $cur + (int) round((float) $deltaMin * 60)));
        $this->setCourtRemainingSeconds($i, $next, $nowMs);
    }

    public function applyCourtRemainingMinutes(int $i): void
    {
        $raw = $this->state['courtRemainingInput'][$i] ?? null;
        if ($raw === '' || $raw === null) {
            return;
        }
        $n = is_numeric($raw) ? 0 + $raw : NAN;
        if (is_nan($n) || $n < 0) {
            return;
        }
        $this->setCourtRemainingSeconds($i, (int) round((float) $n * 60));
        $this->state['courtRemainingInput'][$i] = '';
    }

    public function syncQueueFromIdle(): void
    {
        $onCourt = [];
        foreach ($this->state['courts'] as $c) {
            if ($c && is_array($c)) {
                foreach (array_merge($c['sideA'] ?? [], $c['sideB'] ?? []) as $id) {
                    $onCourt[(string) $id] = true;
                }
            }
        }
        $shouldWait = [];
        foreach ($this->eligiblePool() as $p) {
            $id = $p['id'];
            if (! isset($onCourt[(string) $id])) {
                $shouldWait[] = $id;
            }
        }
        $next = [];
        foreach ($this->state['queue'] as $id) {
            if ($this->someIdEqual($shouldWait, $id)) {
                $next[] = $id;
            }
        }
        foreach ($shouldWait as $id) {
            if (! $this->someIdEqual($next, $id)) {
                $next[] = $id;
            }
        }
        $filtered = array_values(array_filter($next, function ($qid) {
            $pl = $this->playerById($qid);

            return $pl && empty($pl['disabled']) && empty($pl['skipShuffle']);
        }));
        $prio = [];
        foreach ($filtered as $i => $id) {
            $prio[(string) $id] = $i;
        }
        usort($filtered, function ($a, $b) use ($prio) {
            $cmp = $this->totalGamesForQueueId($a) <=> $this->totalGamesForQueueId($b);
            if ($cmp !== 0) {
                return $cmp;
            }

            return ($prio[(string) $a] ?? 0) <=> ($prio[(string) $b] ?? 0);
        });
        $this->state['queue'] = $filtered;
    }

    public function clearCourt(int $i): void
    {
        $c = $this->state['courts'][$i] ?? null;
        if (! $c) {
            return;
        }
        foreach (array_merge($c['sideA'] ?? [], $c['sideB'] ?? []) as $id) {
            $pl = $this->playerById($id);
            if ($pl && (! empty($pl['skipShuffle']) || ! empty($pl['disabled']))) {
                continue;
            }
            if (! $this->queueHas($id)) {
                $this->state['queue'][] = $id;
            }
        }
        $this->state['courts'][$i] = null;
    }

    /**
     * @return array{a: int|float, b: int|float}
     */
    public function getScoreDraft(int $i): array
    {
        if (empty($this->state['scoreDraft'][$i]) || ! is_array($this->state['scoreDraft'][$i])) {
            $this->state['scoreDraft'][$i] = ['a' => 0, 'b' => 0];
        }

        return $this->state['scoreDraft'][$i];
    }

    public function completeMatch(int $i, ?int $nowMs = null): void
    {
        $court = $this->state['courts'][$i] ?? null;
        if (! $court) {
            return;
        }
        $d = $this->getScoreDraft($i);
        $scoreA = isset($d['a']) && is_numeric($d['a']) ? (float) $d['a'] : 0.0;
        $scoreB = isset($d['b']) && is_numeric($d['b']) ? (float) $d['b'] : 0.0;
        if (is_nan($scoreA)) {
            $scoreA = 0.0;
        }
        if (is_nan($scoreB)) {
            $scoreB = 0.0;
        }
        $winA = $scoreA > $scoreB;
        $winB = $scoreB > $scoreA;
        foreach ($court['sideA'] ?? [] as $id) {
            $p = $this->playerById($id);
            if ($p) {
                $idx = $this->playerIndexById($id);
                if ($idx !== null) {
                    if ($winA) {
                        $this->state['players'][$idx]['wins'] = (int) ($this->state['players'][$idx]['wins'] ?? 0) + 1;
                    } elseif ($winB) {
                        $this->state['players'][$idx]['losses'] = (int) ($this->state['players'][$idx]['losses'] ?? 0) + 1;
                    }
                }
            }
        }
        foreach ($court['sideB'] ?? [] as $id) {
            $p = $this->playerById($id);
            if ($p) {
                $idx = $this->playerIndexById($id);
                if ($idx !== null) {
                    if ($winB) {
                        $this->state['players'][$idx]['wins'] = (int) ($this->state['players'][$idx]['wins'] ?? 0) + 1;
                    } elseif ($winA) {
                        $this->state['players'][$idx]['losses'] = (int) ($this->state['players'][$idx]['losses'] ?? 0) + 1;
                    }
                }
            }
        }
        if ($winA || $winB) {
            $this->bumpH2h($court['sideA'] ?? [], $court['sideB'] ?? [], $winA);
        }
        $this->state['completedMatches'][] = [
            'sideA' => array_values($court['sideA'] ?? []),
            'sideB' => array_values($court['sideB'] ?? []),
            'scoreA' => $scoreA,
            'scoreB' => $scoreB,
            'at' => $this->nowMs($nowMs),
            'courtIndex' => $i,
        ];
        $this->state['courts'][$i] = null;
        foreach (array_merge($court['sideA'] ?? [], $court['sideB'] ?? []) as $id) {
            $pl = $this->playerById($id);
            if ($pl && (! empty($pl['skipShuffle']) || ! empty($pl['disabled']))) {
                continue;
            }
            if (! $this->queueHas($id)) {
                $this->state['queue'][] = $id;
            }
        }
        $this->state['scoreDraft'][$i] = ['a' => 0, 'b' => 0];
    }

    /**
     * @param  list<string|int>  $sideA
     * @param  list<string|int>  $sideB
     */
    public function bumpH2h(array $sideA, array $sideB, bool $sideAWon): void
    {
        $key = self::h2hStorageKey($sideA, $sideB);
        $ka = self::sideKey($sideA);
        $kb = self::sideKey($sideB);
        $aIsLow = $ka < $kb;
        $row = $this->state['h2h'][$key] ?? ['winsLow' => 0, 'winsHigh' => 0];
        if (! is_array($row)) {
            $row = ['winsLow' => 0, 'winsHigh' => 0];
        }
        if ($sideAWon) {
            if ($aIsLow) {
                $row['winsLow'] = (int) ($row['winsLow'] ?? 0) + 1;
            } else {
                $row['winsHigh'] = (int) ($row['winsHigh'] ?? 0) + 1;
            }
        } elseif ($aIsLow) {
            $row['winsHigh'] = (int) ($row['winsHigh'] ?? 0) + 1;
        } else {
            $row['winsLow'] = (int) ($row['winsLow'] ?? 0) + 1;
        }
        $this->state['h2h'][$key] = $row;
    }

    /**
     * @return list<array{key: string, left: string, right: string, winsLeft: int, winsRight: int}>
     */
    public function h2hRows(): array
    {
        $out = [];
        foreach ($this->state['h2h'] as $key => $row) {
            if (! is_string($key) || ! is_array($row)) {
                continue;
            }
            $parts = explode('||', $key, 2);
            $low = $parts[0] ?? '';
            $high = $parts[1] ?? '';
            $idsLow = $low !== '' ? explode(',', $low) : [];
            $idsHigh = $high !== '' ? explode(',', $high) : [];
            $out[] = [
                'key' => $key,
                'left' => $this->sideLabels($idsLow),
                'right' => $this->sideLabels($idsHigh),
                'winsLeft' => (int) ($row['winsLow'] ?? 0),
                'winsRight' => (int) ($row['winsHigh'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return array{winsA: int, winsB: int, lowFirst: bool}|null
     */
    public function pairH2hSummary(string|int|null $idA, string|int|null $idB): ?array
    {
        if (! $idA || ! $idB || self::idEqual($idA, $idB)) {
            return null;
        }
        $key = self::h2hStorageKey([$idA], [$idB]);
        $row = $this->state['h2h'][$key] ?? null;
        $ka = self::sideKey([$idA]);
        $kb = self::sideKey([$idB]);
        $lowFirst = $ka < $kb;
        if (! is_array($row)) {
            return [
                'winsA' => 0,
                'winsB' => 0,
                'lowFirst' => $lowFirst,
            ];
        }

        return [
            'winsA' => $lowFirst ? (int) ($row['winsLow'] ?? 0) : (int) ($row['winsHigh'] ?? 0),
            'winsB' => $lowFirst ? (int) ($row['winsHigh'] ?? 0) : (int) ($row['winsLow'] ?? 0),
            'lowFirst' => $lowFirst,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rankings(): array
    {
        $rows = [];
        foreach ($this->state['players'] as $p) {
            if (! is_array($p) || ! empty($p['disabled'])) {
                continue;
            }
            $w = (int) ($p['wins'] ?? 0);
            $l = (int) ($p['losses'] ?? 0);
            $played = $w + $l;
            $pct = $played ? (int) round((100 * $w) / $played) : 0;
            $rows[] = array_merge($p, ['played' => $played, 'pct' => $pct]);
        }
        usort($rows, function ($a, $b) {
            $aw = (int) ($a['wins'] ?? 0);
            $bw = (int) ($b['wins'] ?? 0);
            if ($bw !== $aw) {
                return $bw <=> $aw;
            }
            $ap = (int) ($a['pct'] ?? 0);
            $bp = (int) ($b['pct'] ?? 0);
            if ($bp !== $ap) {
                return $bp <=> $ap;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $rows;
    }

    public function addPlayer(): void
    {
        if (count($this->state['players']) >= OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
            return;
        }
        $name = trim((string) ($this->state['newName'] ?? ''));
        if ($name === '') {
            return;
        }
        $nl = (int) ($this->state['newLevel'] ?? 3);
        $this->state['players'][] = [
            'id' => self::newId(),
            'name' => $name,
            'level' => max(1, min(10, $nl ?: 3)),
            'wins' => 0,
            'losses' => 0,
            'disabled' => false,
            'skipShuffle' => false,
            'teamId' => trim((string) ($this->state['newTeamId'] ?? '')),
        ];
        $this->state['newName'] = '';
        $this->state['newTeamId'] = '';
        $this->primeH2hPicks();
    }

    public function cleanupBulkPlayerList(): void
    {
        $names = self::parseBulkPlayerNames((string) ($this->state['bulkPlayerList'] ?? ''));
        $this->state['bulkPlayerList'] = implode("\n", $names);
        $this->state['bulkAddFeedback'] = count($names) > 0
            ? 'Ready: '.count($names).' name'.(count($names) === 1 ? '' : 's').' (duplicates and empty lines removed).'
            : '';
    }

    public function addPlayersFromBulk(): void
    {
        $this->state['bulkAddFeedback'] = '';
        $names = self::parseBulkPlayerNames((string) ($this->state['bulkPlayerList'] ?? ''));
        $this->state['bulkPlayerList'] = implode("\n", $names);
        if ($names === []) {
            $this->state['bulkAddFeedback'] = 'Paste at least one name.';

            return;
        }
        $existingLower = [];
        foreach ($this->state['players'] as $p) {
            if (is_array($p) && isset($p['name'])) {
                $existingLower[strtolower(trim((string) $p['name']))] = true;
            }
        }
        $defaultLevel = max(1, min(10, (int) ($this->state['newLevel'] ?? 3) ?: 3));
        $defaultTeam = trim((string) ($this->state['newTeamId'] ?? ''));
        $added = 0;
        $skippedDup = 0;
        $i = 0;
        for (; $i < count($names); $i++) {
            if (count($this->state['players']) >= OpenPlaySession::MAX_PLAYERS_PER_SESSION) {
                break;
            }
            $name = $names[$i];
            $k = strtolower($name);
            if (isset($existingLower[$k])) {
                $skippedDup++;

                continue;
            }
            $existingLower[$k] = true;
            $this->state['players'][] = [
                'id' => self::newId(),
                'name' => $name,
                'level' => $defaultLevel,
                'wins' => 0,
                'losses' => 0,
                'disabled' => false,
                'skipShuffle' => false,
                'teamId' => $defaultTeam,
            ];
            $added++;
        }
        $notAddedDueToCap = count($names) - $i;
        $this->state['bulkPlayerList'] = '';
        $parts = [];
        if ($added > 0) {
            $parts[] = 'Added '.$added.' player'.($added === 1 ? '' : 's');
        }
        if ($skippedDup > 0) {
            $parts[] = $skippedDup.' already on roster';
        }
        if ($notAddedDueToCap > 0) {
            $parts[] = $notAddedDueToCap.' not added (roster full)';
        }
        if ($added === 0 && $skippedDup === 0 && $notAddedDueToCap === 0) {
            $parts[] = 'nothing new to add';
        }
        $this->state['bulkAddFeedback'] = implode(' · ', $parts);
        $this->primeH2hPicks();
    }

    public function removePlayer(string|int $id): void
    {
        $this->state['players'] = array_values(array_filter(
            $this->state['players'],
            fn ($p) => is_array($p) && ! self::idEqual($p['id'] ?? null, $id)
        ));
        $this->state['queue'] = array_values(array_filter(
            $this->state['queue'],
            fn ($x) => ! self::idEqual($x, $id)
        ));
        $courts = $this->state['courts'];
        foreach ($courts as $ci => $c) {
            if (! $c) {
                continue;
            }
            $sideA = array_values(array_filter($c['sideA'] ?? [], fn ($x) => ! self::idEqual($x, $id)));
            $sideB = array_values(array_filter($c['sideB'] ?? [], fn ($x) => ! self::idEqual($x, $id)));
            if (count($sideA) !== count($c['sideA'] ?? []) || count($sideB) !== count($c['sideB'] ?? [])) {
                foreach (array_merge($c['sideA'] ?? [], $c['sideB'] ?? []) as $x) {
                    if (! self::idEqual($x, $id) && ! $this->queueHas($x)) {
                        $this->state['queue'][] = $x;
                    }
                }
                $courts[$ci] = null;
            } else {
                $c['sideA'] = $sideA;
                $c['sideB'] = $sideB;
                $courts[$ci] = $c;
            }
        }
        $this->state['courts'] = $courts;
        $this->primeH2hPicks();
    }

    public function toggleDisabled(string|int $id): void
    {
        $idx = $this->playerIndexById($id);
        if ($idx === null) {
            return;
        }
        $p = $this->state['players'][$idx];
        $p['disabled'] = ! (bool) ($p['disabled'] ?? false);
        $this->state['players'][$idx] = $p;
        if (! empty($p['disabled'])) {
            $this->state['players'][$idx]['skipShuffle'] = false;
            $this->state['queue'] = array_values(array_filter(
                $this->state['queue'],
                fn ($x) => ! self::idEqual($x, $id)
            ));
            $courts = $this->state['courts'];
            foreach ($courts as $ci => $c) {
                if (! $c) {
                    continue;
                }
                $on = array_merge($c['sideA'] ?? [], $c['sideB'] ?? []);
                $hit = false;
                foreach ($on as $oid) {
                    if (self::idEqual($oid, $id)) {
                        $hit = true;
                        break;
                    }
                }
                if ($hit) {
                    foreach ($on as $x) {
                        if (! $this->queueHas($x)) {
                            $this->state['queue'][] = $x;
                        }
                    }
                    $courts[$ci] = null;
                }
            }
            $this->state['courts'] = $courts;
        }
    }

    /**
     * @param  array<string, mixed>  $p
     */
    public function syncSkipShuffleQueueAndPersist(array $p): void
    {
        if ($p === [] || ! empty($p['disabled'])) {
            return;
        }
        if (! empty($p['skipShuffle'])) {
            $this->state['queue'] = array_values(array_filter(
                $this->state['queue'],
                fn ($x) => ! self::idEqual($x, $p['id'] ?? null)
            ));
        } else {
            $this->syncQueueFromIdle();
        }
    }

    public function toggleSkipShuffle(string|int $id): void
    {
        $p = $this->playerById($id);
        if (! $p || ! empty($p['disabled'])) {
            return;
        }
        $idx = $this->playerIndexById($id);
        if ($idx === null) {
            return;
        }
        $cur = (bool) ($this->state['players'][$idx]['skipShuffle'] ?? false);
        $this->state['players'][$idx]['skipShuffle'] = ! $cur;
        $this->syncSkipShuffleQueueAndPersist($this->state['players'][$idx]);
    }

    public function setSkipShuffleForPlayer(string|int $id, bool $skip): void
    {
        $p = $this->playerById($id);
        if (! $p || ! empty($p['disabled'])) {
            return;
        }
        $idx = $this->playerIndexById($id);
        if ($idx === null) {
            return;
        }
        $this->state['players'][$idx]['skipShuffle'] = $skip;
        $this->syncSkipShuffleQueueAndPersist($this->state['players'][$idx]);
    }

    public function moveQueueUp(int $i): void
    {
        if ($i <= 0) {
            return;
        }
        $q = $this->state['queue'];
        [$q[$i - 1], $q[$i]] = [$q[$i], $q[$i - 1]];
        $this->state['queue'] = $q;
    }

    public function moveQueueDown(int $i): void
    {
        $q = $this->state['queue'];
        if ($i >= count($q) - 1) {
            return;
        }
        [$q[$i], $q[$i + 1]] = [$q[$i + 1], $q[$i]];
        $this->state['queue'] = $q;
    }

    public function removeFromQueue(int $i): void
    {
        if (! isset($this->state['queue'][$i])) {
            return;
        }
        array_splice($this->state['queue'], $i, 1);
    }

    public function clearPlayState(): void
    {
        foreach ($this->state['players'] as $i => $p) {
            if (is_array($p)) {
                $this->state['players'][$i]['wins'] = 0;
                $this->state['players'][$i]['losses'] = 0;
            }
        }
        $this->state['queue'] = [];
        foreach ($this->state['courts'] as $ci => $_) {
            $this->state['courts'][$ci] = null;
        }
        $this->state['completedMatches'] = [];
        $this->state['h2h'] = [];
        $this->state['scoreDraft'] = [];
        $this->state['courtRemainingInput'] = [];
        $this->state['courtLineupDraft'] = [];
        $this->state['lineupEditError'] = '';
    }

    public function ensureCourtSlots(): void
    {
        $n = max(1, min(8, (int) ($this->state['courtsCount'] ?? 1) ?: 1));
        $this->state['courtsCount'] = $n;
        $courts = $this->state['courts'];
        while (count($courts) < $n) {
            $courts[] = null;
        }
        if (count($courts) > $n) {
            $courts = array_slice($courts, 0, $n);
        }
        $this->state['courts'] = array_values($courts);
    }

    public static function newId(): string
    {
        return sprintf(
            '%08x-%04x-%04x-%04x-%012x',
            random_int(0, 0xFFFFFFFF),
            random_int(0, 0xFFFF),
            random_int(0, 0x0FFF) | 0x4000,
            random_int(0, 0x3FFF) | 0x8000,
            random_int(0, 0xFFFFFFFFFFFF)
        );
    }

    /**
     * @param  list<string|int>  $ids
     */
    public static function sideKey(array $ids): string
    {
        $s = array_map('strval', $ids);
        sort($s, SORT_STRING);

        return implode(',', $s);
    }

    /**
     * @param  list<string|int>  $sideA
     * @param  list<string|int>  $sideB
     */
    public static function h2hStorageKey(array $sideA, array $sideB): string
    {
        $a = self::sideKey($sideA);
        $b = self::sideKey($sideB);

        return $a < $b ? $a.'||'.$b : $b.'||'.$a;
    }

    /**
     * @param  list<array<string, mixed>>  $arr
     */
    private static function shuffleInPlace(array &$arr): void
    {
        for ($i = count($arr) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }
    }

    public static function cleanBulkPlayerLine(string $line): string
    {
        $s = str_replace("\u{00A0}", ' ', (string) $line);
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/^\d{1,3}\s*[.):\-]\s*/u', '', $s) ?? $s;
        $s = trim($s);
        if (preg_match('/^(\d{1,3})\s+(\S.*)$/u', $s, $spaced)) {
            $rest = trim($spaced[2] ?? '');
            if ($rest !== '' && preg_match('/^[A-Za-z\x{C0}-\x{24F}]/u', $rest)) {
                $s = $rest;
            }
        }

        return trim($s);
    }

    /**
     * @return list<string>
     */
    public static function parseBulkPlayerNames(string $raw): array
    {
        $seen = [];
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $name = self::cleanBulkPlayerLine((string) $line);
            if ($name === '') {
                continue;
            }
            $key = strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $name;
        }

        return $out;
    }

    private static function idEqual(mixed $a, mixed $b): bool
    {
        return (string) $a === (string) $b;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private static function cloneState(array $state): array
    {
        return json_decode(json_encode($state, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function nowMs(?int $override): int
    {
        return $override ?? (int) round(microtime(true) * 1000);
    }

    private function queueHas(string|int|null $id): bool
    {
        foreach ($this->state['queue'] as $q) {
            if (self::idEqual($q, $id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string|int>  $ids
     */
    private function someIdEqual(array $ids, string|int|null $needle): bool
    {
        foreach ($ids as $id) {
            if (self::idEqual($id, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function playerIndexById(string|int|null $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $k = (string) $id;
        foreach ($this->state['players'] as $i => $p) {
            if (is_array($p) && (string) ($p['id'] ?? '') === $k) {
                return (int) $i;
            }
        }

        return null;
    }
}
