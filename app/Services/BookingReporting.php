<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BookingReporting
{
    public static function nowTz(): IlluminateCarbon
    {
        return now(config('app.timezone', 'UTC'));
    }

    public static function monthStart(): IlluminateCarbon
    {
        return self::nowTz()->copy()->startOfMonth();
    }

    /** Revenue from confirmed + completed; NULL amounts count as 0. */
    public static function coalescedRevenueSum(Builder $bookingQuery): int
    {
        return (int) (clone $bookingQuery)
            ->countingTowardRevenue()
            ->sum(DB::raw('COALESCE(amount_cents, 0)'));
    }

    /** Platform convenience fee total (confirmed + completed); NULL counts as 0. */
    public static function coalescedPlatformBookingFeeSum(Builder $bookingQuery): int
    {
        return (int) (clone $bookingQuery)
            ->countingTowardRevenue()
            ->sum(DB::raw('COALESCE(platform_booking_fee_cents, 0)'));
    }

    /**
     * @param  Closure(Builder):void  $scope  Apply venue / global scope to a fresh {@see Booking} query builder.
     * @return list<array{label: string, count: int}>
     */
    public static function lastNMonthsVolume(Closure $scope, int $months = 6): array
    {
        $tz = config('app.timezone', 'UTC');
        $anchor = self::nowTz()->copy()->startOfMonth();
        $firstMonth = $anchor->copy()->subMonths(max(1, $months) - 1);

        $q = Booking::query();
        $scope($q);
        $rows = $q
            ->countingTowardRevenue()
            ->where('starts_at', '>=', $firstMonth->copy()->timezone($tz))
            ->where('starts_at', '<', $anchor->copy()->addMonth()->timezone($tz))
            ->get(['starts_at']);

        $counts = $rows->groupBy(fn (Booking $b) => $b->starts_at->timezone($tz)->format('Y-m'))
            ->map(fn ($g) => $g->count());

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $firstMonth->copy()->addMonths($i);
            $key = $m->format('Y-m');
            $out[] = [
                'label' => $key,
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param  Closure(Builder):void  $scope
     * @return list<array{label: string, revenue_cents: int, convenience_fee_cents: int}>
     */
    public static function lastNMonthsRevenue(Closure $scope, int $months = 6): array
    {
        $tz = config('app.timezone', 'UTC');
        $anchor = self::nowTz()->copy()->startOfMonth();
        $firstMonth = $anchor->copy()->subMonths(max(1, $months) - 1);

        $q = Booking::query();
        $scope($q);
        $rows = $q
            ->countingTowardRevenue()
            ->where('starts_at', '>=', $firstMonth->copy()->timezone($tz))
            ->where('starts_at', '<', $anchor->copy()->addMonth()->timezone($tz))
            ->get(['starts_at', 'amount_cents', 'platform_booking_fee_cents']);

        $byMonth = $rows->groupBy(fn (Booking $b) => $b->starts_at->timezone($tz)->format('Y-m'));

        $sumsRev = $byMonth->map(fn ($g) => $g->sum(fn (Booking $b) => (int) ($b->amount_cents ?? 0)));
        $sumsFee = $byMonth->map(fn ($g) => $g->sum(fn (Booking $b) => (int) ($b->platform_booking_fee_cents ?? 0)));

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $firstMonth->copy()->addMonths($i);
            $key = $m->format('Y-m');
            $out[] = [
                'label' => $key,
                'revenue_cents' => (int) ($sumsRev[$key] ?? 0),
                'convenience_fee_cents' => (int) ($sumsFee[$key] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param  Closure(Builder):void  $scope
     * @return Collection<int, object{status: string, c: int}>
     */
    public static function statusCounts(Closure $scope): Collection
    {
        $q = Booking::query();
        $scope($q);

        return $q
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($r) => (object) ['status' => $r->status, 'c' => (int) $r->c]);
    }

    /**
     * @param  Closure(Builder):void  $scope
     * @return Collection<int, object{user: ?User, booking_count: int, revenue_cents: int, convenience_fee_cents: int}>
     */
    public static function topBookers(Closure $scope, IlluminateCarbon $from, IlluminateCarbon $to, int $limit = 10): Collection
    {
        $q = Booking::query();
        $scope($q);

        $rows = $q
            ->clone()
            ->countingTowardRevenue()
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<=', $to)
            ->selectRaw('user_id, COUNT(*) as booking_count, COALESCE(SUM(COALESCE(amount_cents, 0)), 0) as revenue_cents, COALESCE(SUM(COALESCE(platform_booking_fee_cents, 0)), 0) as convenience_fee_cents')
            ->groupBy('user_id')
            ->orderByDesc('booking_count')
            ->limit($limit)
            ->get();

        $userIds = $rows->pluck('user_id')->filter()->all();
        if ($userIds === []) {
            return collect();
        }

        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'user' => $users->get($row->user_id),
            'booking_count' => (int) $row->booking_count,
            'revenue_cents' => (int) $row->revenue_cents,
            'convenience_fee_cents' => (int) $row->convenience_fee_cents,
        ])->filter(fn (object $x) => $x->user !== null)->values();
    }

    /**
     * @param  Closure(Builder):void  $scope
     * @return Collection<int, object{court: ?Court, booking_count: int}>
     */
    public static function topCourts(Closure $scope, IlluminateCarbon $from, IlluminateCarbon $to, int $limit = 10): Collection
    {
        $q = Booking::query();
        $scope($q);

        $rows = $q
            ->clone()
            ->countingTowardRevenue()
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<=', $to)
            ->whereNotNull('court_id')
            ->selectRaw('court_id, COUNT(*) as booking_count')
            ->groupBy('court_id')
            ->orderByDesc('booking_count')
            ->limit($limit)
            ->get();

        $courtIds = $rows->pluck('court_id')->all();
        $courts = Court::query()->with('courtClient:id,name')->whereIn('id', $courtIds)->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'court' => $courts->get($row->court_id),
            'booking_count' => (int) $row->booking_count,
        ])->filter(fn (object $x) => $x->court !== null)->values();
    }
}
