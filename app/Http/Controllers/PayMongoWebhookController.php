<?php

namespace App\Http\Controllers;

use App\Models\PaymongoBookingIntent;
use App\Services\PaymongoVenueBookingPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $type = data_get($payload, 'data.attributes.type');

        if ($type === 'checkout_session.payment.paid') {
            $sessionId = data_get($payload, 'data.attributes.data.id')
                ?? data_get($payload, 'data.attributes.data.data.id');

            if (! is_string($sessionId) || $sessionId === '') {
                $embedded = data_get($payload, 'data.attributes.data');
                if (is_array($embedded) && isset($embedded['id']) && is_string($embedded['id'])) {
                    $sessionId = $embedded['id'];
                }
            }

            $paymentId = data_get($payload, 'data.attributes.data.attributes.payments.0.id')
                ?? data_get($payload, 'data.attributes.data.attributes.payments.0.data.id');

            foreach ($payload['included'] ?? [] as $inc) {
                if (! is_array($inc)) {
                    continue;
                }
                if (($inc['type'] ?? '') === 'payment' && (($inc['attributes']['status'] ?? '') === 'paid')) {
                    $paymentId = $inc['id'] ?? $paymentId;
                    break;
                }
            }

            if (is_string($sessionId) && $sessionId !== '') {
                $intent = PaymongoBookingIntent::query()
                    ->where('paymongo_checkout_session_id', $sessionId)
                    ->first();

                if ($intent !== null) {
                    try {
                        if (is_string($paymentId) && $paymentId !== '') {
                            PaymongoVenueBookingPayment::completeIntent($intent, $paymentId);
                        } else {
                            PaymongoVenueBookingPayment::tryCompleteIntentFromPaidCheckoutSession($intent);
                        }
                    } catch (\Throwable $e) {
                        Log::error('paymongo.webhook.complete_failed', [
                            'intent_id' => $intent->id,
                            'message' => $e->getMessage(),
                        ]);
                        report($e);
                    }
                }
            }
        }

        if ($type === 'payment.paid') {
            $paymentWrapper = data_get($payload, 'data.attributes.data');
            $intentId = data_get($payload, 'data.attributes.data.attributes.metadata.intent_id');
            $paymentId = is_array($paymentWrapper) ? ($paymentWrapper['id'] ?? null) : null;

            if (! is_string($intentId) || $intentId === '') {
                foreach ($payload['included'] ?? [] as $inc) {
                    if (! is_array($inc) || ($inc['type'] ?? '') !== 'payment') {
                        continue;
                    }
                    $intentId = data_get($inc, 'attributes.metadata.intent_id');
                    $paymentId = $inc['id'] ?? $paymentId;
                    if (is_string($intentId) && $intentId !== '') {
                        break;
                    }
                }
            }

            if (is_string($intentId) && $intentId !== '') {
                $intent = PaymongoBookingIntent::query()->find($intentId);
                if ($intent !== null) {
                    try {
                        if (is_string($paymentId) && $paymentId !== '') {
                            PaymongoVenueBookingPayment::completeIntent($intent, $paymentId);
                        } else {
                            PaymongoVenueBookingPayment::tryCompleteIntentFromPaidCheckoutSession($intent);
                        }
                    } catch (\Throwable $e) {
                        Log::error('paymongo.webhook.complete_failed', [
                            'intent_id' => $intent->id,
                            'message' => $e->getMessage(),
                        ]);
                        report($e);
                    }
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
