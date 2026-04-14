<?php

namespace Tests\Unit;

use App\Models\BookingFeeSetting;
use App\Services\BookingFeeService;
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
}
