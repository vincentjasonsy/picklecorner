<?php

namespace App\GameQ;

/**
 * Match clock on a court — wall-clock math for host and live watch (PHP only).
 * Pass wall-clock milliseconds from Livewire or Engine (e.g. (int) round(microtime(true) * 1000)).
 */
final class Timer
{
    /**
     * @param  array<string, mixed>|null  $court
     */
    public static function courtTimerElapsedMs(?array $court, int $nowMs): int
    {
        if ($court === null || ! isset($court['startedAt'])) {
            return 0;
        }
        $now = $nowMs;
        $state = $court['timerRunState'] ?? 'running';
        if ($state === 'stopped') {
            return max(0, (int) ($court['frozenElapsedMs'] ?? 0));
        }
        $totalPaused = (int) ($court['totalPausedMs'] ?? 0);
        if ($state === 'paused' && isset($court['pausedAt'])) {
            return max(0, (int) $court['pausedAt'] - (int) $court['startedAt'] - $totalPaused);
        }

        return max(0, $now - (int) $court['startedAt'] - $totalPaused);
    }

    /**
     * @param  array<string, mixed>|null  $court
     */
    public static function courtElapsedSeconds(?array $court, int $nowMs): int
    {
        return (int) floor(self::courtTimerElapsedMs($court, $nowMs) / 1000);
    }

    /**
     * Seconds left on the limit clock, or null if no limit / no started match.
     *
     * @param  array<string, mixed>|null  $court
     */
    public static function courtRemainingSeconds(?array $court, int|float|string $timeLimitMinutes, int $nowMs): ?int
    {
        $min = (int) ($timeLimitMinutes ?: 0);
        if ($min <= 0 || $court === null || ! isset($court['startedAt'])) {
            return null;
        }
        $limSec = $min * 60;
        $elapsedSec = self::courtElapsedSeconds($court, $nowMs);

        return max(0, $limSec - $elapsedSec);
    }
}
