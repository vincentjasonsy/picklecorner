<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_registration_page_is_available_when_enabled(): void
    {
        config(['demo.registration_enabled' => true]);

        $this->get(route('register.demo'))
            ->assertOk()
            ->assertSee('Demo account', false)
            ->assertSee('Start demo', false);
    }

    public function test_demo_registration_redirects_when_disabled(): void
    {
        config(['demo.registration_enabled' => false]);

        $this->get(route('register.demo'))
            ->assertRedirect(route('register'));
    }

    public function test_demo_user_factory_sets_expiry_and_flags(): void
    {
        $user = User::factory()->player()->demoAccount(24)->create();

        $this->assertTrue($user->isDemoAccount());
        $this->assertFalse($user->demoHasExpired());
    }

    public function test_expired_demo_user_is_logged_out_by_middleware(): void
    {
        $user = User::factory()->player()->create([
            'demo_expires_at' => now()->subHour(),
        ]);

        $this->assertTrue($user->demoHasExpired());

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_purge_command_removes_expired_demo_accounts(): void
    {
        $expired = User::factory()->player()->create([
            'demo_expires_at' => now()->subMinute(),
        ]);
        $active = User::factory()->player()->create([
            'demo_expires_at' => now()->addDay(),
        ]);
        $normal = User::factory()->player()->create([
            'demo_expires_at' => null,
        ]);

        $this->artisan('demo:purge-expired-accounts')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $expired->id]);
        $this->assertDatabaseHas('users', ['id' => $active->id]);
        $this->assertDatabaseHas('users', ['id' => $normal->id]);
    }
}
