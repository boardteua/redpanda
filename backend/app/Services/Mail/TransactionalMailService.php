<?php

namespace App\Services\Mail;

use App\Mail\AccountSecurityNoticeMail;
use App\Mail\WelcomeRegisteredUserMail;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionalMailService
{
    public function __construct(
        private readonly TransactionalMailTemplateResolver $mailTemplates,
    ) {}

    /**
     * Лист скидання пароля: той самий SPA-URL, що раніше в AppServiceProvider.
     */
    public function buildPasswordResetMailMessage(object $notifiable, #[\SensitiveParameter] string $token): MailMessage
    {
        return $this->mailTemplates->buildPasswordResetMailMessage($notifiable, $token);
    }

    /**
     * Після реєстрації з паролем (не гість). Помилки не ламають HTTP 201 — лише лог без PII.
     */
    public function sendWelcomeRegisteredUser(User $user): void
    {
        if ($user->guest || $user->email === null || $user->email === '') {
            return;
        }

        try {
            Mail::to($user->email)->send(new WelcomeRegisteredUserMail($user));
        } catch (\Throwable $e) {
            Log::warning('transactional_mail_failed', [
                'kind' => TransactionalMailKind::WelcomeRegistered->value,
                'exception' => $e::class,
            ]);
        }
    }

    /**
     * Заглушка розширення: майбутні події безпеки акаунта (новий пристрій, зміна пошти тощо).
     */
    public function sendAccountSecurityNotice(User $user, string $headline, string $bodyLine): void
    {
        if ($user->email === null || $user->email === '') {
            return;
        }

        try {
            Mail::to($user->email)->send(new AccountSecurityNoticeMail($user, $headline, $bodyLine));
        } catch (\Throwable $e) {
            Log::warning('transactional_mail_failed', [
                'kind' => TransactionalMailKind::AccountSecurityNotice->value,
                'exception' => $e::class,
            ]);
        }
    }
}
