<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent synchronously so venue staff see booking mail immediately after submission,
 * without requiring a queue worker.
 */
class CourtAdminVenueBookingSubmittedNotification extends Notification
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
     *     paymentLabel: ?string,
     *     paymentReference: ?string,
     *     feeRuleLabel: ?string,
     *     bookerName: string,
     *     bookerEmail: string,
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
        $status = $this->payload['status'];
        $subjectVenue = $this->payload['venueName'];
        $booker = $this->payload['bookerName'];
        $subject = match ($status) {
            Booking::STATUS_CONFIRMED => '['.$subjectVenue.'] Confirmed booking — '.$booker,
            Booking::STATUS_DENIED => '['.$subjectVenue.'] Auto-declined request — '.$booker,
            Booking::STATUS_PENDING_APPROVAL => '['.$subjectVenue.'] New booking request — '.$booker,
            default => '['.$subjectVenue.'] Member booking — '.$booker,
        };

        return (new MailMessage)
            ->subject($subject)
            ->replyTo((string) $this->payload['bookerEmail'])
            ->markdown('mail.court-admin-venue-booking-submitted', [
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
                'bookerName' => $this->payload['bookerName'],
                'bookerEmail' => $this->payload['bookerEmail'],
                'venueBookingsUrl' => route('venue.bookings.pending', [], true),
            ]);
    }
}
