<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Support\BookingCalendarGrid;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingCalendarGridTest extends TestCase
{
    #[Test]
    public function it_builds_five_or_six_week_rows_covering_month(): void
    {
        $tz = config('app.timezone', 'UTC');
        $monthStart = Carbon::create(2026, 4, 1, 0, 0, 0, $tz);
        $grid = BookingCalendarGrid::build($monthStart, $tz, collect());

        $this->assertGreaterThanOrEqual(5, count($grid['weeks']));
        $this->assertLessThanOrEqual(6, count($grid['weeks']));
        $this->assertCount(7, $grid['weeks'][0]);
        $this->assertTrue($grid['weeks'][0][0]['date']->isMonday());
    }

    #[Test]
    public function it_buckets_bookings_by_local_day(): void
    {
        $tz = 'Asia/Manila';
        $monthStart = Carbon::create(2026, 4, 15, 0, 0, 0, $tz);

        $booking = new Booking;
        $booking->forceFill([
            'starts_at' => Carbon::create(2026, 4, 15, 10, 0, 0, $tz),
        ]);

        $grid = BookingCalendarGrid::build($monthStart, $tz, new Collection([$booking]));

        $found = false;
        foreach ($grid['weeks'] as $week) {
            foreach ($week as $day) {
                if ($day['date']->isSameDay(Carbon::create(2026, 4, 15, 0, 0, 0, $tz))) {
                    $this->assertCount(1, $day['bookings']);
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }
}
