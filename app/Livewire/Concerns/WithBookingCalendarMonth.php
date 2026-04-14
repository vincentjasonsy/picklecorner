<?php

namespace App\Livewire\Concerns;

use Carbon\Carbon;
use Livewire\Attributes\Url;

trait WithBookingCalendarMonth
{
    #[Url]
    public string $ym = '';

    protected function initializeCalendarMonth(): void
    {
        if ($this->ym === '') {
            $this->ym = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m');
        }
        $this->sanitizeYm();
    }

    protected function sanitizeYm(): void
    {
        $tz = config('app.timezone', 'UTC');
        try {
            Carbon::createFromFormat('Y-m', $this->ym, $tz)->startOfMonth();
        } catch (\Throwable) {
            $this->ym = Carbon::now($tz)->format('Y-m');
        }
    }

    public function updatedYm(): void
    {
        $this->sanitizeYm();
    }

    public function shiftCalendarMonth(int $deltaMonths): void
    {
        $tz = config('app.timezone', 'UTC');
        $anchor = Carbon::createFromFormat('Y-m', $this->ym, $tz)->startOfMonth()->addMonths($deltaMonths);
        $this->ym = $anchor->format('Y-m');
        $this->sanitizeYm();
    }

    protected function calendarMonthStart(): Carbon
    {
        $tz = config('app.timezone', 'UTC');

        return Carbon::createFromFormat('Y-m', $this->ym, $tz)->startOfMonth()->startOfDay();
    }
}
