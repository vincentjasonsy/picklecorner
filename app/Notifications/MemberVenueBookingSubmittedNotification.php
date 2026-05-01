<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent synchronously so members receive mail as soon as the booking succeeds,
 * without requiring a queue worker (QUEUE_CONNECTION / artisan queue:work).
 */
class MemberVenueBookingSubmittedNotification extends Notification
{
    /**
     * @param  array{
     *     venueName: string,
     *     status: string,
     *     statusLabel: string,
     *     lines: list<array{court: string, when: string}>,
     *     currency: string,
     *     requestTotals: array<string, mixed>,
     *     bookingRequestId: string,
     *     firstBookingId: string,
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
        $channels = ['database'];
        if ($notifiable instanceof User && self::notifiableHasEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array{
     *     kind: string,
     *     title: string,
     *     body: string,
     *     venue_name: string,
     *     status: string,
     *     status_label: string,
     *     lines: list<array{court: string, when: string}>,
     *     booking_url: string,
     * }
     */
    public function toDatabase(object $notifiable): array
    {
        $venue = $this->payload['venueName'];
        $status = $this->payload['status'];
        $label = $this->payload['statusLabel'];

        $title = match ($status) {
            Booking::STATUS_CONFIRMED => 'Booking confirmed',
            Booking::STATUS_DENIED => 'Booking request not accepted',
            Booking::STATUS_PENDING_APPROVAL => 'Booking request received',
            default => 'Booking update',
        };

        $firstLine = $this->payload['lines'][0] ?? null;
        $detail = '';
        if (is_array($firstLine)) {
            $detail = trim(($firstLine['court'] ?? '').((isset($firstLine['when']) && $firstLine['when'] !== '') ? ' · '.$firstLine['when'] : ''));
        }
        $body = $detail !== '' ? $detail : ($label.' · '.$venue);

        $bid = $this->payload['firstBookingId'] ?? '';

        return [
            'kind' => 'member_venue_booking',
            'title' => $title.' · '.$venue,
            'body' => $body,
            'venue_name' => $venue,
            'status' => $status,
            'status_label' => $label,
            'lines' => array_slice($this->payload['lines'], 0, 5),
            'booking_url' => $bid !== ''
                ? route('account.bookings.show', ['booking' => $bid], true)
                : route('account.bookings', [], true),
        ];
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

    private static function notifiableHasEmail(User $user): bool
    {
        $email = $user->email;

        return is_string($email) && trim($email) !== '';
    }
}
