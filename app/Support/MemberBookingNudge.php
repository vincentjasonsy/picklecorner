<?php

namespace App\Support;

use App\Models\User;

/**
 * In-app “you haven’t booked in a while” prompt for the member area.
 */
final class MemberBookingNudge
{
    public static function cacheKey(User $user): string
    {
        return 'member.booking_nudge.dismissed.'.$user->getKey();
    }

    /**
     * Player or coach in the member app, not staff; no upcoming booking as booker; dormant or never booked.
     */
    public static function shouldPrompt(User $user): bool
    {
        if ($user->usesStaffAppNav()) {
            return false;
        }

        if (! $user->isPlayer() && ! $user->isCoach() && ! $user->isOpenPlayHost()) {
            return false;
        }

        $row = InternalTeamPlayReminder::dashboardRow($user);

        if ($row['latest_is_upcoming']) {
            return false;
        }

        $neverBookedAfterDays = (int) config('booking.member_booking_nudge_never_booked_after_days', 2);
        $dormantAfterDays = (int) config('booking.member_booking_nudge_after_days', InternalTeamPlayReminder::DAYS_THRESHOLD);

        if ($row['last_past_booking_starts_at'] === null) {
            return $user->created_at !== null
                && $user->created_at->lte(now()->subDays(max(1, $neverBookedAfterDays)));
        }

        $days = $row['days_since_last_past'];

        return $days !== null && $days >= $dormantAfterDays;
    }

    /**
     * @return array{headline: string, body: string, days: ?int, never_booked: bool}
     */
    public static function copy(User $user): array
    {
        $row = InternalTeamPlayReminder::dashboardRow($user);
        $never = $row['last_past_booking_starts_at'] === null;

        if ($never) {
            return [
                'headline' => 'Ready to hit the court?',
                'body' => 'You don’t have any bookings yet. Grab a slot at a partner venue — it only takes a minute.',
                'days' => null,
                'never_booked' => true,
            ];
        }

        $days = $row['days_since_last_past'] ?? 0;

        return [
            'headline' => 'We miss you out there!',
            'body' => "It’s been about {$days} days since your last booking. Lock in a new game while your favorite times are still open.",
            'days' => $days,
            'never_booked' => false,
        ];
    }
}
