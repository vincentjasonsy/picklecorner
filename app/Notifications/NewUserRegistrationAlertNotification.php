<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistrationAlertNotification extends Notification
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
        return (new MailMessage)
            ->subject('[Pickle Corner] New user registration')
            ->markdown('mail.new-user-registration-alert', [
                'name' => (string) $this->user->name,
                'email' => (string) $this->user->email,
                'registeredAt' => optional($this->user->created_at)?->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            ]);
    }
}
