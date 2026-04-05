<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\GiftCardRedemption;
use Illuminate\Support\Str;

final class GiftCardService
{
    public static function normalizeCode(string $raw): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($raw)));
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = 'GIFT-'.strtoupper(Str::random(10));
        } while (GiftCard::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @param  string  $valueType  {@see GiftCard::VALUE_FIXED} or {@see GiftCard::VALUE_PERCENT}
     * @param  int  $faceValueCents  Fixed: peso value applied (up to booking gross) on every redemption. Percent: display/reference only (redemption uses full % of gross each time).
     * @param  int|null  $percentOff  1–100 when value type is percent; must be null for fixed
     * @param  CourtClient|null  $client  Null issues a platform-wide card redeemable at any venue.
     * @param  int|null  $maxRedemptionsTotal  Max booking rows that can apply this code (null = unlimited).
     * @param  int|null  $maxRedemptionsPerUser  Max booking rows per guest user (null = unlimited).
     */
    public static function issue(
        ?CourtClient $client,
        string $valueType,
        int $faceValueCents,
        ?int $percentOff,
        ?string $title = null,
        ?string $eventLabel = null,
        $validFrom = null,
        $validUntil = null,
        ?string $customCode = null,
        ?string $notes = null,
        ?string $createdByUserId = null,
        ?int $maxRedemptionsTotal = null,
        ?int $maxRedemptionsPerUser = null,
    ): GiftCard {
        if (! in_array($valueType, [GiftCard::VALUE_FIXED, GiftCard::VALUE_PERCENT], true)) {
            throw new \InvalidArgumentException('Invalid gift card value type.');
        }

        if ($valueType === GiftCard::VALUE_FIXED) {
            if ($percentOff !== null) {
                throw new \InvalidArgumentException('Fixed-value cards cannot set a percentage.');
            }
            if ($faceValueCents < 1) {
                throw new \InvalidArgumentException('Face value must be positive.');
            }
        } else {
            if ($percentOff === null || $percentOff < 1 || $percentOff > 100) {
                throw new \InvalidArgumentException('Percentage must be between 1 and 100.');
            }
            if ($faceValueCents < 1) {
                throw new \InvalidArgumentException('Reference amount must be positive for percent cards.');
            }
        }

        if ($maxRedemptionsTotal !== null && $maxRedemptionsTotal < 1) {
            throw new \InvalidArgumentException('Max total uses must be at least 1 when set.');
        }

        if ($maxRedemptionsPerUser !== null && $maxRedemptionsPerUser < 1) {
            throw new \InvalidArgumentException('Max uses per user must be at least 1 when set.');
        }

        if ($maxRedemptionsTotal !== null && $maxRedemptionsPerUser !== null && $maxRedemptionsPerUser > $maxRedemptionsTotal) {
            throw new \InvalidArgumentException('Max uses per user cannot exceed max total uses.');
        }

        $code = $customCode !== null && $customCode !== ''
            ? self::normalizeCode($customCode)
            : self::generateUniqueCode();

        if (GiftCard::query()->where('code', $code)->exists()) {
            throw new \InvalidArgumentException('This gift card code is already in use.');
        }

        return GiftCard::query()->create([
            'court_client_id' => $client?->id,
            'code' => $code,
            'title' => $title !== '' ? $title : null,
            'event_label' => $eventLabel !== '' ? $eventLabel : null,
            'value_type' => $valueType,
            'percent_off' => $valueType === GiftCard::VALUE_PERCENT ? $percentOff : null,
            'face_value_cents' => $faceValueCents,
            'balance_cents' => $faceValueCents,
            'currency' => $client?->currency ?? 'PHP',
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'created_by' => $createdByUserId,
            'notes' => $notes !== '' ? $notes : null,
            'max_redemptions_total' => $maxRedemptionsTotal,
            'max_redemptions_per_user' => $maxRedemptionsPerUser,
        ]);
    }

    /**
     * Lock row for update. Validates venue scope and {@see GiftCard::redeemableNow()}.
     *
     * @throws \InvalidArgumentException
     */
    public static function lockCardForDebit(string $courtClientId, string $rawCode): GiftCard
    {
        $normalized = self::normalizeCode($rawCode);
        /** @var GiftCard|null $card */
        $card = GiftCard::query()
            ->where('code', $normalized)
            ->where(function ($q) use ($courtClientId): void {
                $q->where('court_client_id', $courtClientId)
                    ->orWhereNull('court_client_id');
            })
            ->lockForUpdate()
            ->first();

        if ($card === null) {
            throw new \InvalidArgumentException('Gift card code not found for this venue.');
        }

        if (! $card->redeemableNow()) {
            throw new \InvalidArgumentException('This gift card cannot be redeemed right now.');
        }

        return $card;
    }

    public static function computeAppliedCents(GiftCard $card, int $grossCents): int
    {
        $candidate = $card->computeDiscountFromGross($grossCents);
        if ($card->isFixedValue()) {
            return min(max(0, (int) $card->face_value_cents), $candidate);
        }

        return $candidate;
    }

    /**
     * Each booking row that applies the gift counts as one use toward limits.
     *
     * @throws \InvalidArgumentException
     */
    public static function assertRedemptionLimits(GiftCard $card, ?string $forUserId, int $newBookingsWithGift): void
    {
        if ($newBookingsWithGift < 1) {
            return;
        }

        if ($card->max_redemptions_total !== null) {
            $current = Booking::query()->where('gift_card_id', $card->id)->count();
            if ($current + $newBookingsWithGift > $card->max_redemptions_total) {
                throw new \InvalidArgumentException('This gift card has reached its maximum number of uses.');
            }
        }

        if ($forUserId !== null && $card->max_redemptions_per_user !== null) {
            $current = Booking::query()
                ->where('gift_card_id', $card->id)
                ->where('user_id', $forUserId)
                ->count();
            if ($current + $newBookingsWithGift > $card->max_redemptions_per_user) {
                throw new \InvalidArgumentException('This guest has reached the maximum uses of this gift card.');
            }
        }
    }

    /**
     * Lock row and compute discount for this booking. Does not reduce {@see GiftCard::$balance_cents}
     * (codes are reusable: fixed cards apply up to face value each time; percent cards apply the full percentage each time).
     *
     * @param  ?string  $forUserId  Guest user id when enforcing per-user limits.
     * @param  ?int  $newBookingsWithGift  How many booking rows will apply this code in this transaction (defaults to 1).
     * @return array{gift_card_id: string, applied_cents: int}
     */
    public static function debitForGrossAmount(
        string $courtClientId,
        string $rawCode,
        int $grossCents,
        ?string $forUserId = null,
        ?int $newBookingsWithGift = null,
    ): array {
        $card = self::lockCardForDebit($courtClientId, $rawCode);

        $applied = self::computeAppliedCents($card, $grossCents);

        if ($applied <= 0) {
            throw new \InvalidArgumentException('Nothing to apply from this gift card.');
        }

        self::assertRedemptionLimits($card, $forUserId, $newBookingsWithGift ?? 1);

        return [
            'gift_card_id' => $card->id,
            'applied_cents' => $applied,
        ];
    }

    public static function recordBookingRedemption(Booking $booking, string $giftCardId, int $appliedCents): GiftCardRedemption
    {
        return GiftCardRedemption::query()->create([
            'gift_card_id' => $giftCardId,
            'booking_id' => $booking->id,
            'amount_cents' => $appliedCents,
            'note' => null,
        ]);
    }
}
