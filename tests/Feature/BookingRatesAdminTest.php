<?php

namespace Tests\Feature;

use App\Livewire\Admin\BookingRates;
use App\Models\BookingFeeSetting;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingRatesAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_rate_from_table_leaves_only_one_active(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $older = BookingFeeSetting::query()->create([
            'base_fee' => '10.00',
            'percentage_fee' => '0.0200',
            'max_fee' => null,
            'is_active' => true,
        ]);

        $newer = BookingFeeSetting::query()->create([
            'base_fee' => '15.00',
            'percentage_fee' => '0.0200',
            'max_fee' => null,
            'is_active' => true,
        ]);

        $this->assertFalse($older->fresh()->is_active);
        $this->assertTrue($newer->fresh()->is_active);

        Livewire::actingAs($super)
            ->test(BookingRates::class)
            ->call('activateRate', $older->id);

        $this->assertTrue($older->fresh()->is_active);
        $this->assertFalse($newer->fresh()->is_active);
    }

    public function test_deactivate_rate_clears_active_until_another_is_activated(): void
    {
        $this->seed(UserTypeSeeder::class);

        $super = User::factory()->superAdmin()->create();

        $row = BookingFeeSetting::query()->create([
            'base_fee' => '15.00',
            'percentage_fee' => '0.0200',
            'max_fee' => null,
            'is_active' => true,
        ]);

        Livewire::actingAs($super)
            ->test(BookingRates::class)
            ->call('deactivateRate', $row->id);

        $this->assertFalse($row->fresh()->is_active);
    }
}
