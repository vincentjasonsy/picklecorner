<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoQuickLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_login_is_disabled_returns_404(): void
    {
        config(['demo.quick_login_enabled' => false]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('demo.quick-login'), ['role' => 'player'])
            ->assertNotFound();
    }

    public function test_quick_login_logs_in_player_when_account_exists(): void
    {
        $this->seed(UserTypeSeeder::class);
        config(['demo.quick_login_enabled' => true]);

        $user = User::factory()->player()->create([
            'email' => 'player@picklecorner.ph',
            'password' => 'password',
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('demo.quick-login'), ['role' => 'player'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_quick_login_redirects_back_with_warning_when_user_missing(): void
    {
        $this->seed(UserTypeSeeder::class);
        config(['demo.quick_login_enabled' => true]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->from(route('login'))
            ->post(route('demo.quick-login'), ['role' => 'player'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(session()->has('warning'));
    }

    public function test_login_page_shows_quick_login_when_enabled(): void
    {
        config(['demo.quick_login_enabled' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Quick login', false)
            ->assertSee('Court admin', false)
            ->assertDontSee('Super admin', false);
    }

    public function test_quick_login_rejects_super_admin_role(): void
    {
        $this->seed(UserTypeSeeder::class);
        config(['demo.quick_login_enabled' => true]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('demo.quick-login'), ['role' => 'super_admin'])
            ->assertSessionHasErrors('role');
    }
}
