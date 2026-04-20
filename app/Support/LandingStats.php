<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class LandingStats
{
    public static function listedCourtsCount(): int
    {
        return Court::query()
            ->where('is_available', true)
            ->whereHas('courtClient', fn ($q) => $q->wherePubliclyBookable())
            ->count();
    }

    public static function happyPlayersCount(): int
    {
        return User::query()
            ->whereHas('bookings', function ($q): void {
                $q->whereIn('status', [
                    Booking::STATUS_CONFIRMED,
                    Booking::STATUS_COMPLETED,
                    Booking::STATUS_PENDING_APPROVAL,
                ]);
            })
            ->count();
    }

    public static function averageBookingSessionMinutes(): ?float
    {
        $driver = DB::connection()->getDriverName();

        $base = Booking::query()->whereIn('status', [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_COMPLETED,
        ]);

        if ($driver === 'sqlite') {
            $row = (clone $base)->selectRaw(
                'avg((cast(strftime(\'%s\', ends_at) as integer) - cast(strftime(\'%s\', starts_at) as integer)) / 60.0) as v'
            )->first();

            return $row !== null && $row->v !== null ? (float) $row->v : null;
        }

        $row = (clone $base)->selectRaw(
            'avg(TIMESTAMPDIFF(MINUTE, starts_at, ends_at)) as v'
        )->first();

        return $row !== null && $row->v !== null ? (float) $row->v : null;
    }

    public static function formatAverageSession(?float $minutes): string
    {
        if ($minutes === null || $minutes <= 0) {
            return '—';
        }

        if ($minutes < 60) {
            return (string) max(1, (int) round($minutes)).'m';
        }

        $hours = $minutes / 60.0;

        return $hours >= 10 ? (string) (int) round($hours).'h' : round($hours, 1).'h';
    }
}
