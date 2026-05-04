<?php

namespace App\Services;

use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\User;
use App\Models\UserVenueCredit;

/**
 * Single source for member venue checkout totals (gift card + optional venue credit wallet).
 */
final class VenueBookingCheckoutAmounts
{
    /**
     * @param  list<array{court: mixed, starts: mixed, ends: mixed, gross_cents: int, hours: list<int>}>  $specs
     * @return array{
     *     total_gross: int,
     *     booking_fee: int,
     *     checkout_total: int,
     *     gift_applied: int,
     *     after_gift: int,
     *     venue_credit_applied: int,
     *     payable: int,
     * }
     */
    public static function preview(
        CourtClient $courtClient,
        User $booker,
        array $specs,
        string $giftCardCodeRaw,
        bool $applyVenueCredit,
    ): array {
        $totalGross = (int) array_sum(array_column($specs, 'gross_cents'));
        $bookingFeeCents = BookingFeeService::calculateCentsForSpecs($specs);
        $checkoutTotal = $totalGross + $bookingFeeCents;

        $giftApplied = 0;
        $normalizedGift = GiftCardService::normalizeCode(trim($giftCardCodeRaw));
        if ($normalizedGift !== '') {
            $card = GiftCard::query()
                ->where('code', $normalizedGift)
                ->where(function ($q) use ($courtClient): void {
                    $q->where('court_client_id', $courtClient->id)
                        ->orWhereNull('court_client_id');
                })
                ->first();
            if ($card !== null && $card->redeemableNow()) {
                $giftApplied = GiftCardService::computeAppliedCents($card, $checkoutTotal);
            }
        }

        $afterGift = max(0, $checkoutTotal - $giftApplied);

        $venueApplied = 0;
        if ($applyVenueCredit && $afterGift > 0) {
            $currency = $courtClient->currency ?? 'PHP';
            $balance = (int) (UserVenueCredit::query()
                ->where('user_id', $booker->id)
                ->where('currency', $currency)
                ->value('balance_cents') ?? 0);
            $venueApplied = min(max(0, $balance), $afterGift);
        }

        $payable = max(0, $afterGift - $venueApplied);

        return [
            'total_gross' => $totalGross,
            'booking_fee' => $bookingFeeCents,
            'checkout_total' => $checkoutTotal,
            'gift_applied' => $giftApplied,
            'after_gift' => $afterGift,
            'venue_credit_applied' => $venueApplied,
            'payable' => $payable,
        ];
    }

    /**
     * Apply a venue-credit slice against court+coach cash first, then platform fee (same order as refund math).
     *
     * @return array{0: int, 1: int} [finalCourtCoachCashCents, finalPlatformFeeCents]
     */
    public static function reducePayableBySlice(int $courtCoachAfterGiftCents, int $platformFeeCents, int $sliceCents): array
    {
        $rem = $sliceCents;
        $take = min($courtCoachAfterGiftCents, $rem);
        $net = $courtCoachAfterGiftCents - $take;
        $rem -= $take;
        $take2 = min($platformFeeCents, $rem);
        $plat = $platformFeeCents - $take2;

        return [max(0, $net), max(0, $plat)];
    }
}
