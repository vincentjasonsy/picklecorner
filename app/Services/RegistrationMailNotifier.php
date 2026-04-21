<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewUserRegistrationAlertNotification;
use App\Notifications\NewUserWelcomeNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class RegistrationMailNotifier
{
    public static function notify(User $user): void
    {
        if (! self::hasNonEmptyEmail($user->email)) {
            return;
        }

        try {
            $user->notify(new NewUserWelcomeNotification($user));
        } catch (Throwable $e) {
            report($e);
        }

        $alertEmail = trim((string) config('booking.registration_alert_email', ''));
        if ($alertEmail === '') {
            return;
        }

        try {
            Notification::route('mail', $alertEmail)->notify(new NewUserRegistrationAlertNotification($user));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function hasNonEmptyEmail(mixed $email): bool
    {
        return is_string($email) && trim($email) !== '';
    }
}
