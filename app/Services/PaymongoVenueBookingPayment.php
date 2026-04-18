<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\PaymongoBookingIntent;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PaymongoVenueBookingPayment
{
    /**
     * Creates a PayMongo Checkout Session and returns the hosted payment URL.
     *
     * @param  array{max_slots: int, public_notes?: string|null, host_payment_details: string, external_contact?: string|null, refund_policy?: string|null}|null  $openPlayPayload
     *
     * @throws \RuntimeException
     */
    public static function createCheckoutRedirect(
        CourtClient $courtClient,
        User $booker,
        array $scheduleRows,
        string $bookingCalendarDate,
        array $selectedSlots,
        string $giftCardCode,
        ?string $coachUserId,
        int $coachPaidHours,
        bool $venueCheckoutShowCoach,
        bool $isOpenPlay,
        ?array $openPlayPayload,
        int $amountCentavos,
    ): string {
        $secret = config('paymongo.secret_key');
        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('PayMongo is not configured.');
        }

        if ($amountCentavos < 100) {
            throw new \RuntimeException('Amount is below the minimum for online checkout.');
        }

        if (! VenueBookingSpecsBuilder::eachCourtHasOnlyContiguousHours($selectedSlots)) {
            throw new \RuntimeException(
                'On each court, select one continuous block of hours with no gaps.',
            );
        }

        $specs = VenueBookingSpecsBuilder::buildSpecsForSubmit(
            $courtClient,
            $scheduleRows,
            $bookingCalendarDate,
            $selectedSlots,
            (string) ($coachUserId ?? ''),
            $coachPaidHours,
            $venueCheckoutShowCoach,
        );

        if ($specs === []) {
            throw new \RuntimeException('No time slots to book for PayMongo checkout.');
        }

        $amounts = self::amountsForSpecsAndGift($courtClient, $specs, $giftCardCode);

        if ($amounts['payable'] !== $amountCentavos) {
            throw new \RuntimeException('Checkout amount mismatch; refresh this page and try again.');
        }

        $bookingRequestId = (string) Str::uuid();

        $payload = [
            'booking_request_id' => $bookingRequestId,
            'schedule_rows' => $scheduleRows,
            'booking_calendar_date' => $bookingCalendarDate,
            'selected_slots' => array_values($selectedSlots),
            'gift_card_code' => $giftCardCode,
            'coach_user_id' => $coachUserId,
            'coach_paid_hours' => $coachPaidHours,
            'venue_checkout_show_coach' => $venueCheckoutShowCoach,
            'is_open_play' => $isOpenPlay,
            'open_play' => $openPlayPayload,
            'expected_balance_centavos' => $amountCentavos,
        ];

        $intent = PaymongoBookingIntent::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $booker->id,
            'court_client_id' => $courtClient->id,
            'amount_centavos' => $amountCentavos,
            'currency' => $courtClient->currency ?? 'PHP',
            'payload_json' => $payload,
            'status' => PaymongoBookingIntent::STATUS_PENDING,
        ]);

        $successUrl = route('paymongo.booking.return', ['intent' => $intent->id]);
        $cancelUrl = route('paymongo.booking.cancel', ['intent' => $intent->id]);

        $paymentMethodTypes = config('paymongo.payment_method_types', ['gcash', 'qrph']);
        if ($paymentMethodTypes === []) {
            $paymentMethodTypes = ['gcash', 'qrph'];
        }

        $lineItems = self::paymongoLineItemsForAmounts($courtClient, $amounts, $specs);

        $sessionDescription = 'Court booking — '.$courtClient->name;
        if ($amounts['booking_fee'] > 0) {
            $sessionDescription .= ' (includes non-refundable convenience fee)';
        }

        $body = [
            'data' => [
                'attributes' => [
                    'line_items' => $lineItems,
                    'payment_method_types' => $paymentMethodTypes,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'description' => $sessionDescription,
                    'send_email_receipt' => false,
                    'metadata' => [
                        'intent_id' => $intent->id,
                        'booking_request_id' => $bookingRequestId,
                    ],
                ],
            ],
        ];

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->asJson()
            ->post('https://api.paymongo.com/v1/checkout_sessions', $body);

        if (! $response->successful()) {
            $intent->update(['status' => PaymongoBookingIntent::STATUS_FAILED]);

            throw new \RuntimeException(
                'PayMongo could not start checkout: '.($response->json('errors.0.detail') ?? $response->body() ?: 'HTTP '.$response->status()),
            );
        }

        $checkoutUrl = $response->json('data.attributes.checkout_url');
        $sessionId = $response->json('data.id');

        if (! is_string($checkoutUrl) || $checkoutUrl === '' || ! is_string($sessionId) || $sessionId === '') {
            $intent->update(['status' => PaymongoBookingIntent::STATUS_FAILED]);

            throw new \RuntimeException('PayMongo returned an unexpected response.');
        }

        $intent->update([
            'paymongo_checkout_session_id' => $sessionId,
        ]);

        return $checkoutUrl;
    }

    /**
     * After redirect from hosted checkout: fetch session from PayMongo and complete the booking if payment is paid.
     * Use this when webhooks are delayed, unreachable (local dev), or payloads differ.
     */
    public static function tryCompleteIntentFromPaidCheckoutSession(PaymongoBookingIntent $intent): bool
    {
        if ($intent->status === PaymongoBookingIntent::STATUS_COMPLETED) {
            return true;
        }

        $sessionId = $intent->paymongo_checkout_session_id;
        if (! is_string($sessionId) || $sessionId === '') {
            return false;
        }

        $secret = config('paymongo.secret_key');
        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->get('https://api.paymongo.com/v1/checkout_sessions/'.$sessionId, [
                'include' => 'payments',
            ]);

        if (! $response->successful()) {
            Log::warning('paymongo.checkout_session.retrieve_failed', [
                'intent_id' => $intent->id,
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return false;
        }

        $paymentId = self::extractPaidPaymentIdFromCheckoutSessionPayload($response->json() ?? []);

        if (! is_string($paymentId) || $paymentId === '') {
            Log::info('paymongo.checkout_session.no_paid_payment_yet', [
                'intent_id' => $intent->id,
                'session_id' => $sessionId,
            ]);

            return false;
        }

        try {
            self::completeIntent($intent->fresh(['courtClient']), $paymentId);
        } catch (\Throwable $e) {
            Log::error('paymongo.return.complete_failed', [
                'intent_id' => $intent->id,
                'message' => $e->getMessage(),
            ]);
            report($e);

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function extractPaidPaymentIdFromCheckoutSessionPayload(array $payload): ?string
    {
        foreach ($payload['included'] ?? [] as $resource) {
            if (! is_array($resource)) {
                continue;
            }
            if (($resource['type'] ?? '') !== 'payment') {
                continue;
            }
            if (($resource['attributes']['status'] ?? '') !== 'paid') {
                continue;
            }
            $id = $resource['id'] ?? null;
            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        $root = $payload['data'] ?? null;
        if (! is_array($root)) {
            return null;
        }

        $attrs = $root['attributes'] ?? null;
        if (! is_array($attrs)) {
            return null;
        }

        foreach ($attrs['payments'] ?? [] as $payment) {
            if (! is_array($payment)) {
                continue;
            }
            $status = data_get($payment, 'attributes.status');
            if ($status === 'paid') {
                $id = $payment['id'] ?? null;

                return is_string($id) && $id !== '' ? $id : null;
            }
        }

        foreach ($attrs['payments'] ?? [] as $payment) {
            if (! is_array($payment)) {
                continue;
            }
            $id = $payment['id'] ?? null;
            if (is_string($id) && str_starts_with($id, 'pay_')) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Completes the booking after PayMongo confirms payment (webhook).
     *
     * @throws \Throwable
     */
    public static function completeIntent(PaymongoBookingIntent $intent, string $paymongoPaymentId): void
    {
        if ($intent->status === PaymongoBookingIntent::STATUS_COMPLETED) {
            return;
        }

        DB::transaction(function () use ($intent, $paymongoPaymentId): void {
            $locked = PaymongoBookingIntent::query()->whereKey($intent->id)->lockForUpdate()->first();
            if ($locked === null || $locked->status === PaymongoBookingIntent::STATUS_COMPLETED) {
                return;
            }

            /** @var array<string, mixed> $payload */
            $payload = $locked->payload_json ?? [];
            $courtClient = CourtClient::query()
                ->with(['weeklyHours'])
                ->findOrFail($locked->court_client_id);
            $booker = User::query()->findOrFail($locked->user_id);

            $scheduleRows = $payload['schedule_rows'] ?? [];
            if (! is_array($scheduleRows)) {
                $scheduleRows = [];
            }
            $bookingCalendarDate = is_string($payload['booking_calendar_date'] ?? null)
                ? (string) $payload['booking_calendar_date']
                : '';
            $selectedSlots = $payload['selected_slots'] ?? [];
            if (! is_array($selectedSlots)) {
                $selectedSlots = [];
            }
            $selectedSlots = array_values(array_filter(array_map('strval', $selectedSlots)));

            $giftCardCode = trim(is_string($payload['gift_card_code'] ?? null) ? (string) $payload['gift_card_code'] : '');
            $coachUserId = isset($payload['coach_user_id']) && is_string($payload['coach_user_id']) && $payload['coach_user_id'] !== ''
                ? (string) $payload['coach_user_id']
                : null;
            $coachPaidHours = (int) ($payload['coach_paid_hours'] ?? 0);
            $venueCheckoutShowCoach = (bool) ($payload['venue_checkout_show_coach'] ?? false);
            $isOpenPlay = (bool) ($payload['is_open_play'] ?? false);
            $openPlayPayload = $payload['open_play'] ?? null;
            if ($openPlayPayload !== null && ! is_array($openPlayPayload)) {
                $openPlayPayload = null;
            }

            $specs = VenueBookingSpecsBuilder::buildSpecsForSubmit(
                $courtClient,
                $scheduleRows,
                $bookingCalendarDate,
                $selectedSlots,
                (string) ($coachUserId ?? ''),
                $coachPaidHours,
                $venueCheckoutShowCoach,
            );

            if ($specs === []) {
                throw new \RuntimeException('PayMongo intent '.$locked->id.' could not rebuild booking specs.');
            }

            $amounts = self::amountsForSpecsAndGift($courtClient, $specs, $giftCardCode);

            if ($amounts['payable'] !== $locked->amount_centavos) {
                throw new \RuntimeException(
                    'PayMongo intent '.$locked->id.' amount mismatch (expected '.$locked->amount_centavos.', computed '.$amounts['payable'].').',
                );
            }

            $bookingRequestId = is_string($payload['booking_request_id'] ?? null)
                ? (string) $payload['booking_request_id']
                : null;

            $openForSubmit = null;
            if ($isOpenPlay && count($specs) === 1 && is_array($openPlayPayload)) {
                $openForSubmit = $openPlayPayload;
            }

            PublicVenueBookingSubmission::submit(
                $courtClient,
                $booker,
                $specs,
                null,
                Booking::PAYMENT_PAYMONGO,
                $paymongoPaymentId,
                null,
                $giftCardCode,
                $coachUserId,
                $openForSubmit,
                $bookingRequestId,
            );

            $locked->update([
                'status' => PaymongoBookingIntent::STATUS_COMPLETED,
                'paymongo_payment_id' => $paymongoPaymentId,
                'booking_request_id' => $bookingRequestId,
            ]);
        });
    }

    /**
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, court_gross_cents?: int, hours: list<int>, coach_fee_cents?: int}>  $specs
     * @return array{total_gross: int, booking_fee: int, checkout_total: int, payable: int}
     */
    private static function amountsForSpecsAndGift(CourtClient $courtClient, array $specs, string $giftCardCode): array
    {
        $totalGross = (int) array_sum(array_column($specs, 'gross_cents'));
        $bookingFeeCents = BookingFeeService::calculateCentsForSpecs($specs);
        $checkoutTotal = $totalGross + $bookingFeeCents;
        $payable = $checkoutTotal;

        $normalizedGift = GiftCardService::normalizeCode(trim($giftCardCode));
        if ($normalizedGift !== '') {
            $card = GiftCard::query()
                ->where('code', $normalizedGift)
                ->where(function ($q) use ($courtClient): void {
                    $q->where('court_client_id', $courtClient->id)
                        ->orWhereNull('court_client_id');
                })
                ->first();
            if ($card !== null && $card->redeemableNow()) {
                $applied = GiftCardService::computeAppliedCents($card, $checkoutTotal);
                $payable = max(0, $checkoutTotal - $applied);
            }
        }

        return [
            'total_gross' => $totalGross,
            'booking_fee' => $bookingFeeCents,
            'checkout_total' => $checkoutTotal,
            'payable' => $payable,
        ];
    }

    /**
     * Hosted checkout line items. When both court rental and a convenience fee apply, we send a single line item whose
     * description breaks out each part — PayMongo’s hosted page also shows a separate “Fees” row for PayMongo’s own
     * payment-method charges (often “Free”), which is unrelated to our convenience fee.
     *
     * @param  array{total_gross: int, booking_fee: int, checkout_total: int, payable: int}  $amounts
     * @param  list<array{court: Court, starts: Carbon, ends: Carbon, gross_cents: int, court_gross_cents?: int, hours: list<int>, coach_fee_cents?: int}>  $specs
     * @return list<array{currency: string, amount: int, name: string, quantity: int, description: string}>
     */
    private static function paymongoLineItemsForAmounts(CourtClient $courtClient, array $amounts, array $specs): array
    {
        $currency = $courtClient->currency ?? 'PHP';
        $payable = $amounts['payable'];
        $totalGross = $amounts['total_gross'];
        $bookingFee = $amounts['booking_fee'];
        $checkoutTotal = $amounts['checkout_total'];

        if ($checkoutTotal <= 0) {
            throw new \RuntimeException('Invalid checkout total for PayMongo line items.');
        }

        if ($bookingFee <= 0) {
            return [[
                'currency' => $currency,
                'amount' => $payable,
                'name' => 'Venue booking',
                'quantity' => 1,
                'description' => $courtClient->name,
            ]];
        }

        $venuePortion = (int) floor($payable * $totalGross / $checkoutTotal);
        $feePortion = $payable - $venuePortion;

        $coachCents = (int) array_sum(array_column($specs, 'coach_fee_cents'));
        $venueLineName = $coachCents > 0 ? 'Courts & coach' : 'Court rental';

        if ($venuePortion <= 0) {
            return [[
                'currency' => $currency,
                'amount' => $payable,
                'name' => 'Convenience fee',
                'quantity' => 1,
                'description' => 'Non-refundable convenience fee ('.config('app.name').')',
            ]];
        }

        if ($feePortion <= 0) {
            return [[
                'currency' => $currency,
                'amount' => $payable,
                'name' => $venueLineName,
                'quantity' => 1,
                'description' => $courtClient->name,
            ]];
        }

        $rentalPartLabel = $coachCents > 0 ? 'Courts & coach' : 'Court rental';
        $breakdownDescription = Money::formatMinor($venuePortion, $currency).' '.$rentalPartLabel.' + '
            .Money::formatMinor($feePortion, $currency).' convenience fee (non-refundable). '
            .$courtClient->name.'. Any separate “Fees” row on this screen is the processor payment fee, not this convenience fee.';

        return [[
            'currency' => $currency,
            'amount' => $payable,
            'name' => 'Court booking (includes convenience fee)',
            'quantity' => 1,
            'description' => $breakdownDescription,
        ]];
    }
}
