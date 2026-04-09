<?php

namespace App\GameQ;

use App\Models\OpenPlaySession;
use App\Models\User;

/**
 * Cross-session GameQ stats for a member profile: who you've played against (and with),
 * matched by roster name === account display name.
 */
final class ProfileOpponentAggregator
{
    /**
     * @param  iterable<OpenPlaySession>  $sessions
     * @return array{
     *   opponents: list<array{key: string, displayName: string, wins: int, losses: int, ties: int}>,
     *   partners: list<array{key: string, displayName: string, games: int}>,
     *   sessions_matched: int,
     *   matches_counted: int,
     * }
     */
    public static function forUser(User $user, iterable $sessions): array
    {
        $myNorm = self::normalizeName($user->name);
        if ($myNorm === '') {
            return self::emptyResult();
        }

        /** @var array<string, array{displayName: string, wins: int, losses: int, ties: int}> $against */
        $against = [];
        /** @var array<string, array{displayName: string, games: int}> $with */
        $with = [];
        $sessionsMatched = 0;
        $matchesCounted = 0;

        foreach ($sessions as $session) {
            if (! $session instanceof OpenPlaySession) {
                continue;
            }
            $payload = $session->payload;
            if (! is_array($payload)) {
                continue;
            }
            $engine = new Engine($payload);
            $myId = self::findSelfPlayerId($engine, $myNorm);
            if ($myId === null) {
                continue;
            }
            $sessionsMatched++;

            $log = $payload['completedMatches'] ?? [];
            if (! is_array($log)) {
                continue;
            }

            foreach ($log as $m) {
                if (! is_array($m)) {
                    continue;
                }
                $sideA = array_values(is_array($m['sideA'] ?? null) ? $m['sideA'] : []);
                $sideB = array_values(is_array($m['sideB'] ?? null) ? $m['sideB'] : []);
                $scoreA = isset($m['scoreA']) && is_numeric($m['scoreA']) ? (float) $m['scoreA'] : 0.0;
                $scoreB = isset($m['scoreB']) && is_numeric($m['scoreB']) ? (float) $m['scoreB'] : 0.0;
                if (is_nan($scoreA)) {
                    $scoreA = 0.0;
                }
                if (is_nan($scoreB)) {
                    $scoreB = 0.0;
                }
                $inA = self::sideHasId($sideA, $myId);
                $inB = self::sideHasId($sideB, $myId);
                if (! $inA && ! $inB) {
                    continue;
                }
                $matchesCounted++;
                $winA = $scoreA > $scoreB;
                $winB = $scoreB > $scoreA;
                $tie = ! $winA && ! $winB;
                $mySide = $inA ? $sideA : $sideB;
                $oppSide = $inA ? $sideB : $sideA;
                $weWon = $inA ? $winA : $winB;

                foreach ($oppSide as $oid) {
                    $label = $engine->playerLabel($oid);
                    $key = self::normalizeName($label);
                    if ($key === '' || $key === $myNorm) {
                        continue;
                    }
                    if (! isset($against[$key])) {
                        $against[$key] = ['displayName' => $label, 'wins' => 0, 'losses' => 0, 'ties' => 0];
                    }
                    if ($tie) {
                        $against[$key]['ties']++;
                    } elseif ($weWon) {
                        $against[$key]['wins']++;
                    } else {
                        $against[$key]['losses']++;
                    }
                }

                foreach ($mySide as $tid) {
                    if (self::idEqual($tid, $myId)) {
                        continue;
                    }
                    $plabel = $engine->playerLabel($tid);
                    $pkey = self::normalizeName($plabel);
                    if ($pkey === '' || $pkey === $myNorm) {
                        continue;
                    }
                    if (! isset($with[$pkey])) {
                        $with[$pkey] = ['displayName' => $plabel, 'games' => 0];
                    }
                    $with[$pkey]['games']++;
                }
            }
        }

        $opponents = [];
        foreach ($against as $key => $row) {
            $opponents[] = [
                'key' => $key,
                'displayName' => $row['displayName'],
                'wins' => $row['wins'],
                'losses' => $row['losses'],
                'ties' => $row['ties'],
            ];
        }
        usort($opponents, function (array $a, array $b): int {
            $ga = ($a['wins'] + $a['losses'] + $a['ties']);
            $gb = ($b['wins'] + $b['losses'] + $b['ties']);
            if ($gb !== $ga) {
                return $gb <=> $ga;
            }

            return strcmp($a['displayName'], $b['displayName']);
        });

        $partners = [];
        foreach ($with as $key => $row) {
            $partners[] = [
                'key' => $key,
                'displayName' => $row['displayName'],
                'games' => $row['games'],
            ];
        }
        usort($partners, function (array $a, array $b): int {
            if (($b['games'] ?? 0) !== ($a['games'] ?? 0)) {
                return ($b['games'] ?? 0) <=> ($a['games'] ?? 0);
            }

            return strcmp($a['displayName'], $b['displayName']);
        });

        return [
            'opponents' => $opponents,
            'partners' => $partners,
            'sessions_matched' => $sessionsMatched,
            'matches_counted' => $matchesCounted,
        ];
    }

    /**
     * @return array{sessions_matched: int, matches_counted: int, opponents: array{}, partners: array{}}
     */
    private static function emptyResult(): array
    {
        return [
            'opponents' => [],
            'partners' => [],
            'sessions_matched' => 0,
            'matches_counted' => 0,
        ];
    }

    private static function normalizeName(?string $name): string
    {
        $s = trim(mb_strtolower((string) $name));

        return $s;
    }

    private static function findSelfPlayerId(Engine $engine, string $myNorm): ?string
    {
        $players = $engine->toArray()['players'] ?? [];
        if (! is_array($players)) {
            return null;
        }
        foreach ($players as $p) {
            if (! is_array($p) || ! empty($p['disabled'])) {
                continue;
            }
            $nid = self::normalizeName((string) ($p['name'] ?? ''));
            if ($nid !== '' && $nid === $myNorm) {
                return (string) ($p['id'] ?? '');
            }
        }

        return null;
    }

    /**
     * @param  list<string|int>  $side
     */
    private static function sideHasId(array $side, string $id): bool
    {
        foreach ($side as $x) {
            if (self::idEqual($x, $id)) {
                return true;
            }
        }

        return false;
    }

    private static function idEqual(mixed $a, mixed $b): bool
    {
        return (string) $a === (string) $b;
    }
}
