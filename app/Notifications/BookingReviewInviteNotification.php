<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserReview;
use App\Support\UserReviewMailLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReviewInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
    ) {
        $this->booking->loadMissing(['user', 'courtClient', 'coach']);
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->booking->user;
        if ($user === null) {
            return (new MailMessage)->subject('Booking review')->line('No member on this booking.');
        }

        $venueUrl = UserReviewMailLink::signedUrl(
            $user,
            UserReview::TARGET_VENUE,
            (string) $this->booking->court_client_id,
        );

        $coachUrl = $this->booking->coach_user_id !== null
            ? UserReviewMailLink::signedUrl(
                $user,
                UserReview::TARGET_COACH,
                (string) $this->booking->coach_user_id,
            )
            : null;

        $venueName = $this->booking->courtClient?->name ?? 'the venue';
        $when = $this->booking->ends_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '';

        return (new MailMessage)
            ->subject('How was your time at '.$venueName.'?')
            ->markdown('mail.booking-review-invite', [
                'firstName' => self::firstName($user),
                'venueName' => $venueName,
                'when' => $when,
                'venueReviewUrl' => $venueUrl,
                'coachReviewUrl' => $coachUrl,
                'coachName' => $this->booking->coach?->name,
            ]);
    }

    protected static function firstName(User $user): string
    {
        $parts = preg_split('/\s+/', trim($user->name)) ?: [];

        return (string) ($parts[0] ?? 'there');
    }
}
