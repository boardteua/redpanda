<?php

namespace App\Services\Mail;

/**
 * Продуктові типи транзакційних листів (див. docs/chat-v2/T109-MAIL-TYPES.md).
 */
enum TransactionalMailKind: string
{
    /** Через Illuminate ResetPassword notification + MailMessage::view (не Mailable). */
    case PasswordReset = 'password_reset';

    case WelcomeRegistered = 'welcome_registered';

    /** Резерв для сповіщень на кшталт «новий вхід», зміни e-mail тощо. */
    case AccountSecurityNotice = 'account_security_notice';
}
