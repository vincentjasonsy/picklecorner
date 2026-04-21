<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NewUserRegistrationAlertNotification;
use App\Notifications\NewUserWelcomeNotification;
use App\Services\RegistrationMailNotifier;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationMailNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_welcome_and_admin_registration_alert_emails(): void
    {
        $this->seed(UserTypeSeeder::class);
        config()->set('booking.registration_alert_email', 'vincent.m.sy@gmail.com');

        Notification::fake();

        $user = User::factory()->player()->create([
            'name' => 'Vincent Sy',
            'email' => 'new-user@example.com',
        ]);

        RegistrationMailNotifier::notify($user);

        Notification::assertSentTo($user, NewUserWelcomeNotification::class);
        Notification::assertSentOnDemand(NewUserRegistrationAlertNotification::class);
    }
}
