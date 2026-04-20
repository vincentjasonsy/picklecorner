<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $inquiryLabel,
        public string $name,
        public string $email,
        public string $messageBody,
        public ?string $phone = null,
        public ?string $clubName = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = '['.config('app.name').'] '.$this->inquiryLabel.' — '.$this->name;

        return new Envelope(
            from: new Address((string) config('mail.from.address'), (string) config('mail.from.name')),
            subject: $subject,
            replyTo: [
                new Address($this->email, $this->name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-inquiry-text',
        );
    }
}
