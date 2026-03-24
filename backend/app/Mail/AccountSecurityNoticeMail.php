<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountSecurityNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $headline,
        public string $bodyLine,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->headline.' — '.(string) config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.account-security-notice',
            text: 'mail.account-security-notice-text',
            with: [
                'userName' => $this->user->user_name,
                'appName' => config('app.name'),
                'headline' => $this->headline,
                'bodyLine' => $this->bodyLine,
            ],
        );
    }
}
