<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CourtClient;
use App\Models\GiftCard;
use App\Models\PaymongoBookingIntent;
use App\Models\User;
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
        $cancelUrl = route('book-now.venue.book', $courtClient);

        $paymentMethodTypes = config('paymongo.payment_method_types', ['gcash', 'qrph']);
        if ($paymentMethodTypes === []) {
            $paymentMethodTypes = ['gcash', 'qrph'];
        }

        $body = [
            'data' => [
                'attributes' => [
                    'line_items' => [
                        [
                            'currency' => $courtClient->currency ?? 'PHP',
                            'amount' => $amountCentavos,
                            'name' => 'Court booking',
                            'quantity' => 1,
                            'description' => $courtClient->name,
                        ],
                    ],
                    'payment_method_types' => $paymentMethodTypes,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'description' => 'Court booking — '.$courtClient->name,
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

            $totalGross = (int) array_sum(array_column($specs, 'gross_cents'));
            $courtSubtotalCents = (int) array_sum(array_column($specs, 'court_gross_cents'));
            $bookingFeeCents = BookingFeeService::calculateCentsFromCourtSubtotalCents($courtSubtotalCents);
            $checkoutTotal = $totalGross + $bookingFeeCents;

            $balance = $checkoutTotal;
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
                    $balance = max(0, $checkoutTotal - $applied);
                }
            }

            if ($balance !== $locked->amount_centavos) {
                throw new \RuntimeException(
                    'PayMongo intent '.$locked->id.' amount mismatch (expected '.$locked->amount_centavos.', computed '.$balance.').',
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
}
