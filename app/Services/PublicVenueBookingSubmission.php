<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicVenueBookingSubmission
{
    /**
     * Create bookings for a member self-serve request (venue desk policy, no gift card, no desk_submitted_by).
     *
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, hours: list<int>}>  $specs
     * @return array{bookings: list<Booking>, desk_policy: string}
     */
    public static function submit(
        CourtClient $courtClient,
        User $booker,
        array $specs,
        ?string $notes,
        ?string $paymentMethod,
        ?string $paymentReference,
        mixed $paymentProof,
    ): array {
        if ($specs === []) {
            throw new \InvalidArgumentException('No time slots to book.');
        }

        $deskPolicy = CourtClient::DESK_BOOKING_POLICY_MANUAL;
        $deskStatus = Booking::STATUS_PENDING_APPROVAL;
        $bookingNotesForCreate = $notes !== null && $notes !== '' ? $notes : null;

        $rawPolicy = CourtClient::query()
            ->where('id', $courtClient->id)
            ->value('desk_booking_policy');
        $deskPolicy = in_array((string) $rawPolicy, CourtClient::deskBookingPolicyValues(), true)
            ? (string) $rawPolicy
            : CourtClient::DESK_BOOKING_POLICY_MANUAL;

        $deskStatus = match ($deskPolicy) {
            CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => Booking::STATUS_CONFIRMED,
            CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => Booking::STATUS_DENIED,
            default => Booking::STATUS_PENDING_APPROVAL,
        };

        if ($deskPolicy === CourtClient::DESK_BOOKING_POLICY_AUTO_DENY) {
            $suffix = 'Auto-denied by venue desk booking policy.';
            $bookingNotesForCreate = $bookingNotesForCreate !== null && $bookingNotesForCreate !== ''
                ? $bookingNotesForCreate."\n\n".$suffix
                : $suffix;
        }

        $proofPath = null;
        if ($paymentProof !== null) {
            $proofPath = $paymentProof->store(
                'venue-booking-proofs/'.Str::uuid()->toString(),
                'public',
            );
        }

        $pm = $paymentMethod !== null && $paymentMethod !== '' ? $paymentMethod : null;
        $pref = trim((string) $paymentReference);

        $bookings = DB::transaction(function () use (
            $specs,
            $courtClient,
            $booker,
            $deskStatus,
            $bookingNotesForCreate,
            $pm,
            $pref,
            $proofPath,
        ) {
            $created = [];
            foreach ($specs as $spec) {
                /** @var Court $court */
                $court = $spec['court'];
                $gross = (int) $spec['gross_cents'];
                $netCents = $gross > 0 ? $gross : null;

                $created[] = Booking::query()->create([
                    'court_client_id' => $courtClient->id,
                    'court_id' => $court->id,
                    'user_id' => $booker->id,
                    'desk_submitted_by' => null,
                    'starts_at' => $spec['starts'],
                    'ends_at' => $spec['ends'],
                    'status' => $deskStatus,
                    'amount_cents' => $netCents,
                    'currency' => $courtClient->currency ?? 'PHP',
                    'notes' => $bookingNotesForCreate,
                    'gift_card_id' => null,
                    'gift_card_redeemed_cents' => null,
                    'payment_method' => $pm,
                    'payment_reference' => $pref !== '' ? $pref : null,
                    'payment_proof_path' => $proofPath,
                ]);
            }

            return $created;
        });

        $first = $bookings[0]->fresh();
        $ids = array_map(fn (Booking $b) => $b->id, $bookings);

        match ($deskPolicy) {
            CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => ActivityLogger::log(
                'booking.desk_auto_approved',
                ['booking_ids' => $ids, 'policy' => $deskPolicy, 'source' => 'member_public'],
                $first,
                count($bookings) === 1
                    ? 'Member booking auto-approved'
                    : count($bookings).' member bookings auto-approved',
            ),
            CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => ActivityLogger::log(
                'booking.desk_auto_denied',
                ['booking_ids' => $ids, 'policy' => $deskPolicy, 'source' => 'member_public'],
                $first,
                count($bookings) === 1
                    ? 'Member booking auto-denied'
                    : count($bookings).' member bookings auto-denied',
            ),
            default => ActivityLogger::log(
                'booking.desk_submitted',
                ['booking_ids' => $ids, 'source' => 'member_public'],
                $first,
                count($bookings) === 1
                    ? 'Member booking request submitted'
                    : count($bookings).' member booking requests submitted',
            ),
        };

        return ['bookings' => $bookings, 'desk_policy' => $deskPolicy];
    }
}
