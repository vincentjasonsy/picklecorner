<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Notifications\InternalTeamPlayReminderNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Eligibility and court picks for member (player) “get back on court” reminders.
 */
final class InternalTeamPlayReminder
{
    public const DAYS_THRESHOLD = 10;

    public const DAYS_BETWEEN_REMINDERS = 14;

    public const MAX_COURTS_IN_EMAIL = 10;

    public const MAX_PICKED_FROM_HISTORY = 5;

    public static function latestBookingAsBooker(User $user): ?Booking
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->orderByDesc('starts_at')
            ->first();
    }

    public static function lastPastBookingAsBooker(User $user): ?Booking
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->first();
    }

    /**
     * Calendar days since the user's most recent past booking as booker (any age), or null if none.
     */
    public static function daysSinceLastPastBookingAsBooker(User $user): ?int
    {
        $last = self::lastPastBookingAsBooker($user);
        if ($last === null) {
            return null;
        }

        return (int) $last->starts_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
    }

    /**
     * Days since last booking for automated reminder eligibility (≥ threshold, latest slot not in the future).
     */
    public static function daysSinceLastBooking(User $user): ?int
    {
        $latest = self::latestBookingAsBooker($user);
        if ($latest === null || $latest->starts_at->isFuture()) {
            return null;
        }

        $days = (int) $latest->starts_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
        if ($days < self::DAYS_THRESHOLD) {
            return null;
        }

        return $days;
    }

    public static function lastInternalReminderSentAt(User $user): ?Carbon
    {
        $at = $user->notifications()
            ->where('type', InternalTeamPlayReminderNotification::class)
            ->latest('created_at')
            ->value('created_at');

        return $at !== null ? Carbon::parse($at) : null;
    }

    /**
     * @return array{
     *     user: User,
     *     latest_booking_starts_at: ?Carbon,
     *     latest_is_upcoming: bool,
     *     last_past_booking_starts_at: ?Carbon,
     *     days_since_last_past: ?int,
     *     dormant_10_plus: bool,
     *     eligible_for_scheduled_reminder: bool,
     *     unsubscribed: bool,
     *     last_reminder_sent_at: ?Carbon,
     *     next_scheduled_window_at: ?Carbon,
     * }
     */
    public static function dashboardRow(User $user): array
    {
        $latest = self::latestBookingAsBooker($user);
        $lastPast = self::lastPastBookingAsBooker($user);
        $daysSincePast = self::daysSinceLastPastBookingAsBooker($user);
        $lastReminder = self::lastInternalReminderSentAt($user);
        $unsubscribed = $user->internal_team_play_reminders_unsubscribed_at !== null;

        $nextWindow = $lastReminder?->copy()->addDays(self::DAYS_BETWEEN_REMINDERS);

        return [
            'user' => $user,
            'latest_booking_starts_at' => $latest?->starts_at,
            'latest_is_upcoming' => $latest !== null && $latest->starts_at->isFuture(),
            'last_past_booking_starts_at' => $lastPast?->starts_at,
            'days_since_last_past' => $daysSincePast,
            'dormant_10_plus' => $daysSincePast !== null && $daysSincePast >= self::DAYS_THRESHOLD,
            'eligible_for_scheduled_reminder' => self::shouldSend($user),
            'unsubscribed' => $unsubscribed,
            'last_reminder_sent_at' => $lastReminder,
            'next_scheduled_window_at' => $nextWindow,
        ];
    }

    public static function shouldSend(User $user): bool
    {
        if (! $user->isPlayer()) {
            return false;
        }

        if ($user->internal_team_play_reminders_unsubscribed_at !== null) {
            return false;
        }

        if (self::daysSinceLastBooking($user) === null) {
            return false;
        }

        return ! $user->notifications()
            ->where('type', InternalTeamPlayReminderNotification::class)
            ->where('created_at', '>=', now()->subDays(self::DAYS_BETWEEN_REMINDERS))
            ->exists();
    }

    /**
     * @return Collection<int, Court>
     */
    public static function availableCourts(): Collection
    {
        return Court::query()
            ->where('is_available', true)
            ->whereHas('courtClient', fn ($q) => $q->where('is_active', true))
            ->with('courtClient')
            ->orderBy('court_client_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{
     *     venue_name: string,
     *     court_name: string,
     *     city: ?string,
     *     environment_label: string,
     *     book_url: string,
     *     venue_book_url: string,
     *     picked_for_you: bool,
     *     badge: string,
     * }>
     */
    public static function courtSuggestionsForUser(User $user): array
    {
        $all = self::availableCourts();
        if ($all->isEmpty()) {
            return [];
        }

        $preferredVenueIds = $user->bookings()
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_DENIED])
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->limit(40)
            ->pluck('court_client_id')
            ->unique()
            ->values();

        $rows = [];
        $seen = [];

        foreach ($preferredVenueIds as $venueId) {
            foreach ($all->where('court_client_id', $venueId) as $court) {
                if (isset($seen[$court->id])) {
                    continue;
                }
                if (count($rows) >= self::MAX_PICKED_FROM_HISTORY) {
                    break 2;
                }
                $seen[$court->id] = true;
                $rows[] = ['court' => $court, 'picked_for_you' => true];
            }
        }

        foreach ($all as $court) {
            if (isset($seen[$court->id])) {
                continue;
            }
            if (count($rows) >= self::MAX_COURTS_IN_EMAIL) {
                break;
            }
            $seen[$court->id] = true;
            $rows[] = ['court' => $court, 'picked_for_you' => false];
        }

        return array_map(
            fn (array $row) => self::courtToPayload($row['court'], $row['picked_for_you']),
            $rows,
        );
    }

    /**
     * @return array{
     *     venue_name: string,
     *     court_name: string,
     *     city: ?string,
     *     environment_label: string,
     *     book_url: string,
     *     venue_book_url: string,
     *     picked_for_you: bool,
     *     badge: string,
     * }
     */
    private static function courtToPayload(Court $court, bool $pickedForYou): array
    {
        $client = $court->courtClient;
        $venueName = $client?->name ?? 'Venue';

        $environmentLabel = $court->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor';

        return [
            'venue_name' => $venueName,
            'court_name' => $court->name,
            'city' => $client?->city,
            'environment_label' => $environmentLabel,
            'book_url' => route('book-now.court', $court),
            'venue_book_url' => $client ? route('book-now.venue.book', $client) : route('book-now'),
            'picked_for_you' => $pickedForYou,
            'badge' => $pickedForYou ? 'Your usual venues' : 'More courts',
        ];
    }

    public static function firstName(User $user): string
    {
        $name = trim((string) $user->name);

        return $name !== '' ? (string) Str::of($name)->before(' ') : 'Champ';
    }

    /**
     * @return list<string>
     */
    public static function suggestionLines(): array
    {
        return [
            'Grab a peak-time slot if you like energy on the court — quieter mornings are great for drilling.',
            'Try a new venue or surface; it keeps the game fresh and you might find a new home club.',
            'Round up a partner or a foursome — games are easier to lock in when everyone commits together.',
        ];
    }
}
