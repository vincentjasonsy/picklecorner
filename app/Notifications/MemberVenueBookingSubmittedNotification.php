<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberVenueBookingSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{
     *     venueName: string,
     *     status: string,
     *     statusLabel: string,
     *     lines: list<array{court: string, when: string}>,
     *     currency: string,
     *     requestTotals: array<string, mixed>,
     *     bookingRequestId: string,
     *     paymentLabel: ?string,
     *     paymentReference: ?string,
     *     feeRuleLabel: ?string,
     * }  $payload
     */
    public function __construct(
        public array $payload,
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
        $user = $notifiable instanceof User ? $notifiable : null;
        $firstName = $user !== null ? self::firstName($user) : 'there';

        $status = $this->payload['status'];
        $subjectVenue = $this->payload['venueName'];
        $subject = match ($status) {
            Booking::STATUS_CONFIRMED => '['.$subjectVenue.'] Booking confirmed',
            Booking::STATUS_DENIED => '['.$subjectVenue.'] Booking request not accepted',
            Booking::STATUS_PENDING_APPROVAL => '['.$subjectVenue.'] Booking request received',
            default => '['.$subjectVenue.'] Booking update',
        };

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.member-venue-booking-submitted', [
                'firstName' => $firstName,
                'venueName' => $this->payload['venueName'],
                'status' => $status,
                'statusLabel' => $this->payload['statusLabel'],
                'lines' => $this->payload['lines'],
                'currency' => $this->payload['currency'],
                'requestTotals' => $this->payload['requestTotals'],
                'bookingRequestId' => $this->payload['bookingRequestId'],
                'paymentLabel' => $this->payload['paymentLabel'],
                'paymentReference' => $this->payload['paymentReference'],
                'feeRuleLabel' => $this->payload['feeRuleLabel'],
                'bookingsUrl' => route('account.bookings', [], true),
            ]);
    }

    protected static function firstName(User $user): string
    {
        $parts = preg_split('/\s+/', trim($user->name)) ?: [];

        return (string) ($parts[0] ?? 'there');
    }
}
