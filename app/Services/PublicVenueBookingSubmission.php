<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\User;
use App\Models\UserVenueCredit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicVenueBookingSubmission
{
    /**
     * Create bookings for a member self-serve request (venue desk policy; optional gift card; no desk_submitted_by).
     *
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, hours: list<int>}>  $specs
     * @return array{bookings: list<Booking>, desk_policy: string}
     */
    /**
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, hours: list<int>, coach_fee_cents?: int}>  $specs
     * @param  array{max_slots: int, public_notes?: string|null, host_payment_details: string, external_contact?: string|null, refund_policy?: string|null}|null  $openPlay
     * @param  string|null  $forcedBookingRequestId  When set (e.g. PayMongo), all rows share this id instead of a new UUID.
     */
    public static function submit(
        CourtClient $courtClient,
        User $booker,
        array $specs,
        ?string $notes,
        ?string $paymentMethod,
        ?string $paymentReference,
        mixed $paymentProof,
        ?string $giftCardCodeRaw = null,
        ?string $coachUserId = null,
        ?array $openPlay = null,
        ?string $forcedBookingRequestId = null,
        bool $applyVenueCredit = false,
    ): array {
        if ($specs === []) {
            throw new \InvalidArgumentException('No time slots to book.');
        }

        if ($openPlay !== null && count($specs) !== 1) {
            throw new \InvalidArgumentException('Open play is only available when booking a single court time block.');
        }

        $pm = $paymentMethod !== null && $paymentMethod !== '' ? $paymentMethod : null;

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

        if ($deskPolicy === CourtClient::DESK_BOOKING_POLICY_AUTO_DENY && $pm !== Booking::PAYMENT_PAYMONGO) {
            $suffix = 'Auto-denied by venue desk booking policy.';
            $bookingNotesForCreate = $bookingNotesForCreate !== null && $bookingNotesForCreate !== ''
                ? $bookingNotesForCreate."\n\n".$suffix
                : $suffix;
        }

        if ($pm === Booking::PAYMENT_PAYMONGO) {
            $deskStatus = Booking::STATUS_CONFIRMED;
        }

        $proofPath = null;
        if ($paymentProof !== null) {
            $proofPath = $paymentProof->store(
                'venue-booking-proofs/'.Str::uuid()->toString(),
                'public',
            );
        }

        $pref = trim((string) $paymentReference);
        $giftCodeRaw = trim((string) $giftCardCodeRaw);

        $coachId = $coachUserId !== null && $coachUserId !== '' ? (string) $coachUserId : null;

        $bookings = DB::transaction(function () use (
            $specs,
            $courtClient,
            $booker,
            $deskStatus,
            $bookingNotesForCreate,
            $pm,
            $pref,
            $proofPath,
            $giftCodeRaw,
            $coachId,
            $openPlay,
            $forcedBookingRequestId,
            $applyVenueCredit,
        ) {
            $bookingRequestId = ($forcedBookingRequestId !== null && $forcedBookingRequestId !== '')
                ? (string) $forcedBookingRequestId
                : (string) Str::uuid();

            $totalGross = (int) array_sum(array_column($specs, 'gross_cents'));
            $courtSubtotalCents = (int) array_sum(array_column($specs, 'court_gross_cents'));
            $bookingFeeCents = BookingFeeService::calculateCentsForSpecs($specs);
            $totalDueForGift = $totalGross + $bookingFeeCents;

            $giftCardId = null;
            $giftAppliedTotal = null;
            $lockedGiftCard = null;
            if ($giftCodeRaw !== '') {
                $lockedGiftCard = GiftCardService::lockCardForDebit(
                    $courtClient->id,
                    $giftCodeRaw,
                );
                $giftAppliedTotal = GiftCardService::computeAppliedCents($lockedGiftCard, $totalDueForGift);
                if ($giftAppliedTotal <= 0) {
                    throw new \InvalidArgumentException('Nothing to apply from this gift card.');
                }
                $giftCardId = $lockedGiftCard->id;
            }

            $grosses = array_column($specs, 'gross_cents');
            $grossesForGift = GiftCardService::augmentGrossesWithBookingFee($grosses, $bookingFeeCents);
            $platformFeePerLine = [];
            foreach ($specs as $fi => $_spec) {
                $platformFeePerLine[$fi] = max(
                    0,
                    ($grossesForGift[$fi] ?? 0) - ($grosses[$fi] ?? 0),
                );
            }
            $giftSlices = $giftAppliedTotal !== null
                ? GiftCardService::allocateAppliedCentsAcrossLines($giftAppliedTotal, $grossesForGift)
                : array_fill(0, count($specs), 0);

            $coachFeeTotalCents = (int) array_sum(array_column($specs, 'coach_fee_cents'));
            $feeRuleLabel = currentBookingFeeSetting()->breakdownLabel();

            if ($lockedGiftCard !== null && $giftCardId !== null) {
                $nWithGift = count(array_filter($giftSlices, fn (int $s): bool => $s > 0));
                if ($nWithGift > 0) {
                    GiftCardService::assertRedemptionLimits(
                        $lockedGiftCard,
                        $booker->id,
                        $nWithGift,
                    );
                }
            }

            CourtBookingConcurrency::lockCourtsAndAssertNoOverlap($specs);

            $nSpecs = count($specs);
            $netAfterGiftPerLine = [];
            for ($gi = 0; $gi < $nSpecs; $gi++) {
                $grossGi = (int) $specs[$gi]['gross_cents'];
                $sliceGi = $giftSlices[$gi] ?? 0;
                $netAfterGiftPerLine[$gi] = max(0, $grossGi - $sliceGi);
            }
            $linePayables = [];
            for ($gi = 0; $gi < $nSpecs; $gi++) {
                $linePayables[$gi] = $netAfterGiftPerLine[$gi] + (int) ($platformFeePerLine[$gi] ?? 0);
            }
            $venueCreditGrandTotal = 0;
            $venueSlices = array_fill(0, $nSpecs, 0);
            if ($applyVenueCredit) {
                $totalAfterGiftPayable = (int) array_sum($linePayables);
                if ($totalAfterGiftPayable > 0) {
                    $creditWallet = UserVenueCredit::query()
                        ->where('user_id', $booker->id)
                        ->where('currency', $courtClient->currency ?? 'PHP')
                        ->lockForUpdate()
                        ->first();
                    $balance = $creditWallet !== null ? (int) $creditWallet->balance_cents : 0;
                    $venueCreditGrandTotal = min($balance, $totalAfterGiftPayable);
                    if ($venueCreditGrandTotal > 0) {
                        $venueSlices = GiftCardService::allocateAppliedCentsAcrossLines($venueCreditGrandTotal, $linePayables);
                    }
                }
            }

            $created = [];
            foreach ($specs as $i => $spec) {
                /** @var Court $court */
                $court = $spec['court'];
                $gross = (int) $spec['gross_cents'];
                $slice = $giftSlices[$i] ?? 0;
                $netAfterGift = $netAfterGiftPerLine[$i] ?? max(0, $gross - $slice);

                $coachFee = (int) ($spec['coach_fee_cents'] ?? 0);

                $useOpenPlay = $openPlay !== null && count($specs) === 1;
                $publicNotes = $useOpenPlay ? trim((string) ($openPlay['public_notes'] ?? '')) : '';
                $hostPay = $useOpenPlay ? trim((string) ($openPlay['host_payment_details'] ?? '')) : '';
                $extContact = $useOpenPlay ? trim((string) ($openPlay['external_contact'] ?? '')) : '';
                $refundPolicy = $useOpenPlay ? trim((string) ($openPlay['refund_policy'] ?? '')) : '';

                $linePlatform = (int) ($platformFeePerLine[$i] ?? 0);
                $courtGrossLine = (int) ($spec['court_gross_cents'] ?? 0);

                $vSlice = (int) ($venueSlices[$i] ?? 0);
                [$finalNet, $finalPlatform] = VenueBookingCheckoutAmounts::reducePayableBySlice(
                    $netAfterGift,
                    $linePlatform,
                    $vSlice,
                );

                $booking = Booking::query()->create([
                    'court_client_id' => $courtClient->id,
                    'booking_request_id' => $bookingRequestId,
                    'court_id' => $court->id,
                    'user_id' => $booker->id,
                    'coach_user_id' => $coachId,
                    'desk_submitted_by' => null,
                    'starts_at' => $spec['starts'],
                    'ends_at' => $spec['ends'],
                    'status' => $deskStatus,
                    'amount_cents' => $finalNet > 0 ? $finalNet : null,
                    'coach_fee_cents' => $coachFee > 0 ? $coachFee : null,
                    'platform_booking_fee_cents' => $finalPlatform > 0 ? $finalPlatform : null,
                    'checkout_snapshot' => BookingCheckoutSnapshot::memberPublicCheckout(
                        currency: $courtClient->currency ?? 'PHP',
                        feeRuleLabel: $feeRuleLabel,
                        requestCourtSubtotalCents: $courtSubtotalCents,
                        requestCoachFeeTotalCents: $coachFeeTotalCents,
                        requestBookingFeeTotalCents: $bookingFeeCents,
                        requestCheckoutTotalBeforeGiftCents: $totalDueForGift,
                        requestGiftAppliedTotalCents: $giftAppliedTotal,
                        lineCourtSubtotalCents: $courtGrossLine,
                        lineCoachFeeCents: $coachFee,
                        lineCourtCoachGrossCents: $gross,
                        lineGiftAppliedCents: $slice,
                        lineCourtCoachAfterGiftCents: $netAfterGift,
                        linePlatformBookingFeeCents: $linePlatform,
                        lineVenueCreditAppliedCents: $vSlice,
                        lineCourtCoachCashDueCents: $finalNet,
                        linePlatformFeeCashDueCents: $finalPlatform,
                        requestVenueCreditAppliedTotalCents: $venueCreditGrandTotal > 0 ? $venueCreditGrandTotal : null,
                    ),
                    'currency' => $courtClient->currency ?? 'PHP',
                    'notes' => $bookingNotesForCreate,
                    'gift_card_id' => $slice > 0 ? $giftCardId : null,
                    'gift_card_redeemed_cents' => $slice > 0 ? $slice : null,
                    'venue_credit_redeemed_cents' => $vSlice > 0 ? $vSlice : null,
                    'payment_method' => $pm,
                    'payment_reference' => $pref !== '' ? $pref : null,
                    'payment_proof_path' => $proofPath,
                    'is_open_play' => $useOpenPlay,
                    'open_play_max_slots' => $useOpenPlay ? (int) $openPlay['max_slots'] : null,
                    'open_play_public_notes' => $useOpenPlay && $publicNotes !== '' ? $publicNotes : null,
                    'open_play_host_payment_details' => $useOpenPlay && $hostPay !== '' ? $hostPay : null,
                    'open_play_external_contact' => $useOpenPlay && $extContact !== '' ? $extContact : null,
                    'open_play_refund_policy' => $useOpenPlay && $refundPolicy !== '' ? $refundPolicy : null,
                ]);

                if ($slice > 0 && $giftCardId !== null) {
                    GiftCardService::recordBookingRedemption($booking, $giftCardId, $slice);
                }

                $created[] = $booking;
            }

            if ($venueCreditGrandTotal > 0 && $created !== []) {
                UserVenueCreditService::debitForCheckout(
                    $booker,
                    $courtClient,
                    $venueCreditGrandTotal,
                    $created[0],
                    'Venue credit applied to checkout ('.count($created).' court line(s)).',
                );

                ActivityLogger::log(
                    'venue_credit.redeemed_checkout',
                    [
                        'amount_cents' => $venueCreditGrandTotal,
                        'booking_ids' => array_map(fn (Booking $b) => $b->id, $created),
                    ],
                    $created[0],
                    'Venue credit applied to member checkout',
                    $booker->id,
                );
            }

            if ($giftAppliedTotal !== null && $giftCardId !== null && $giftAppliedTotal > 0) {
                $card = GiftCard::query()->find($giftCardId);
                ActivityLogger::log(
                    'gift_card.redeemed',
                    [
                        'amount_cents' => $giftAppliedTotal,
                        'booking_ids' => array_map(fn (Booking $b) => $b->id, $created),
                        'source' => 'member_public',
                    ],
                    $card,
                    $card ? "Gift card {$card->code} applied to member booking request" : 'Gift card applied to member booking request',
                );
            }

            return $created;
        });

        $first = $bookings[0]->fresh();
        $ids = array_map(fn (Booking $b) => $b->id, $bookings);

        $requestId = $first->booking_request_id;

        match ($deskPolicy) {
            CourtClient::DESK_BOOKING_POLICY_AUTO_APPROVE => ActivityLogger::log(
                'booking.desk_auto_approved',
                [
                    'booking_ids' => $ids,
                    'booking_request_id' => $requestId,
                    'policy' => $deskPolicy,
                    'source' => 'member_public',
                ],
                $first,
                count($bookings) === 1
                    ? 'Member booking auto-approved'
                    : 'Member booking request auto-approved ('.count($bookings).' courts)',
            ),
            CourtClient::DESK_BOOKING_POLICY_AUTO_DENY => ActivityLogger::log(
                'booking.desk_auto_denied',
                [
                    'booking_ids' => $ids,
                    'booking_request_id' => $requestId,
                    'policy' => $deskPolicy,
                    'source' => 'member_public',
                ],
                $first,
                count($bookings) === 1
                    ? 'Member booking auto-denied'
                    : 'Member booking request auto-denied ('.count($bookings).' courts)',
            ),
            default => ActivityLogger::log(
                'booking.desk_submitted',
                [
                    'booking_ids' => $ids,
                    'booking_request_id' => $requestId,
                    'source' => 'member_public',
                ],
                $first,
                count($bookings) === 1
                    ? 'Member booking request submitted'
                    : 'Member booking request submitted ('.count($bookings).' courts)',
            ),
        };

        VenueBookingConfirmationNotifier::notifyMemberPublicSubmission($courtClient, $booker, $bookings);

        return ['bookings' => $bookings, 'desk_policy' => $deskPolicy];
    }
}
