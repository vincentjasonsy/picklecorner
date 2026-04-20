<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\InternalTeamPlayReminderNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo rows for Admin → Team play reminders (member / player accounts only).
 * Idempotent: removes prior seeded bookings & reminder notifications for these emails, then recreates.
 */
class InternalTeamPlayDemoSeeder extends Seeder
{
    public const BOOKING_NOTE = 'seed:internal_team_play_demo';

    /** @var list<string> */
    private const DEMO_EMAILS = [
        'play-reminder-dormant@picklecorner.ph',
        'play-reminder-recent@picklecorner.ph',
        'play-reminder-upcoming@picklecorner.ph',
        'play-reminder-never@picklecorner.ph',
        'play-reminder-unsub@picklecorner.ph',
        'play-reminder-cooldown@picklecorner.ph',
    ];

    public function run(): void
    {
        $court = Court::query()
            ->where('is_available', true)
            ->whereHas('courtClient', fn ($q) => $q->wherePubliclyBookable())
            ->orderBy('court_client_id')
            ->first();

        if ($court === null) {
            $this->command?->warn('InternalTeamPlayDemoSeeder skipped: no bookable court.');

            return;
        }

        $playerTypeId = UserType::query()->where('slug', UserType::SLUG_USER)->value('id');
        if ($playerTypeId === null) {
            return;
        }

        $verified = ['email_verified_at' => now()];

        foreach (self::DEMO_EMAILS as $email) {
            $existing = User::query()->where('email', $email)->first();
            if ($existing !== null) {
                $existing->notifications()
                    ->where('type', InternalTeamPlayReminderNotification::class)
                    ->delete();
            }
        }

        Booking::query()->where('notes', self::BOOKING_NOTE)->delete();

        $now = Carbon::now();

        $makeBooking = function (User $user, Carbon $starts, Carbon $ends) use ($court): void {
            Booking::query()->create([
                'court_client_id' => $court->court_client_id,
                'court_id' => $court->id,
                'user_id' => $user->id,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'status' => Booking::STATUS_CONFIRMED,
                'amount_cents' => 30000,
                'currency' => 'PHP',
                'notes' => self::BOOKING_NOTE,
            ]);
        };

        $dormant = User::query()->updateOrCreate(
            ['email' => 'play-reminder-dormant@picklecorner.ph'],
            [
                'name' => 'Demo Member · Dormant',
                'password' => 'password',
                'user_type_id' => $playerTypeId,
                ...$verified,
            ],
        );
        $dormant->forceFill(['internal_team_play_reminders_unsubscribed_at' => null])->save();
        $s = $now->copy()->subDays(18)->setTime(10, 0);
        $makeBooking($dormant, $s, $s->copy()->addHour());

        $recent = User::query()->updateOrCreate(
            ['email' => 'play-reminder-recent@picklecorner.ph'],
            [
                'name' => 'Demo Member · Recent play',
                'password' => 'password',
                'user_type_id' => $playerTypeId,
                ...$verified,
            ],
        );
        $recent->forceFill(['internal_team_play_reminders_unsubscribed_at' => null])->save();
        $s = $now->copy()->subDays(5)->setTime(14, 0);
        $makeBooking($recent, $s, $s->copy()->addHour());

        $upcoming = User::query()->updateOrCreate(
            ['email' => 'play-reminder-upcoming@picklecorner.ph'],
            [
                'name' => 'Demo Member · Upcoming latest',
                'password' => 'password',
                'user_type_id' => $playerTypeId,
                ...$verified,
            ],
        );
        $upcoming->forceFill(['internal_team_play_reminders_unsubscribed_at' => null])->save();
        $past = $now->copy()->subDays(40)->setTime(9, 0);
        $makeBooking($upcoming, $past, $past->copy()->addHour());
        $future = $now->copy()->addDays(7)->setTime(16, 0);
        $makeBooking($upcoming, $future, $future->copy()->addHour());

        $never = User::query()->updateOrCreate(
            ['email' => 'play-reminder-never@picklecorner.ph'],
            [
                'name' => 'Demo Member · Never booked',
                'password' => 'password',
                'user_type_id' => $playerTypeId,
                ...$verified,
            ],
        );
        $never->forceFill(['internal_team_play_reminders_unsubscribed_at' => null])->save();

        $unsub = User::query()->updateOrCreate(
            ['email' => 'play-reminder-unsub@picklecorner.ph'],
            [
                'name' => 'Demo Member · Unsubscribed',
                'password' => 'password',
                'user_type_id' => $playerTypeId,
                ...$verified,
            ],
        );
        $unsub->forceFill(['internal_team_play_reminders_unsubscribed_at' => $now->copy()->subDays(3)])->save();
        $s = $now->copy()->subDays(22)->setTime(11, 0);
        $makeBooking($unsub, $s, $s->copy()->addHour());

        $cooldown = User::query()->updateOrCreate(
            ['email' => 'play-reminder-cooldown@picklecorner.ph'],
            [
                'name' => 'Demo Member · Cooldown',
                'password' => 'password',
                'user_type_id' => $playerTypeId,
                ...$verified,
            ],
        );
        $cooldown->forceFill(['internal_team_play_reminders_unsubscribed_at' => null])->save();
        $s = $now->copy()->subDays(16)->setTime(8, 0);
        $makeBooking($cooldown, $s, $s->copy()->addHour());
        $cooldown->notify(new InternalTeamPlayReminderNotification(16, []));
        $n = $cooldown->notifications()->latest()->first();
        if ($n !== null) {
            $n->forceFill([
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ])->save();
        }

        $this->command?->info('Team play reminder demo members seeded (password: password).');
    }
}
