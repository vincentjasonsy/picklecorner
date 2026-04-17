<?php

namespace App\Services;

use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\MemberNewCourtOpeningNotification;

final class CourtOpeningMailNotifier
{
    /**
     * Notify marketing-opted members in the venue city when a court becomes bookable / announced.
     */
    public static function maybeSendForCourt(Court $court): void
    {
        if (! config('booking.court_opening_emails')) {
            return;
        }

        $court->loadMissing('courtClient');

        /** @var CourtClient|null $venue */
        $venue = $court->courtClient;
        if ($venue === null || ! $venue->is_active || ! $court->is_available) {
            return;
        }

        $city = trim((string) ($venue->city ?? ''));
        if ($city === '') {
            return;
        }

        $alreadySent = Court::query()->whereKey($court->id)->value('opening_notice_sent_at');
        if ($alreadySent !== null) {
            return;
        }

        $opensAt = $court->opens_at;
        $isUpcoming = $opensAt !== null && $opensAt->isFuture();

        $users = self::eligibleRecipientsForCity($city);
        foreach ($users as $user) {
            $user->notify(new MemberNewCourtOpeningNotification($court->id, $isUpcoming));
        }

        Court::query()->whereKey($court->id)->update([
            'opening_notice_sent_at' => now(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private static function eligibleRecipientsForCity(string $city): \Illuminate\Database\Eloquent\Collection
    {
        $normalized = mb_strtolower(trim($city));

        $typeIds = UserType::query()
            ->whereIn('slug', [UserType::SLUG_USER, UserType::SLUG_OPEN_PLAY_HOST])
            ->pluck('id');

        return User::query()
            ->whereIn('user_type_id', $typeIds)
            ->whereNotNull('marketing_emails_consent_at')
            ->whereNotNull('home_city')
            ->whereRaw('LOWER(TRIM(home_city)) = ?', [$normalized])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get();
    }
}
