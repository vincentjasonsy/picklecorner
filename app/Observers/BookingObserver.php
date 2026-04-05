<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\ActivityLogger;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        ActivityLogger::log(
            'booking.created',
            [
                'starts_at' => $booking->starts_at?->toIso8601String(),
                'ends_at' => $booking->ends_at?->toIso8601String(),
                'status' => $booking->status,
                'amount_cents' => $booking->amount_cents,
                'gift_card_redeemed_cents' => $booking->gift_card_redeemed_cents,
                'payment_method' => $booking->payment_method,
                'payment_reference' => $booking->payment_reference,
                'payment_proof_path' => $booking->payment_proof_path,
            ],
            $booking,
            "Booking #{$booking->getKey()} created",
        );
    }

    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged([
            'status',
            'starts_at',
            'ends_at',
            'amount_cents',
            'gift_card_redeemed_cents',
            'payment_method',
            'payment_reference',
            'payment_proof_path',
        ])) {
            return;
        }

        ActivityLogger::log(
            'booking.updated',
            [
                'changes' => $booking->getChanges(),
                'original' => collect($booking->getOriginal())->only(array_keys($booking->getChanges()))->all(),
            ],
            $booking,
            "Booking #{$booking->getKey()} updated",
        );
    }

    public function deleted(Booking $booking): void
    {
        ActivityLogger::log(
            'booking.deleted',
            [
                'court_client_id' => $booking->court_client_id,
                'user_id' => $booking->user_id,
            ],
            null,
            "Booking #{$booking->getKey()} deleted",
        );
    }
}
