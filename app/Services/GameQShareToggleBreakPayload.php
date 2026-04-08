<?php

namespace App\Services;

/** Live-watch “take a break” toggle — same queue rules as {@see \App\GameQ\Engine} roster updates. */
final class GameQShareToggleBreakPayload
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: array<string, mixed>, 1: string|null}
     */
    public static function apply(array $payload, string $playerId, bool $skipShuffle): array
    {
        $players = $payload['players'] ?? null;
        if (! is_array($players)) {
            return [$payload, 'Invalid payload'];
        }

        $idx = null;
        foreach ($players as $i => $p) {
            if (! is_array($p)) {
                continue;
            }
            if ((string) ($p['id'] ?? '') === $playerId) {
                $idx = $i;
                break;
            }
        }

        if ($idx === null) {
            return [$payload, 'Player not found'];
        }

        if (! empty($players[$idx]['disabled'])) {
            return [$payload, 'Player is inactive'];
        }

        $payload['players'][$idx]['skipShuffle'] = $skipShuffle;

        $queue = $payload['queue'] ?? [];
        if (! is_array($queue)) {
            $queue = [];
        }

        if ($skipShuffle) {
            $payload['queue'] = array_values(array_filter(
                $queue,
                fn ($qid) => (string) $qid !== $playerId,
            ));

            return [$payload, null];
        }

        $courts = $payload['courts'] ?? [];
        if (! is_array($courts)) {
            $courts = [];
        }

        $onCourt = [];
        foreach ($courts as $c) {
            if (! is_array($c)) {
                continue;
            }
            foreach (array_merge($c['sideA'] ?? [], $c['sideB'] ?? []) as $oid) {
                $onCourt[(string) $oid] = true;
            }
        }

        $players = $payload['players'];
        $shouldWait = [];
        foreach ($players as $pl) {
            if (! is_array($pl)) {
                continue;
            }
            if (! empty($pl['disabled']) || ! empty($pl['skipShuffle'])) {
                continue;
            }
            $pid = $pl['id'] ?? null;
            if ($pid === null) {
                continue;
            }
            $pidStr = (string) $pid;
            if (isset($onCourt[$pidStr])) {
                continue;
            }
            $shouldWait[] = $pid;
        }

        $next = [];
        foreach ($queue as $qid) {
            if (self::idInList($qid, $shouldWait)) {
                $next[] = $qid;
            }
        }
        foreach ($shouldWait as $eid) {
            if (! self::idInList($eid, $next)) {
                $next[] = $eid;
            }
        }

        $payload['queue'] = $next;

        return [$payload, null];
    }

    /**
     * @param  array<int, mixed>  $list
     */
    private static function idInList(mixed $needle, array $list): bool
    {
        $n = (string) $needle;

        foreach ($list as $h) {
            if ((string) $h === $n) {
                return true;
            }
        }

        return false;
    }
}
