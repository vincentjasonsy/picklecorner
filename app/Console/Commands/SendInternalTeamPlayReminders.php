<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserType;
use App\Notifications\InternalTeamPlayReminderNotification;
use App\Support\InternalTeamPlayReminder;
use Illuminate\Console\Command;

class SendInternalTeamPlayReminders extends Command
{
    protected $signature = 'internal:send-team-play-reminders';

    protected $description = 'Email + in-app nudge for member (player) accounts that have not booked court time recently.';

    public function handle(): int
    {
        $playerTypeId = UserType::query()->where('slug', UserType::SLUG_USER)->value('id');
        if ($playerTypeId === null) {
            $this->warn('No member (user) type found.');

            return self::FAILURE;
        }

        $sent = 0;

        User::query()
            ->where('user_type_id', $playerTypeId)
            ->orderBy('email')
            ->each(function (User $user) use (&$sent): void {
                if (! InternalTeamPlayReminder::shouldSend($user)) {
                    return;
                }

                $days = InternalTeamPlayReminder::daysSinceLastBooking($user);
                if ($days === null) {
                    return;
                }

                $courts = InternalTeamPlayReminder::courtSuggestionsForUser($user);
                $user->notify(new InternalTeamPlayReminderNotification($days, $courts));
                $sent++;
                $this->line("Sent reminder to {$user->email}");
            });

        $this->info("Done. {$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
