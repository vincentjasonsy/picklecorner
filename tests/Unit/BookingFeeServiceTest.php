<?php

namespace Tests\Unit;

use App\Models\BookingFeeSetting;
use App\Models\Court;
use App\Models\CourtClient;
use App\Services\BookingFeeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFeeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_uses_defaults_when_no_database_row(): void
    {
        // Courts subtotal ₱100 → 15 + 2 = 17
        $this->assertSame(17.0, BookingFeeService::calculate(100.0));
    }

    public function test_calculate_applies_max_cap_from_active_setting(): void
    {
        BookingFeeSetting::query()->create([
            'base_fee' => '15.00',
            'percentage_fee' => '0.0200',
            'max_fee' => '20.00',
            'is_active' => true,
        ]);

        // Uncapped would be 15 + 1000*0.02 = 35; cap 20
        $this->assertSame(20.0, BookingFeeService::calculate(1000.0));
    }

    public function test_max_fee_of_zero_is_treated_as_no_cap(): void
    {
        BookingFeeSetting::query()->create([
            'base_fee' => '15.00',
            'percentage_fee' => '0.0200',
            'max_fee' => '0.00',
            'is_active' => true,
        ]);

        // Would wrongly become 0 if min(fee, 0); expect uncapped 15 + 100*0.02 = 17
        $this->assertSame(17.0, BookingFeeService::calculate(100.0));
    }

    public function test_per_court_hour_fixed_ignores_zero_max_fee_cap(): void
    {
        BookingFeeSetting::query()->create([
            'base_fee' => '0',
            'percentage_fee' => '0',
            'max_fee' => '0.00',
            'is_active' => true,
            'fee_basis' => BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR,
            'per_court_hour_mode' => BookingFeeSetting::PER_COURT_HOUR_FIXED,
            'per_court_hour_fixed' => '15.00',
            'per_court_hour_percent' => null,
        ]);

        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Test court',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $tz = config('app.timezone', 'UTC');
        $starts = Carbon::parse('2026-06-01 09:00:00', $tz);
        $ends = Carbon::parse('2026-06-01 10:00:00', $tz);

        $court->loadMissing(['courtClient', 'timeSlotSettings']);

        $specs = [[
            'court' => $court,
            'starts' => $starts,
            'ends' => $ends,
            'gross_cents' => 5000,
            'court_gross_cents' => 5000,
            'hours' => [9],
            'coach_fee_cents' => 0,
        ]];

        $this->assertSame(1500, BookingFeeService::calculateCentsForSpecs($specs));
    }

    public function test_calculate_ignores_inactive_rows_and_uses_fallback(): void
    {
        BookingFeeSetting::query()->create([
            'base_fee' => '99.00',
            'percentage_fee' => '0.5000',
            'max_fee' => null,
            'is_active' => false,
        ]);

        $this->assertSame(17.0, BookingFeeService::calculate(100.0));
    }

    public function test_calculate_cents_from_court_subtotal(): void
    {
        // ₱100.00 courts → ₱17.00 fee → 1700 cents
        $this->assertSame(1700, BookingFeeService::calculateCentsFromCourtSubtotalCents(10000));
    }

    public function test_calculate_cents_for_specs_per_court_hour_fixed(): void
    {
        BookingFeeSetting::query()->create([
            'base_fee' => '0',
            'percentage_fee' => '0',
            'max_fee' => null,
            'is_active' => true,
            'fee_basis' => BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR,
            'per_court_hour_mode' => BookingFeeSetting::PER_COURT_HOUR_FIXED,
            'per_court_hour_fixed' => '10.00',
            'per_court_hour_percent' => null,
        ]);

        $client = CourtClient::factory()->create();
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Test court',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
        ]);

        $tz = config('app.timezone', 'UTC');
        $starts = Carbon::parse('2026-06-01 09:00:00', $tz);
        $ends = Carbon::parse('2026-06-01 11:00:00', $tz);

        $court->loadMissing(['courtClient', 'timeSlotSettings']);

        $specs = [[
            'court' => $court,
            'starts' => $starts,
            'ends' => $ends,
            'gross_cents' => 5000,
            'court_gross_cents' => 5000,
            'hours' => [9, 10],
            'coach_fee_cents' => 0,
        ]];

        // 2 court hours × ₱10 = ₱20 → 2000 cents
        $this->assertSame(2000, BookingFeeService::calculateCentsForSpecs($specs));
    }

    public function test_calculate_cents_for_specs_per_court_hour_percent(): void
    {
        BookingFeeSetting::query()->create([
            'base_fee' => '0',
            'percentage_fee' => '0',
            'max_fee' => null,
            'is_active' => true,
            'fee_basis' => BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR,
            'per_court_hour_mode' => BookingFeeSetting::PER_COURT_HOUR_PERCENT,
            'per_court_hour_fixed' => null,
            'per_court_hour_percent' => '0.0200',
        ]);

        $client = CourtClient::factory()->create(['hourly_rate_cents' => 10_000]);
        $court = Court::query()->create([
            'court_client_id' => $client->id,
            'name' => 'Rate court',
            'sort_order' => 1,
            'environment' => Court::ENV_OUTDOOR,
            'is_available' => true,
            'hourly_rate_cents' => 10_000,
        ]);

        $tz = config('app.timezone', 'UTC');
        $starts = Carbon::parse('2026-06-01 14:00:00', $tz);
        $ends = Carbon::parse('2026-06-01 15:00:00', $tz);

        $court->loadMissing(['courtClient', 'timeSlotSettings']);

        $specs = [[
            'court' => $court,
            'starts' => $starts,
            'ends' => $ends,
            'gross_cents' => 10_000,
            'court_gross_cents' => 10_000,
            'hours' => [14],
            'coach_fee_cents' => 0,
        ]];

        // 2% of ₱100 (10000¢) = ₱2 → 200 cents
        $this->assertSame(200, BookingFeeService::calculateCentsForSpecs($specs));
    }

    public function test_calculate_cents_from_court_subtotal_throws_when_active_rate_is_per_hour(): void
    {
        BookingFeeSetting::query()->create([
            'base_fee' => '15.00',
            'percentage_fee' => '0.0200',
            'max_fee' => null,
            'is_active' => true,
            'fee_basis' => BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR,
            'per_court_hour_mode' => BookingFeeSetting::PER_COURT_HOUR_FIXED,
            'per_court_hour_fixed' => '1.00',
            'per_court_hour_percent' => null,
        ]);

        $this->expectException(\LogicException::class);
        BookingFeeService::calculateCentsFromCourtSubtotalCents(10_000);
    }
}
