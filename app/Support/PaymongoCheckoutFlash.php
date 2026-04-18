<?php

namespace App\Support;

use App\Models\PaymongoBookingIntent;
use Carbon\Carbon;

final class PaymongoCheckoutFlash
{
    /**
     * Session payload for {@see session('paymongo_checkout')} on the venue booking page.
     *
     * @return array{kind: string, title: string, body: string, amount_label: string, date_label: ?string}
     */
    public static function forIntent(PaymongoBookingIntent $intent, string $scenario): array
    {
        $payload = $intent->payload_json ?? [];
        $dateLabel = null;
        $rawDate = $payload['booking_calendar_date'] ?? null;
        if (is_string($rawDate) && $rawDate !== '') {
            try {
                $dateLabel = Carbon::parse($rawDate, config('app.timezone', 'UTC'))->isoFormat('MMM D, YYYY');
            } catch (\Throwable) {
                $dateLabel = null;
            }
        }

        $amountLabel = Money::formatMinor($intent->amount_centavos, $intent->currency);

        return match ($scenario) {
            'cancelled' => [
                'kind' => 'cancelled',
                'title' => 'Checkout cancelled',
                'body' => 'No payment was charged. Your selected times are still on this page — you can try paying again when you are ready.',
                'amount_label' => $amountLabel,
                'date_label' => $dateLabel,
            ],
            'unpaid' => [
                'kind' => 'unpaid',
                'title' => 'Payment not completed',
                'body' => 'We did not receive a completed payment for this checkout, so no booking was created. Your slot selection is still here if you would like to try again.',
                'amount_label' => $amountLabel,
                'date_label' => $dateLabel,
            ],
            'failed' => [
                'kind' => 'failed',
                'title' => 'Checkout did not finish',
                'body' => 'Online payment could not be completed, so no booking was created. You can start checkout again from the review step when you are ready.',
                'amount_label' => $amountLabel,
                'date_label' => $dateLabel,
            ],
            default => [
                'kind' => 'unpaid',
                'title' => 'Payment not completed',
                'body' => 'No booking was created for this checkout.',
                'amount_label' => $amountLabel,
                'date_label' => $dateLabel,
            ],
        };
    }
}
