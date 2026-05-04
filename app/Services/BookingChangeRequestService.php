<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserVenueCreditLedgerEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class BookingChangeRequestService
{
    public static function defaultRefundCreditCents(Booking $booking): int
    {
        return max(0, (int) $booking->amount_cents + (int) ($booking->platform_booking_fee_cents ?? 0));
    }

    /**
     * Total account credit to restore when a pending manual booking request is denied (cash due + platform fee + wallet redeemed on the line).
     *
     * @param  iterable<int, Booking>  $bookings
     */
    public static function totalDeskDenialCreditCents(iterable $bookings): int
    {
        $sum = 0;
        foreach ($bookings as $booking) {
            $sum += self::deskDenialCreditCentsForLine($booking);
        }

        return max(0, $sum);
    }

    public static function deskDenialCreditCentsForLine(Booking $booking): int
    {
        return max(
            0,
            self::defaultRefundCreditCents($booking) + (int) ($booking->venue_credit_redeemed_cents ?? 0),
        );
    }

    public static function memberMayPlaceRequest(Booking $booking): bool
    {
        if ($booking->starts_at === null || $booking->ends_at === null) {
            return false;
        }

        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            return false;
        }

        if ($booking->starts_at->lte(now())) {
            return false;
        }

        return ! self::hasPendingRequest($booking);
    }

    public static function hasPendingRequest(Booking $booking): bool
    {
        return BookingChangeRequest::query()
            ->where('booking_id', $booking->id)
            ->where('status', BookingChangeRequest::STATUS_PENDING)
            ->exists();
    }

    public static function staffMayReview(User $staff, CourtClient $venue): bool
    {
        if ($staff->isCourtAdmin()
            && $staff->administeredCourtClient !== null
            && (string) $staff->administeredCourtClient->id === (string) $venue->id) {
            return true;
        }

        if ($staff->isCourtClientDesk()
            && $staff->desk_court_client_id !== null
            && (string) $staff->desk_court_client_id === (string) $venue->id) {
            return true;
        }

        return false;
    }

    public static function validateRescheduleWindow(Booking $booking, Carbon $newStart, Carbon $newEnd): void
    {
        if ($newEnd <= $newStart) {
            throw new \InvalidArgumentException('End time must be after start time.');
        }

        $originalMinutes = (int) $booking->starts_at->diffInMinutes($booking->ends_at);
        $newMinutes = (int) $newStart->diffInMinutes($newEnd);
        if ($originalMinutes !== $newMinutes) {
            throw new \InvalidArgumentException('Keep the same booking length when requesting a new slot.');
        }

        if ($newStart->lte(now())) {
            throw new \InvalidArgumentException('Choose a future start time.');
        }

        if (VenueBookingSpecsBuilder::bookingOverlapsCourt(
            (string) $booking->court_id,
            $newStart,
            $newEnd,
            (string) $booking->id,
        )) {
            throw new \InvalidArgumentException('That time is not available on this court. Pick another slot.');
        }

        if ($booking->coach_user_id) {
            if (CoachAvailabilityService::coachHasOverlappingBooking(
                (string) $booking->coach_user_id,
                $newStart,
                $newEnd,
                (string) $booking->id,
            )) {
                throw new \InvalidArgumentException('The coach is already booked in that window.');
            }
        }
    }

    public static function submitRefund(Booking $booking, User $member, ?string $memberNote): BookingChangeRequest
    {
        if ((string) $booking->user_id !== (string) $member->id) {
            throw new \InvalidArgumentException('Not your booking.');
        }
        if (! self::memberMayPlaceRequest($booking)) {
            throw new \InvalidArgumentException(
                'You already have a pending request, or this booking can’t be changed.',
            );
        }

        $offered = self::defaultRefundCreditCents($booking);

        $row = BookingChangeRequest::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $member->id,
            'court_client_id' => $booking->court_client_id,
            'type' => BookingChangeRequest::TYPE_REFUND_CREDIT,
            'status' => BookingChangeRequest::STATUS_PENDING,
            'member_note' => $memberNote !== null && trim($memberNote) !== '' ? trim($memberNote) : null,
            'offered_credit_cents' => $offered,
        ]);

        ActivityLogger::log(
            'booking_change_request.submitted',
            ['request_id' => $row->id, 'type' => $row->type],
            $booking,
            'Member requested credit refund',
            $member->id,
        );

        return $row;
    }

    public static function submitReschedule(
        Booking $booking,
        User $member,
        Carbon $newStart,
        Carbon $newEnd,
        ?string $memberNote,
    ): BookingChangeRequest {
        if ((string) $booking->user_id !== (string) $member->id) {
            throw new \InvalidArgumentException('Not your booking.');
        }
        if (! self::memberMayPlaceRequest($booking)) {
            throw new \InvalidArgumentException(
                'You already have a pending request, or this booking can’t be changed.',
            );
        }

        self::validateRescheduleWindow($booking, $newStart, $newEnd);

        $row = BookingChangeRequest::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $member->id,
            'court_client_id' => $booking->court_client_id,
            'type' => BookingChangeRequest::TYPE_RESCHEDULE,
            'status' => BookingChangeRequest::STATUS_PENDING,
            'member_note' => $memberNote !== null && trim($memberNote) !== '' ? trim($memberNote) : null,
            'requested_starts_at' => $newStart,
            'requested_ends_at' => $newEnd,
        ]);

        ActivityLogger::log(
            'booking_change_request.submitted',
            ['request_id' => $row->id, 'type' => $row->type],
            $booking,
            'Member requested reschedule',
            $member->id,
        );

        return $row;
    }

    public static function withdraw(BookingChangeRequest $request, User $member): void
    {
        if ((string) $request->user_id !== (string) $member->id) {
            throw new \InvalidArgumentException('Not your request.');
        }
        if ($request->status !== BookingChangeRequest::STATUS_PENDING) {
            throw new \InvalidArgumentException('This request can’t be withdrawn.');
        }

        $request->status = BookingChangeRequest::STATUS_WITHDRAWN;
        $request->save();

        ActivityLogger::log(
            'booking_change_request.withdrawn',
            ['request_id' => $request->id, 'type' => $request->type],
            $request->booking,
            'Member withdrew booking change request',
        );
    }

    public static function decline(
        BookingChangeRequest $request,
        User $staff,
        ?string $reviewNote,
    ): void {
        $venue = CourtClient::query()->findOrFail($request->court_client_id);
        abort_unless(self::staffMayReview($staff, $venue), 403);
        abort_unless($request->status === BookingChangeRequest::STATUS_PENDING, 422);

        $request->status = BookingChangeRequest::STATUS_DECLINED;
        $request->reviewed_by_user_id = $staff->id;
        $request->reviewed_at = now();
        $request->review_note = $reviewNote !== null && trim($reviewNote) !== '' ? trim($reviewNote) : null;
        $request->save();

        ActivityLogger::log(
            'booking_change_request.declined',
            ['request_id' => $request->id, 'type' => $request->type],
            $request->booking,
            'Booking change request declined',
            $staff->id,
        );
    }

    /**
     * @param  int|null  $refundCreditCentsOverride  For refund type only; null uses offered amount.
     */
    public static function accept(
        BookingChangeRequest $request,
        User $staff,
        ?int $refundCreditCentsOverride,
        ?string $reviewNote,
    ): void {
        $venue = CourtClient::query()->findOrFail($request->court_client_id);
        abort_unless(self::staffMayReview($staff, $venue), 403);
        abort_unless($request->status === BookingChangeRequest::STATUS_PENDING, 422);

        DB::transaction(function () use ($request, $staff, $refundCreditCentsOverride, $reviewNote, $venue): void {
            $locked = BookingChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== BookingChangeRequest::STATUS_PENDING) {
                throw new \RuntimeException('This request is no longer pending.');
            }

            $booking = Booking::query()->whereKey($locked->booking_id)->lockForUpdate()->firstOrFail();

            if ($locked->type === BookingChangeRequest::TYPE_REFUND_CREDIT) {
                if ($booking->status !== Booking::STATUS_CONFIRMED) {
                    throw new \InvalidArgumentException('Booking is no longer confirmed.');
                }

                $credit = $refundCreditCentsOverride ?? $locked->offered_credit_cents;
                if ($credit === null) {
                    $credit = self::defaultRefundCreditCents($booking);
                }
                $credit = max(0, (int) $credit);

                $member = User::query()->findOrFail($booking->user_id);
                UserVenueCreditService::addCredit(
                    $member,
                    $venue,
                    $credit,
                    UserVenueCreditLedgerEntry::ENTRY_TYPE_REFUND,
                    $locked,
                    'Refund as venue credit (approved)',
                );

                $booking->status = Booking::STATUS_CANCELLED;
                $booking->save();

                $locked->resolved_credit_cents = $credit;
            } elseif ($locked->type === BookingChangeRequest::TYPE_RESCHEDULE) {
                if ($booking->status !== Booking::STATUS_CONFIRMED) {
                    throw new \InvalidArgumentException('Booking is no longer confirmed.');
                }

                $newStart = $locked->requested_starts_at;
                $newEnd = $locked->requested_ends_at;
                if ($newStart === null || $newEnd === null) {
                    throw new \RuntimeException('Missing proposed times on reschedule request.');
                }

                self::validateRescheduleWindow($booking, $newStart, $newEnd);

                $booking->starts_at = $newStart;
                $booking->ends_at = $newEnd;
                $booking->save();
            } else {
                throw new \RuntimeException('Unknown request type.');
            }

            $locked->status = BookingChangeRequest::STATUS_ACCEPTED;
            $locked->reviewed_by_user_id = $staff->id;
            $locked->reviewed_at = now();
            $locked->review_note = $reviewNote !== null && trim($reviewNote) !== '' ? trim($reviewNote) : null;
            $locked->save();
        });

        ActivityLogger::log(
            'booking_change_request.accepted',
            [
                'request_id' => $request->id,
                'type' => $request->type,
                'resolved_credit_cents' => $request->fresh()->resolved_credit_cents,
            ],
            $request->booking,
            'Booking change request accepted',
            $staff->id,
        );
    }
}
