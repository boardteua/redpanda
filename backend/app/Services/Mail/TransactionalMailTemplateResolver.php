<?php

namespace App\Services\Mail;

use App\Models\ChatSetting;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class TransactionalMailTemplateResolver
{
    /**
     * @return array<string, array{subject: string, html_body: string, text_body: string}>
     */
    private function overrides(): array
    {
        return ChatSetting::current()->resolvedMailTemplateOverrides();
    }

    public function buildPasswordResetMailMessage(object $notifiable, #[\SensitiveParameter] string $token): MailMessage
    {
        $email = urlencode((string) $notifiable->getEmailForPasswordReset());
        $url = rtrim((string) config('app.url'), '/').'/reset-password?token='.urlencode($token).'&email='.$email;
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        $plainVals = [
            'app_name' => (string) config('app.name'),
            'reset_url' => $url,
            'expire_minutes' => (string) $expire,
        ];
        $htmlVals = [
            'app_name' => e($plainVals['app_name']),
            'reset_url' => e($plainVals['reset_url']),
            'expire_minutes' => e($plainVals['expire_minutes']),
        ];

        $ov = $this->overrides()['password_reset'] ?? null;
        $defaultSubject = 'Скидання пароля';
        if (is_array($ov) && trim((string) ($ov['html_body'] ?? '')) !== '') {
            $subject = trim((string) ($ov['subject'] ?? '')) !== '' ? trim((string) $ov['subject']) : $defaultSubject;
            $htmlRendered = MailTemplateSanitizer::html(
                MailPlaceholderRenderer::render((string) $ov['html_body'], $htmlVals)
            );
            $textRaw = trim((string) ($ov['text_body'] ?? ''));
            $textRendered = $textRaw !== ''
                ? MailPlaceholderRenderer::render($textRaw, $plainVals)
                : trim(strip_tags(preg_replace('#<br\s*/?>#i', "\n", $htmlRendered) ?? ''));

            $bodyText = $textRendered !== '' ? $textRendered : strip_tags($htmlRendered);

            return (new MailMessage)
                ->subject($subject)
                ->view([
                    'html' => 'mail.admin-html-override',
                    'text' => 'mail.admin-text-override',
                ], [
                    'appName' => config('app.name'),
                    'bodyHtml' => $htmlRendered,
                    'bodyText' => $bodyText,
                ]);
        }

        $data = [
            'appName' => config('app.name'),
            'resetUrl' => $url,
            'expireMinutes' => $expire,
        ];

        return (new MailMessage)
            ->subject($defaultSubject)
            ->view('mail.password-reset', $data)
            ->text('mail.password-reset-text', $data);
    }

    /**
     * @return array{subject: string, htmlView: string, textView: string, with: array<string, mixed>}
     */
    public function welcomeMailViews(User $user): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $plainVals = [
            'app_name' => (string) config('app.name'),
            'user_name' => $user->user_name,
            'chat_url' => $base.'/chat',
        ];
        $htmlVals = [
            'app_name' => e($plainVals['app_name']),
            'user_name' => e($plainVals['user_name']),
            'chat_url' => e($plainVals['chat_url']),
        ];

        $ov = $this->overrides()['welcome_registered'] ?? null;
        $defaultSubject = 'Ласкаво просимо — '.(string) config('app.name');
        if (is_array($ov) && trim((string) ($ov['html_body'] ?? '')) !== '') {
            $subject = trim((string) ($ov['subject'] ?? '')) !== '' ? trim((string) $ov['subject']) : $defaultSubject;
            $htmlRendered = MailTemplateSanitizer::html(
                MailPlaceholderRenderer::render((string) $ov['html_body'], $htmlVals)
            );
            $textRaw = trim((string) ($ov['text_body'] ?? ''));
            $textRendered = $textRaw !== ''
                ? MailPlaceholderRenderer::render($textRaw, $plainVals)
                : trim(strip_tags(preg_replace('#<br\s*/?>#i', "\n", $htmlRendered) ?? ''));

            return [
                'subject' => $subject,
                'htmlView' => 'mail.admin-html-override',
                'textView' => 'mail.admin-text-override',
                'with' => [
                    'appName' => config('app.name'),
                    'bodyHtml' => $htmlRendered,
                    'bodyText' => $textRendered !== '' ? $textRendered : strip_tags($htmlRendered),
                ],
            ];
        }

        return [
            'subject' => $defaultSubject,
            'htmlView' => 'mail.welcome-registered',
            'textView' => 'mail.welcome-registered-text',
            'with' => [
                'userName' => $user->user_name,
                'appName' => config('app.name'),
                'chatUrl' => $base.'/chat',
            ],
        ];
    }

    /**
     * @return array{subject: string, htmlView: string, textView: string, with: array<string, mixed>}
     */
    public function accountSecurityNoticeViews(User $user, string $headline, string $bodyLine): array
    {
        $plainVals = [
            'app_name' => (string) config('app.name'),
            'user_name' => $user->user_name,
            'headline' => $headline,
            'body_line' => $bodyLine,
        ];
        $htmlVals = [
            'app_name' => e($plainVals['app_name']),
            'user_name' => e($plainVals['user_name']),
            'headline' => e($headline),
            'body_line' => e($bodyLine),
        ];

        $ov = $this->overrides()['account_security_notice'] ?? null;
        $defaultSubject = $headline.' — '.(string) config('app.name');
        if (is_array($ov) && trim((string) ($ov['html_body'] ?? '')) !== '') {
            $subject = trim((string) ($ov['subject'] ?? '')) !== '' ? trim((string) $ov['subject']) : $defaultSubject;
            $htmlRendered = MailTemplateSanitizer::html(
                MailPlaceholderRenderer::render((string) $ov['html_body'], $htmlVals)
            );
            $textRaw = trim((string) ($ov['text_body'] ?? ''));
            $textRendered = $textRaw !== ''
                ? MailPlaceholderRenderer::render($textRaw, $plainVals)
                : trim(strip_tags(preg_replace('#<br\s*/?>#i', "\n", $htmlRendered) ?? ''));

            return [
                'subject' => $subject,
                'htmlView' => 'mail.admin-html-override',
                'textView' => 'mail.admin-text-override',
                'with' => [
                    'appName' => config('app.name'),
                    'bodyHtml' => $htmlRendered,
                    'bodyText' => $textRendered !== '' ? $textRendered : strip_tags($htmlRendered),
                ],
            ];
        }

        return [
            'subject' => $defaultSubject,
            'htmlView' => 'mail.account-security-notice',
            'textView' => 'mail.account-security-notice-text',
            'with' => [
                'userName' => $user->user_name,
                'appName' => config('app.name'),
                'headline' => $headline,
                'bodyLine' => $bodyLine,
            ],
        ];
    }
}
