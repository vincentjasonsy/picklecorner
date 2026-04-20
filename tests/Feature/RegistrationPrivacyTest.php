<?php

namespace Tests\Feature;

use App\Livewire\Auth\RegisterPage;
use App\Models\User;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function seedUserTypes(): void
    {
        $this->seed(UserTypeSeeder::class);
    }

    public function test_privacy_policy_page_renders(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Data Privacy Act', false)
            ->assertSee('Privacy policy', false);
    }

    public function test_registration_requires_privacy_acceptance(): void
    {
        $this->seedUserTypes();

        $email = 'new-player-'.uniqid('', true).'@example.com';

        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('email', $email)
            ->set('password', 'Password-for-test9')
            ->set('password_confirmation', 'Password-for-test9')
            ->set('accept_privacy', false)
            ->call('register')
            ->assertHasErrors(['accept_privacy']);
    }

    public function test_registration_stores_consent_timestamps(): void
    {
        $this->seedUserTypes();

        $email = 'consented-'.uniqid('', true).'@example.com';

        Livewire::test(RegisterPage::class)
            ->set('name', 'Consent Test')
            ->set('email', $email)
            ->set('password', 'Password-for-test9')
            ->set('password_confirmation', 'Password-for-test9')
            ->set('accept_privacy', true)
            ->set('subscribe_marketing_emails', true)
            ->call('register')
            ->assertHasNoErrors();

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->privacy_consent_at);
        $this->assertNotNull($user->marketing_emails_consent_at);
    }

    public function test_registration_rejects_password_shorter_than_eight_characters(): void
    {
        $this->seedUserTypes();

        $email = 'short-pw-'.uniqid('', true).'@example.com';

        Livewire::test(RegisterPage::class)
            ->set('name', 'Pw Short')
            ->set('email', $email)
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->set('accept_privacy', true)
            ->call('register')
            ->assertHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_registration_rejects_password_without_mixed_case(): void
    {
        $this->seedUserTypes();

        $email = 'no-mixed-'.uniqid('', true).'@example.com';

        Livewire::test(RegisterPage::class)
            ->set('name', 'Case Test')
            ->set('email', $email)
            ->set('password', 'password-for-99')
            ->set('password_confirmation', 'password-for-99')
            ->set('accept_privacy', true)
            ->call('register')
            ->assertHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_registration_without_marketing_opt_in(): void
    {
        $this->seedUserTypes();

        $email = 'no-marketing-'.uniqid('', true).'@example.com';

        Livewire::test(RegisterPage::class)
            ->set('name', 'No Marketing')
            ->set('email', $email)
            ->set('password', 'Password-for-test9')
            ->set('password_confirmation', 'Password-for-test9')
            ->set('accept_privacy', true)
            ->set('subscribe_marketing_emails', false)
            ->call('register')
            ->assertHasNoErrors();

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user->privacy_consent_at);
        $this->assertNull($user->marketing_emails_consent_at);
    }
}
