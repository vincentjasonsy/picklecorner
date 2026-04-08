<?php

namespace App\Livewire;

use App\Mail\ContactInquiryMail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Contact & book a demo')]
class ContactPage extends Component
{
    /** @var 'demo'|'contact' */
    public string $inquiry_type = 'demo';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $club_name = '';

    public string $message = '';

    public function submit(): void
    {
        $validated = $this->validate([
            'inquiry_type' => ['required', 'string', Rule::in(['demo', 'contact'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'club_name' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'message.min' => 'Please add a bit more detail (at least 10 characters).',
        ]);

        $recipient = config('contact.recipient');
        if (! is_string($recipient) || $recipient === '') {
            $this->addError('email', 'Contact email is not configured. Set CONTACT_EMAIL or MAIL_FROM_ADDRESS in your environment.');

            return;
        }

        $executed = RateLimiter::attempt(
            'contact-form:'.request()->ip(),
            $maxAttempts = 5,
            function () use ($validated, $recipient): void {
                $isDemo = $validated['inquiry_type'] === 'demo';
                $label = $isDemo ? 'Book a demo' : 'General contact';

                Mail::to($recipient)->send(new ContactInquiryMail(
                    inquiryLabel: $label,
                    name: $validated['name'],
                    email: $validated['email'],
                    messageBody: $validated['message'],
                    phone: $validated['phone'] !== '' ? $validated['phone'] : null,
                    clubName: $validated['club_name'] !== '' ? $validated['club_name'] : null,
                ));
            },
            decaySeconds: 60,
        );

        if (! $executed) {
            $this->addError('message', 'Too many submissions. Please wait a minute and try again.');

            return;
        }

        $this->reset(['name', 'email', 'phone', 'club_name', 'message']);
        $this->inquiry_type = 'demo';

        session()->flash('status', 'Thanks — we received your message and will get back to you soon.');
    }

    public function render(): View
    {
        return view('livewire.contact-page');
    }
}
