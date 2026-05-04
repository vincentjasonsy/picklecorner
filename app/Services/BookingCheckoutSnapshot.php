<?php

namespace App\Services;

use App\Models\Booking;

/**
 * Persisted JSON on {@see Booking::$checkout_snapshot} so member “booking details”
 * matches the review-step totals from checkout (plus per-line allocation).
 *
 * @phpstan-type MemberPublicSnapshot array{
 *     schema_version: int,
 *     currency: string,
 *     fee_rule_label: string|null,
 *     source: string,
 *     request: array{
 *         court_subtotal_cents: int,
 *         coach_fee_total_cents: int,
 *         booking_fee_total_cents: int,
 *         checkout_total_before_gift_cents: int,
 *         gift_applied_total_cents: int|null,
 *         balance_after_gift_cents: int,
 *         venue_credit_applied_total_cents: int|null,
 *         balance_after_all_credits_cents: int,
 *     },
 *     line: array{
 *         court_subtotal_cents: int,
 *         coach_fee_cents: int,
 *         court_coach_gross_cents: int,
 *         gift_applied_cents: int,
 *         court_coach_after_gift_cents: int,
 *         platform_booking_fee_cents: int,
 *         venue_credit_applied_cents: int,
 *         court_coach_cash_due_cents: int,
 *         platform_fee_cash_due_cents: int,
 *         line_total_payable_cents: int,
 *     },
 * }
 */
final class BookingCheckoutSnapshot
{
    public const SCHEMA_VERSION = 1;

    public const SOURCE_MEMBER_PUBLIC = 'member_public_checkout';

    public const SOURCE_MANUAL_DESK = 'manual_desk_or_admin';

    /**
     * Member book-now flow (same economics as {@see PublicVenueBookingSubmission}).
     *
     * @param  int|null  $requestGiftAppliedTotalCents  null when no gift card used on the request
     */
    public static function memberPublicCheckout(
        string $currency,
        ?string $feeRuleLabel,
        int $requestCourtSubtotalCents,
        int $requestCoachFeeTotalCents,
        int $requestBookingFeeTotalCents,
        int $requestCheckoutTotalBeforeGiftCents,
        ?int $requestGiftAppliedTotalCents,
        int $lineCourtSubtotalCents,
        int $lineCoachFeeCents,
        int $lineCourtCoachGrossCents,
        int $lineGiftAppliedCents,
        int $lineCourtCoachAfterGiftCents,
        int $linePlatformBookingFeeCents,
        int $lineVenueCreditAppliedCents = 0,
        ?int $lineCourtCoachCashDueCents = null,
        ?int $linePlatformFeeCashDueCents = null,
        ?int $requestVenueCreditAppliedTotalCents = null,
    ): array {
        $giftTotal = $requestGiftAppliedTotalCents;
        $balanceAfter = $giftTotal !== null
            ? max(0, $requestCheckoutTotalBeforeGiftCents - $giftTotal)
            : $requestCheckoutTotalBeforeGiftCents;

        $venueTotal = $requestVenueCreditAppliedTotalCents;
        $balanceAfterAll = $venueTotal !== null && $venueTotal > 0
            ? max(0, $balanceAfter - $venueTotal)
            : $balanceAfter;

        $cashCoach = $lineCourtCoachCashDueCents ?? $lineCourtCoachAfterGiftCents;
        $cashPlat = $linePlatformFeeCashDueCents ?? $linePlatformBookingFeeCents;
        $linePayable = max(0, $cashCoach + $cashPlat);

        $request = [
            'court_subtotal_cents' => $requestCourtSubtotalCents,
            'coach_fee_total_cents' => $requestCoachFeeTotalCents,
            'booking_fee_total_cents' => $requestBookingFeeTotalCents,
            'checkout_total_before_gift_cents' => $requestCheckoutTotalBeforeGiftCents,
            'gift_applied_total_cents' => $giftTotal,
            'balance_after_gift_cents' => $balanceAfter,
            'balance_after_all_credits_cents' => $balanceAfterAll,
        ];
        if ($venueTotal !== null && $venueTotal > 0) {
            $request['venue_credit_applied_total_cents'] = $venueTotal;
        }

        $line = [
            'court_subtotal_cents' => $lineCourtSubtotalCents,
            'coach_fee_cents' => $lineCoachFeeCents,
            'court_coach_gross_cents' => $lineCourtCoachGrossCents,
            'gift_applied_cents' => $lineGiftAppliedCents,
            'court_coach_after_gift_cents' => $lineCourtCoachAfterGiftCents,
            'platform_booking_fee_cents' => $linePlatformBookingFeeCents,
            'venue_credit_applied_cents' => $lineVenueCreditAppliedCents,
            'court_coach_cash_due_cents' => $cashCoach,
            'platform_fee_cash_due_cents' => $cashPlat,
            'line_total_payable_cents' => $linePayable,
        ];

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'currency' => $currency,
            'fee_rule_label' => $feeRuleLabel,
            'source' => self::SOURCE_MEMBER_PUBLIC,
            'request' => $request,
            'line' => $line,
        ];
    }

    /**
     * Desk / admin manual booking grid (no platform booking fee).
     *
     * @param  int|null  $requestGiftAppliedTotalCents  null when no gift on the request
     */
    public static function manualCheckout(
        string $currency,
        int $requestCourtSubtotalCents,
        int $requestCheckoutTotalBeforeGiftCents,
        ?int $requestGiftAppliedTotalCents,
        int $lineCourtSubtotalCents,
        int $lineCourtCoachGrossCents,
        int $lineGiftAppliedCents,
        int $lineCourtCoachAfterGiftCents,
    ): array {
        $giftTotal = $requestGiftAppliedTotalCents;
        $balanceAfter = $giftTotal !== null
            ? max(0, $requestCheckoutTotalBeforeGiftCents - $giftTotal)
            : $requestCheckoutTotalBeforeGiftCents;

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'currency' => $currency,
            'fee_rule_label' => null,
            'source' => self::SOURCE_MANUAL_DESK,
            'request' => [
                'court_subtotal_cents' => $requestCourtSubtotalCents,
                'coach_fee_total_cents' => 0,
                'booking_fee_total_cents' => 0,
                'checkout_total_before_gift_cents' => $requestCheckoutTotalBeforeGiftCents,
                'gift_applied_total_cents' => $giftTotal,
                'balance_after_gift_cents' => $balanceAfter,
            ],
            'line' => [
                'court_subtotal_cents' => $lineCourtSubtotalCents,
                'coach_fee_cents' => 0,
                'court_coach_gross_cents' => $lineCourtCoachGrossCents,
                'gift_applied_cents' => $lineGiftAppliedCents,
                'court_coach_after_gift_cents' => $lineCourtCoachAfterGiftCents,
                'platform_booking_fee_cents' => 0,
                'line_total_payable_cents' => max(0, $lineCourtCoachAfterGiftCents),
            ],
        ];
    }
}
