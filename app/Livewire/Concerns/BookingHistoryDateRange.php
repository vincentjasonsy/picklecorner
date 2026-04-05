<?php

namespace App\Livewire\Concerns;

use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

trait BookingHistoryDateRange
{
    public int $maxHistoryRangeDays = 93;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    protected function initializeHistoryRangeDefaults(): void
    {
        if ($this->from === '' || $this->to === '') {
            [$this->from, $this->to] = $this->defaultWeekDateStrings();
        }
        $this->clampHistoryRange();
    }

    /** @return array{0: string, 1: string} */
    protected function defaultWeekDateStrings(): array
    {
        $tz = config('app.timezone', 'UTC');
        $start = Carbon::now($tz)->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        return [$start->toDateString(), $end->toDateString()];
    }

    protected function clampHistoryRange(): void
    {
        $tz = config('app.timezone', 'UTC');
        try {
            $a = Carbon::parse($this->from, $tz)->startOfDay();
            $b = Carbon::parse($this->to, $tz)->endOfDay();
        } catch (\Throwable) {
            [$this->from, $this->to] = $this->defaultWeekDateStrings();

            return;
        }

        if ($a->gt($b)) {
            [$a, $b] = [$b->copy()->startOfDay(), $a->copy()->endOfDay()];
        }

        $maxEnd = $a->copy()->addDays($this->maxHistoryRangeDays - 1)->endOfDay();
        if ($b->gt($maxEnd)) {
            $b = $maxEnd;
        }

        $this->from = $a->toDateString();
        $this->to = $b->toDateString();
    }

    public function updatedFrom(): void
    {
        $this->clampHistoryRange();
    }

    public function updatedTo(): void
    {
        $this->clampHistoryRange();
    }

    public function presetThisWeek(): void
    {
        [$this->from, $this->to] = $this->defaultWeekDateStrings();
        $this->clampHistoryRange();
    }

    public function presetLast7Days(): void
    {
        $tz = config('app.timezone', 'UTC');
        $end = Carbon::now($tz)->endOfDay();
        $start = $end->copy()->subDays(6)->startOfDay();
        $this->from = $start->toDateString();
        $this->to = $end->toDateString();
        $this->clampHistoryRange();
    }

    public function presetLast30Days(): void
    {
        $tz = config('app.timezone', 'UTC');
        $end = Carbon::now($tz)->endOfDay();
        $start = $end->copy()->subDays(29)->startOfDay();
        $this->from = $start->toDateString();
        $this->to = $end->toDateString();
        $this->clampHistoryRange();
    }

    public function presetThisMonth(): void
    {
        $tz = config('app.timezone', 'UTC');
        $start = Carbon::now($tz)->startOfMonth()->startOfDay();
        $end = Carbon::now($tz)->endOfMonth()->endOfDay();
        $this->from = $start->toDateString();
        $this->to = $end->toDateString();
        $this->clampHistoryRange();
    }

    public function shiftCalendarWeek(int $deltaWeeks): void
    {
        $tz = config('app.timezone', 'UTC');
        $anchor = Carbon::parse($this->from, $tz)
            ->startOfWeek(CarbonInterface::MONDAY)
            ->addWeeks($deltaWeeks);
        $this->from = $anchor->toDateString();
        $this->to = $anchor->copy()->addDays(6)->toDateString();
        $this->clampHistoryRange();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return array{start: Carbon, end: Carbon, days: array<string, Collection<int, Booking>>}
     */
    protected function bucketBookingsByDayInRange(Collection $bookings, string $tz): array
    {
        $rangeStart = Carbon::parse($this->from, $tz)->startOfDay();
        $rangeEnd = Carbon::parse($this->to, $tz)->endOfDay();

        $byDay = $bookings->groupBy(fn (Booking $b) => $b->starts_at?->timezone($tz)->format('Y-m-d') ?? '');

        $days = [];
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $key = $cursor->format('Y-m-d');
            $days[$key] = $byDay->get($key, collect());
            $cursor->addDay();
        }

        return [
            'start' => $rangeStart,
            'end' => $rangeEnd,
            'days' => $days,
        ];
    }
}
