<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\BookingReviewInviteNotification;
use App\Notifications\CourtAdminVenueBookingSubmittedNotification;
use App\Notifications\InternalTeamPlayReminderNotification;
use App\Notifications\MemberNewCourtOpeningNotification;
use App\Notifications\MemberVenueBookingSubmittedNotification;
use App\Notifications\NewUserRegistrationAlertNotification;
use App\Notifications\NewUserWelcomeNotification;
use App\Services\BookingCheckoutSnapshot;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class SendAllMailPreviews extends Command
{
    protected $signature = 'mail:test-all
                            {--to=vincent.m.sy@gmail.com : Recipient email for every preview}';

    protected $description = 'Send one preview of each outbound notification email (SMTP must be configured)';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));
        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Provide a valid email via --to=');

            return self::FAILURE;
        }

        $this->ensureUserTypes();

        try {
            $user = User::query()->where('email', $to)->first();
            if ($user === null) {
                $user = User::factory()->player()->create([
                    'email' => $to,
                    'name' => 'Vincent (mail preview)',
                ]);
            }
        } catch (Throwable $e) {
            $this->error('Could not resolve a member user for previews: '.$e->getMessage());

            return self::FAILURE;
        }

        $payload = $this->sampleBookingPayload();

        $steps = [
            'Welcome (new member)' => fn () => Notification::sendNow($user, new NewUserWelcomeNotification($user)),
            'Admin alert (new registration)' => fn () => Notification::sendNow(
                Notification::route('mail', $to),
                new NewUserRegistrationAlertNotification($user),
            ),
            'Member — venue booking submitted' => fn () => Notification::sendNow(
                $user,
                new MemberVenueBookingSubmittedNotification($payload),
            ),
            'Court admin — venue booking submitted' => fn () => Notification::sendNow(
                Notification::route('mail', $to),
                new CourtAdminVenueBookingSubmittedNotification(array_merge($payload, [
                    'bookerName' => $user->name,
                    'bookerEmail' => $user->email,
                ])),
            ),
            'Internal team play reminder' => fn () => Notification::sendNow(
                $user,
                new InternalTeamPlayReminderNotification(14, $this->sampleReminderCourts()),
            ),
        ];

        foreach ($steps as $label => $send) {
            try {
                $send();
                $this->info("Sent: {$label}");
            } catch (Throwable $e) {
                $this->warn("Failed: {$label} — ".$e->getMessage());
            }
        }

        try {
            $client = CourtClient::factory()->create([
                'name' => 'Preview Pickle Club',
                'slug' => 'preview-mail-club-'.Str::lower(Str::random(6)),
                'city' => 'Makati',
            ]);
            $court = Court::query()->create([
                'court_client_id' => $client->id,
                'name' => 'Court A (preview)',
                'sort_order' => 0,
                'environment' => Court::ENV_OUTDOOR,
                'is_available' => true,
                'opens_at' => now()->addMonth(),
            ]);

            Notification::sendNow($user, new MemberNewCourtOpeningNotification($court->id, false));
            $this->info('Sent: Member — new court opening');

            Notification::sendNow($user, new MemberNewCourtOpeningNotification($court->id, true));
            $this->info('Sent: Member — upcoming court opening');

            $booking = Booking::query()->create([
                'court_client_id' => $client->id,
                'court_id' => $court->id,
                'user_id' => $user->id,
                'booking_request_id' => (string) Str::uuid(),
                'starts_at' => now()->subDay()->setTime(14, 0),
                'ends_at' => now()->subDay()->setTime(15, 0),
                'status' => Booking::STATUS_COMPLETED,
                'currency' => 'PHP',
                'checkout_snapshot' => BookingCheckoutSnapshot::memberPublicCheckout(
                    'PHP',
                    'Peak hours',
                    500_00,
                    0,
                    50_00,
                    550_00,
                    null,
                    500_00,
                    0,
                    500_00,
                    0,
                    500_00,
                    0,
                    50_00,
                ),
            ]);

            Notification::sendNow($user, new BookingReviewInviteNotification($booking));
            $this->info('Sent: Booking review invite');
        } catch (Throwable $e) {
            $this->warn('Skipped court / booking previews — '.$e->getMessage());
        }

        $this->newLine();
        $this->info("Done. Check inbox for {$to}");

        return self::SUCCESS;
    }

    protected function ensureUserTypes(): void
    {
        if (UserType::query()->exists()) {
            return;
        }

        Artisan::call('db:seed', ['--class' => UserTypeSeeder::class]);
        $this->comment('Seeded user types for factories.');
    }

    /**
     * @return array{
     *     venueName: string,
     *     status: string,
     *     statusLabel: string,
     *     lines: list<array{court: string, when: string}>,
     *     currency: string,
     *     requestTotals: array<string, mixed>,
     *     bookingRequestId: string,
     *     firstBookingId: string,
     *     paymentLabel: ?string,
     *     paymentReference: ?string,
     *     feeRuleLabel: ?string,
     * }
     */
    protected function sampleBookingPayload(): array
    {
        return [
            'venueName' => 'Preview Courts PH',
            'status' => Booking::STATUS_PENDING_APPROVAL,
            'statusLabel' => Booking::statusDisplayLabel(Booking::STATUS_PENDING_APPROVAL),
            'lines' => [
                ['court' => 'Court 1', 'when' => now()->addDay()->format('M j, Y ').'9:00 AM – 10:00 AM'],
            ],
            'currency' => 'PHP',
            'requestTotals' => [
                'subtotal_cents' => 500_00,
                'platform_fee_cents' => 50_00,
                'total_cents' => 550_00,
            ],
            'bookingRequestId' => (string) Str::uuid(),
            'firstBookingId' => (string) Str::uuid(),
            'paymentLabel' => 'PayMongo (GCash / QRPh)',
            'paymentReference' => null,
            'feeRuleLabel' => 'Standard hourly rate',
        ];
    }

    /**
     * @return list<array{
     *     venue_name: string,
     *     court_name: string,
     *     city: ?string,
     *     environment_label: string,
     *     book_url: string,
     *     venue_book_url: string,
     *     picked_for_you: bool,
     *     badge: string,
     * }>
     */
    protected function sampleReminderCourts(): array
    {
        $book = route('book-now', [], true);
        $venue = route('book-now', [], true);

        return [[
            'venue_name' => 'Preview Courts PH',
            'court_name' => 'Court 1',
            'city' => 'Makati',
            'environment_label' => 'Outdoor',
            'book_url' => $book,
            'venue_book_url' => $venue,
            'picked_for_you' => true,
            'badge' => 'Picked for you',
        ]];
    }
}
