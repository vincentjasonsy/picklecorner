<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\InternalTeamPlayReminder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InternalTeamPlayReminderNotification extends Notification
{
    /**
     * @param  list<array{
     *     venue_name: string,
     *     court_name: string,
     *     city: ?string,
     *     environment_label: string,
     *     book_url: string,
     *     venue_book_url: string,
     *     picked_for_you: bool,
     *     badge: string,
     * }>  $courts
     */
    public function __construct(
        public int $days,
        public array $courts,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $first = $notifiable instanceof User
            ? InternalTeamPlayReminder::firstName($notifiable)
            : 'Champ';

        $unsubscribeUrl = $notifiable instanceof User
            ? URL::signedRoute('internal-team-play-reminders.unsubscribe', ['user' => $notifiable])
            : route('home');

        return (new MailMessage)
            ->subject($this->mailSubject())
            ->markdown('mail.internal-team-play-reminder', [
                'firstName' => $first,
                'days' => $this->days,
                'courts' => $this->courts,
                'tips' => InternalTeamPlayReminder::suggestionLines(),
                'bookUrl' => route('account.book'),
                'browseUrl' => route('book-now'),
                'unsubscribeUrl' => $unsubscribeUrl,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $first = $notifiable instanceof User
            ? InternalTeamPlayReminder::firstName($notifiable)
            : 'Champ';

        $unsubscribeUrl = $notifiable instanceof User
            ? URL::signedRoute('internal-team-play-reminders.unsubscribe', ['user' => $notifiable])
            : route('home');

        return [
            'kind' => 'internal_team_play_reminder',
            'title' => 'Time to book another game?',
            'body' => "Hi {$first} — it's been {$this->days} days since your last booking. Here are some courts you can grab right now.",
            'days' => $this->days,
            'book_url' => route('account.book'),
            'browse_url' => route('book-now'),
            'unsubscribe_url' => $unsubscribeUrl,
            'courts' => array_slice($this->courts, 0, 6),
            'tips' => array_slice(InternalTeamPlayReminder::suggestionLines(), 0, 3),
        ];
    }

    private function mailSubject(): string
    {
        return "We miss you on court — {$this->days} days since your last game";
    }
}
