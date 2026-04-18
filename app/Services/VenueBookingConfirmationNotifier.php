<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\User;
use App\Notifications\CourtAdminVenueBookingSubmittedNotification;
use App\Notifications\MemberVenueBookingSubmittedNotification;
use Illuminate\Support\Collection;

final class VenueBookingConfirmationNotifier
{
    /**
     * Notify the member and venue court admin after a public (member) venue booking submission.
     *
     * @param  Collection<int, Booking>|list<Booking>  $bookings
     */
    public static function notifyMemberPublicSubmission(CourtClient $courtClient, User $booker, Collection|array $bookings): void
    {
        $collection = $bookings instanceof Collection ? $bookings : collect($bookings);
        if ($collection->isEmpty()) {
            return;
        }

        $first = $collection->first();
        if ($first === null) {
            return;
        }

        $courtClient->loadMissing('admin');

        $sorted = $collection->sortBy(fn (Booking $b) => $b->starts_at)->values();
        foreach ($sorted as $booking) {
            $booking->loadMissing('court');
        }

        $lines = [];
        foreach ($sorted as $booking) {
            $courtName = $booking->court?->name ?? 'Court';
            $tz = config('app.timezone');
            $starts = $booking->starts_at;
            $ends = $booking->ends_at;
            $when = '';
            if ($starts !== null && $ends !== null) {
                $when = $starts->timezone($tz)->format('M j, Y ')
                    .$starts->timezone($tz)->format('g:i A').' – '.$ends->timezone($tz)->format('g:i A');
            }
            $lines[] = [
                'court' => $courtName,
                'when' => $when,
            ];
        }

        $snapshot = $first->checkout_snapshot;
        $snapshotArr = is_array($snapshot) ? $snapshot : null;
        $requestTotals = $snapshotArr['request'] ?? null;
        $feeRuleLabel = isset($snapshotArr['fee_rule_label']) && is_string($snapshotArr['fee_rule_label'])
            ? $snapshotArr['fee_rule_label']
            : null;
        $currency = $first->currency ?? $courtClient->currency ?? 'PHP';

        $paymentLabel = $first->payment_method !== null && $first->payment_method !== ''
            ? Booking::paymentMethodLabel((string) $first->payment_method)
            : null;
        $paymentReference = $first->payment_reference !== null && trim((string) $first->payment_reference) !== ''
            ? trim((string) $first->payment_reference)
            : null;

        $status = (string) $first->status;

        $memberData = [
            'venueName' => $courtClient->name,
            'status' => $status,
            'statusLabel' => Booking::statusDisplayLabel($status),
            'lines' => $lines,
            'currency' => $currency,
            'requestTotals' => is_array($requestTotals) ? $requestTotals : [],
            'bookingRequestId' => (string) ($first->booking_request_id ?? ''),
            'firstBookingId' => (string) $first->id,
            'paymentLabel' => $paymentLabel,
            'paymentReference' => $paymentReference,
            'feeRuleLabel' => $feeRuleLabel,
            'isOnlinePayment' => (string) ($first->payment_method ?? '') === Booking::PAYMENT_PAYMONGO,
        ];

        $booker->notify(new MemberVenueBookingSubmittedNotification($memberData));

        $admin = $courtClient->admin;
        if ($admin !== null && self::hasNonEmptyEmail($admin) && $admin->id !== $booker->id) {
            $admin->notify(new CourtAdminVenueBookingSubmittedNotification(
                array_merge($memberData, [
                    'bookerName' => $booker->name,
                    'bookerEmail' => $booker->email,
                ]),
            ));
        }
    }

    private static function hasNonEmptyEmail(User $user): bool
    {
        $email = $user->email;

        return is_string($email) && trim($email) !== '';
    }
}
