<?php

namespace Tests\Feature;

use App\Livewire\Admin\InvoiceCreate;
use App\Livewire\Admin\InvoiceShow;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\CourtClientInvoice;
use App\Models\User;
use App\Services\BookingCheckoutSnapshot;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_cannot_access_invoices(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)->get(route('admin.invoices.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_invoice_and_mark_paid(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $guest = User::factory()->player()->create();

        $starts = Carbon::now(config('app.timezone'))->startOfDay()->addHours(10);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5_000,
            'currency' => 'PHP',
            'checkout_snapshot' => BookingCheckoutSnapshot::manualCheckout(
                currency: 'PHP',
                requestCourtSubtotalCents: 5_000,
                requestCheckoutTotalBeforeGiftCents: 5_000,
                requestGiftAppliedTotalCents: null,
                lineCourtSubtotalCents: 5_000,
                lineCourtCoachGrossCents: 5_000,
                lineGiftAppliedCents: 0,
                lineCourtCoachAfterGiftCents: 5_000,
            ),
            'payment_method' => Booking::PAYMENT_CASH,
        ]);

        $from = $starts->copy()->subDay()->toDateString();
        $to = $starts->copy()->addDay()->toDateString();

        Livewire::actingAs($super)
            ->test(InvoiceCreate::class)
            ->set('courtClientId', $client->id)
            ->set('periodFrom', $from)
            ->set('periodTo', $to)
            ->call('createInvoice')
            ->assertRedirect();

        $invoice = CourtClientInvoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame(CourtClientInvoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(5_000, $invoice->total_cents);
        $this->assertSame(1, $invoice->bookings()->count());

        Livewire::actingAs($super)
            ->test(InvoiceShow::class, ['invoice' => $invoice])
            ->call('markPaid')
            ->assertHasNoErrors();

        $invoice->refresh();
        $this->assertSame(CourtClientInvoice::STATUS_PAID, $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $this->assertSame(
            0,
            Booking::query()
                ->where('court_client_id', $client->id)
                ->countingTowardRevenue()
                ->eligibleForCourtClientInvoice()
                ->whereDoesntHave('courtClientInvoices')
                ->count(),
        );
    }

    public function test_invoice_includes_only_manual_desk_bookings_not_member_checkout(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $guest = User::factory()->player()->create();

        $starts = Carbon::now(config('app.timezone'))->startOfDay()->addHours(11);

        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5_000,
            'currency' => 'PHP',
            'checkout_snapshot' => BookingCheckoutSnapshot::manualCheckout(
                currency: 'PHP',
                requestCourtSubtotalCents: 5_000,
                requestCheckoutTotalBeforeGiftCents: 5_000,
                requestGiftAppliedTotalCents: null,
                lineCourtSubtotalCents: 5_000,
                lineCourtCoachGrossCents: 5_000,
                lineGiftAppliedCents: 0,
                lineCourtCoachAfterGiftCents: 5_000,
            ),
            'payment_method' => Booking::PAYMENT_GCASH,
        ]);

        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'starts_at' => $starts->copy()->addHour(),
            'ends_at' => $starts->copy()->addHours(2),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5_000,
            'currency' => 'PHP',
            'checkout_snapshot' => BookingCheckoutSnapshot::memberPublicCheckout(
                currency: 'PHP',
                feeRuleLabel: 'Regular',
                requestCourtSubtotalCents: 5_000,
                requestCoachFeeTotalCents: 0,
                requestBookingFeeTotalCents: 0,
                requestCheckoutTotalBeforeGiftCents: 5_000,
                requestGiftAppliedTotalCents: null,
                lineCourtSubtotalCents: 5_000,
                lineCoachFeeCents: 0,
                lineCourtCoachGrossCents: 5_000,
                lineGiftAppliedCents: 0,
                lineCourtCoachAfterGiftCents: 5_000,
                linePlatformBookingFeeCents: 0,
            ),
            'payment_method' => Booking::PAYMENT_PAYMONGO,
        ]);

        $from = $starts->copy()->subDay()->toDateString();
        $to = $starts->copy()->addDay()->toDateString();

        Livewire::actingAs($super)
            ->test(InvoiceCreate::class)
            ->set('courtClientId', $client->id)
            ->set('periodFrom', $from)
            ->set('periodTo', $to)
            ->call('createInvoice')
            ->assertRedirect();

        $invoice = CourtClientInvoice::query()->first();
        $this->assertSame(5_000, $invoice->total_cents);
        $this->assertSame(1, $invoice->bookings()->count());

        $remainingMember = Booking::query()
            ->where('court_client_id', $client->id)
            ->countingTowardRevenue()
            ->whereDoesntHave('courtClientInvoices')
            ->count();
        $this->assertSame(1, $remainingMember);

        $remainingEligibleManual = Booking::query()
            ->where('court_client_id', $client->id)
            ->countingTowardRevenue()
            ->eligibleForCourtClientInvoice()
            ->whereDoesntHave('courtClientInvoices')
            ->count();
        $this->assertSame(0, $remainingEligibleManual);
    }

    public function test_super_admin_can_open_invoice_index(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.invoices.index'))->assertOk();
    }

    public function test_super_admin_can_download_invoice_pdf(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $client = CourtClient::factory()->create(['currency' => 'PHP']);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Court A',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);
        $guest = User::factory()->player()->create();

        $starts = Carbon::now(config('app.timezone'))->startOfDay()->addHours(10);
        Booking::query()->create([
            'court_client_id' => $client->id,
            'court_id' => $court->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5_000,
            'currency' => 'PHP',
            'checkout_snapshot' => BookingCheckoutSnapshot::manualCheckout(
                currency: 'PHP',
                requestCourtSubtotalCents: 5_000,
                requestCheckoutTotalBeforeGiftCents: 5_000,
                requestGiftAppliedTotalCents: null,
                lineCourtSubtotalCents: 5_000,
                lineCourtCoachGrossCents: 5_000,
                lineGiftAppliedCents: 0,
                lineCourtCoachAfterGiftCents: 5_000,
            ),
            'payment_method' => Booking::PAYMENT_CASH,
        ]);

        $from = $starts->copy()->subDay()->toDateString();
        $to = $starts->copy()->addDay()->toDateString();

        Livewire::actingAs($super)
            ->test(InvoiceCreate::class)
            ->set('courtClientId', $client->id)
            ->set('periodFrom', $from)
            ->set('periodTo', $to)
            ->call('createInvoice')
            ->assertRedirect();

        $invoice = CourtClientInvoice::query()->first();
        $this->assertNotNull($invoice);

        $response = $this->actingAs($super)->get(route('admin.invoices.pdf', $invoice));
        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString(
            $invoice->reference,
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_player_cannot_download_invoice_pdf(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();
        $client = CourtClient::factory()->create();
        $invoice = CourtClientInvoice::query()->create([
            'court_client_id' => $client->id,
            'period_from' => now()->toDateString(),
            'period_to' => now()->toDateString(),
            'reference' => 'INV-TEST-PDF-ACCESS',
            'status' => CourtClientInvoice::STATUS_UNPAID,
            'total_cents' => 0,
            'currency' => 'PHP',
        ]);

        $this->actingAs($player)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertForbidden();
    }
}
