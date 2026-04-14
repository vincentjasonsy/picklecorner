<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingTransactionReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_reference_uses_booking_request_id_when_set(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $player = User::factory()->player()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $req = (string) Str::uuid();
        $starts = Carbon::parse('2026-07-01 10:00:00', config('app.timezone'));

        $b = Booking::query()->create([
            'court_client_id' => $client->id,
            'booking_request_id' => $req,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1000,
            'currency' => 'PHP',
        ]);

        $this->assertSame($req, $b->transactionReference());
        $this->assertStringEndsWith('…', $b->transactionReferenceShort());
    }

    public function test_transaction_reference_falls_back_to_booking_id_when_no_batch(): void
    {
        $this->seed(UserTypeSeeder::class);

        $client = CourtClient::factory()->create();
        $player = User::factory()->player()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'A',
            'sort_order' => 0,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $starts = Carbon::parse('2026-07-02 10:00:00', config('app.timezone'));

        $b = Booking::query()->create([
            'court_client_id' => $client->id,
            'booking_request_id' => null,
            'court_id' => $court->id,
            'user_id' => $player->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => Booking::STATUS_CONFIRMED,
            'amount_cents' => 1000,
            'currency' => 'PHP',
        ]);

        $this->assertSame((string) $b->id, $b->transactionReference());
    }
}
