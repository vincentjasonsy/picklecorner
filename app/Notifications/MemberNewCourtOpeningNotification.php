<?php

namespace App\Notifications;

use App\Models\Court;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberNewCourtOpeningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $courtId,
        public bool $isUpcomingOpening,
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
        $court = Court::query()->with(['courtClient'])->find($this->courtId);
        if ($court === null) {
            return (new MailMessage)->subject('Court update')->line('This court listing is no longer available.');
        }

        $venue = $court->courtClient;
        $venueName = $venue?->name ?? 'Partner venue';
        $city = trim((string) ($venue?->city ?? ''));

        $courtUrl = route('book-now.court', ['court' => $court->id], true);
        $bookNowUrl = route('book-now', [], true);

        $tz = config('app.timezone', 'UTC');
        $opensLabel = '';
        if ($court->opens_at !== null) {
            $opensLabel = $court->opens_at->timezone($tz)->format('M j, Y \a\t g:i A');
        }

        $user = $notifiable instanceof User ? $notifiable : null;
        $firstName = $user !== null ? self::firstName($user) : 'there';

        $placeTag = $city !== '' ? $city : config('app.name');

        if ($this->isUpcomingOpening && $opensLabel !== '') {
            $subject = '['.$placeTag.'] Coming soon · '.$court->name.' · '.$venueName;

            return (new MailMessage)
                ->subject($subject)
                ->markdown('mail.member-new-court-opening', [
                    'firstName' => $firstName,
                    'kind' => 'upcoming',
                    'venueName' => $venueName,
                    'courtName' => $court->name,
                    'city' => $city,
                    'environmentLabel' => $court->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor',
                    'opensLabel' => $opensLabel,
                    'courtUrl' => $courtUrl,
                    'bookNowUrl' => $bookNowUrl,
                ]);
        }

        $subject = '['.$placeTag.'] New court · '.$court->name.' · '.$venueName;

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.member-new-court-opening', [
                'firstName' => $firstName,
                'kind' => 'new',
                'venueName' => $venueName,
                'courtName' => $court->name,
                'city' => $city,
                'environmentLabel' => $court->environment === Court::ENV_INDOOR ? 'Indoor' : 'Outdoor',
                'opensLabel' => $opensLabel,
                'courtUrl' => $courtUrl,
                'bookNowUrl' => $bookNowUrl,
            ]);
    }

    protected static function firstName(User $user): string
    {
        $parts = preg_split('/\s+/', trim($user->name)) ?: [];

        return (string) ($parts[0] ?? 'there');
    }
}
