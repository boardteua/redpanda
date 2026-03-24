<?php

namespace App\Mail;

use App\Models\ChatSetting;
use App\Models\User;
use App\Services\Mail\TransactionalMailTemplateResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeRegisteredUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $resolvedSubject;

    /** @var array<string, mixed> */
    public array $resolvedWith;

    public string $resolvedHtmlView;

    public string $resolvedTextView;

    public function __construct(public User $user)
    {
        $payload = app(TransactionalMailTemplateResolver::class)->welcomeMailViews($user);
        $this->resolvedSubject = $payload['subject'];
        $this->resolvedHtmlView = $payload['htmlView'];
        $this->resolvedTextView = $payload['textView'];
        $this->resolvedWith = $payload['with'];
    }

    public function envelope(): Envelope
    {
        $fromAddr = (string) config('mail.from.address');
        $defaultName = (string) config('mail.from.name');
        $custom = ChatSetting::current()->effectiveTransactionalMailFromName();
        $from = new Address($fromAddr, $custom ?? $defaultName);

        return new Envelope(
            from: [$from],
            subject: $this->resolvedSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->resolvedHtmlView,
            text: $this->resolvedTextView,
            with: $this->resolvedWith,
        );
    }
}
