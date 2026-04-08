<?php

namespace Tests\Feature;

use App\Livewire\ContactPage;
use App\Mail\ContactInquiryMail;
use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_contact_page(): void
    {
        $this->seed(UserTypeSeeder::class);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Contact &amp; book a demo', escape: false);
    }

    public function test_guest_can_submit_contact_form_and_mail_is_sent(): void
    {
        $this->seed(UserTypeSeeder::class);

        Mail::fake();

        config(['contact.recipient' => 'inbox@example.test']);

        Livewire::test(ContactPage::class)
            ->set('inquiry_type', 'demo')
            ->set('name', 'Jamie Club')
            ->set('email', 'jamie@example.test')
            ->set('phone', '+63 900 000 0000')
            ->set('club_name', 'Metro Pickle')
            ->set('message', 'We would like a 30-minute walkthrough next week.')
            ->call('submit')
            ->assertHasNoErrors();

        Mail::assertSent(ContactInquiryMail::class, function (ContactInquiryMail $mail): bool {
            return $mail->inquiryLabel === 'Book a demo'
                && $mail->name === 'Jamie Club'
                && $mail->email === 'jamie@example.test'
                && str_contains($mail->messageBody, 'walkthrough');
        });
    }

    public function test_contact_form_validates_message_length(): void
    {
        $this->seed(UserTypeSeeder::class);

        Mail::fake();

        config(['contact.recipient' => 'inbox@example.test']);

        Livewire::test(ContactPage::class)
            ->set('name', 'A')
            ->set('email', 'a@b.co')
            ->set('message', 'short')
            ->call('submit')
            ->assertHasErrors(['message']);

        Mail::assertNothingSent();
    }
}
