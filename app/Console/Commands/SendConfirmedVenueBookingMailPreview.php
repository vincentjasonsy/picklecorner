<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\MemberVenueBookingSubmittedNotification;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends the member email matching a PayMongo-paid venue booking ({@see Booking::STATUS_CONFIRMED}).
 */
class SendConfirmedVenueBookingMailPreview extends Command
{
    protected $signature = 'mail:test-confirmed-booking
                            {--to=vincent.m.sy@gmail.com : Recipient (member inbox preview)}';

    protected $description = 'Send a preview of the member “booking confirmed” mail (same as after PayMongo checkout completes)';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Provide a valid email via --to=');

            return self::FAILURE;
        }

        if (! UserType::query()->exists()) {
            Artisan::call('db:seed', ['--class' => UserTypeSeeder::class]);
        }

        try {
            $user = User::query()->where('email', $to)->first()
                ?? User::factory()->player()->create([
                    'email' => $to,
                    'name' => 'Vincent (confirmed booking preview)',
                ]);
        } catch (Throwable $e) {
            $this->error('Could not resolve user: '.$e->getMessage());

            return self::FAILURE;
        }

        $payload = [
            'venueName' => 'Preview Courts PH',
            'status' => Booking::STATUS_CONFIRMED,
            'statusLabel' => Booking::statusDisplayLabel(Booking::STATUS_CONFIRMED),
            'lines' => [
                ['court' => 'Court 1', 'when' => now()->addDay()->format('M j, Y ').'9:00 AM – 10:00 AM'],
            ],
            'currency' => 'PHP',
            // Same shape as checkout_snapshot.request after PayMongo ({@see BookingCheckoutSnapshot::memberPublicCheckout})
            'requestTotals' => [
                'court_subtotal_cents' => 500_00,
                'coach_fee_total_cents' => 0,
                'booking_fee_total_cents' => 50_00,
                'checkout_total_before_gift_cents' => 550_00,
                'gift_applied_total_cents' => null,
                'balance_after_gift_cents' => 550_00,
            ],
            'bookingRequestId' => (string) Str::uuid(),
            'firstBookingId' => (string) Str::uuid(),
            'paymentLabel' => Booking::paymentMethodLabel(Booking::PAYMENT_PAYMONGO),
            'paymentReference' => 'pay_preview_'.Str::lower(Str::random(12)),
            'feeRuleLabel' => 'Standard hourly rate',
        ];

        try {
            Notification::sendNow($user, new MemberVenueBookingSubmittedNotification($payload));
        } catch (Throwable $e) {
            $this->error('Failed to send: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sent member “booking confirmed” preview (PayMongo-style) to {$to}");

        return self::SUCCESS;
    }
}
