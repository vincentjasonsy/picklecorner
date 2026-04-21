<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserWelcomeNotification extends Notification
{
    public function __construct(
        public User $user,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $parts = preg_split('/\s+/', trim((string) $this->user->name)) ?: [];
        $firstName = (string) ($parts[0] ?? 'there');

        return (new MailMessage)
            ->subject('Welcome to Pickle Corner')
            ->markdown('mail.new-user-welcome', [
                'firstName' => $firstName,
                'dashboardUrl' => $this->user->memberHomeUrl(),
            ]);
    }
}
