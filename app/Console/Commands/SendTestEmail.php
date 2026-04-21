<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestEmail extends Command
{
    protected $signature = 'mail:test
                            {--to=vincent.m.sy@gmail.com : Recipient email}
                            {--subject=Pickle Corner test email : Subject line}';

    protected $description = 'Send a quick SMTP test email';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));
        $subject = trim((string) $this->option('subject'));

        if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Please provide a valid recipient via --to=');

            return self::FAILURE;
        }

        if ($subject === '') {
            $subject = 'Pickle Corner test email';
        }

        $sentAt = now()->timezone(config('app.timezone'))->format('M j, Y g:i A');
        $mailer = (string) config('mail.default', 'smtp');
        $host = (string) config('mail.mailers.smtp.host', 'n/a');
        $port = (string) config('mail.mailers.smtp.port', 'n/a');

        try {
            Mail::raw(
                "Test email from ".config('app.name')."\n\nSent at: {$sentAt}\nMailer: {$mailer}\nSMTP: {$host}:{$port}",
                function ($message) use ($to, $subject): void {
                    $message->to($to)->subject($subject);
                },
            );
        } catch (Throwable $e) {
            $this->error('Failed to send test email: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email sent to {$to}");

        return self::SUCCESS;
    }
}
