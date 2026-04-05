<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_cannot_export_admin_bookings_csv(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('admin.reports.export.bookings'))
            ->assertForbidden();
    }

    public function test_player_cannot_export_venue_bookings_csv(): void
    {
        $this->seed(UserTypeSeeder::class);

        $player = User::factory()->player()->create();

        $this->actingAs($player)
            ->get(route('venue.reports.export.bookings'))
            ->assertForbidden();
    }

    public function test_super_admin_csv_includes_bookings_across_venues(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();
        $courtAdmin = User::factory()->courtAdmin()->create();

        $clientMine = CourtClient::factory()->forAdmin($courtAdmin)->create(['name' => 'Alpha Club']);
        $courtMine = Court::query()->create([
            'court_client_id' => $clientMine->id,
            'name' => 'Court Alpha',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $clientOther = CourtClient::factory()->create(['name' => 'Beta Club']);
        $courtOther = Court::query()->create([
            'court_client_id' => $clientOther->id,
            'name' => 'Court Beta',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $guest = User::factory()->player()->create(['name' => 'Guest One', 'email' => 'guest1@example.test']);

        $starts = Carbon::now(config('app.timezone'));
        Booking::query()->create([
            'court_client_id' => $clientMine->id,
            'court_id' => $courtMine->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5000,
            'currency' => 'PHP',
            'payment_method' => Booking::PAYMENT_GCASH,
        ]);

        Booking::query()->create([
            'court_client_id' => $clientOther->id,
            'court_id' => $courtOther->id,
            'user_id' => $guest->id,
            'starts_at' => $starts->copy()->addMinutes(5),
            'ends_at' => $starts->copy()->addHour()->addMinutes(5),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 3000,
            'currency' => 'PHP',
            'payment_method' => Booking::PAYMENT_CASH,
        ]);

        $from = $starts->copy()->subDay()->toDateString();
        $to = $starts->copy()->addDay()->toDateString();

        $response = $this->actingAs($super)->get(route('admin.reports.export.bookings', [
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('booking_id', $csv);
        $this->assertStringContainsString('Alpha Club', $csv);
        $this->assertStringContainsString('Beta Club', $csv);
        $this->assertStringContainsString('guest1@example.test', $csv);
    }

    public function test_court_admin_csv_is_limited_to_their_venue(): void
    {
        $this->seed(UserTypeSeeder::class);

        $courtAdmin = User::factory()->courtAdmin()->create();

        $clientMine = CourtClient::factory()->forAdmin($courtAdmin)->create(['name' => 'Alpha Club']);
        $courtMine = Court::query()->create([
            'court_client_id' => $clientMine->id,
            'name' => 'Court Alpha',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $clientOther = CourtClient::factory()->create(['name' => 'Beta Club']);
        $courtOther = Court::query()->create([
            'court_client_id' => $clientOther->id,
            'name' => 'Court Beta Only',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $guest = User::factory()->player()->create();

        $starts = Carbon::now(config('app.timezone'));
        Booking::query()->create([
            'court_client_id' => $clientMine->id,
            'court_id' => $courtMine->id,
            'user_id' => $guest->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 5000,
            'currency' => 'PHP',
        ]);

        Booking::query()->create([
            'court_client_id' => $clientOther->id,
            'court_id' => $courtOther->id,
            'user_id' => $guest->id,
            'starts_at' => $starts->copy()->addMinutes(5),
            'ends_at' => $starts->copy()->addHour()->addMinutes(5),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 3000,
            'currency' => 'PHP',
        ]);

        $from = $starts->copy()->subDay()->toDateString();
        $to = $starts->copy()->addDay()->toDateString();

        $response = $this->actingAs($courtAdmin)->get(route('venue.reports.export.bookings', [
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Court Alpha', $csv);
        $this->assertStringNotContainsString('Court Beta Only', $csv);
        $this->assertStringNotContainsString('Beta Club', $csv);
    }

    public function test_export_rejects_overlong_date_range(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('admin.reports.export.bookings', [
                'from' => '2020-01-01',
                'to' => '2025-01-01',
            ]))
            ->assertStatus(422);
    }
}
