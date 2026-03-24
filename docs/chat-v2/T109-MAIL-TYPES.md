# T109 — типи транзакційних листів

Єдиний вхід для продуктової пошти: **`App\Services\Mail\TransactionalMailService`** (+ enum **`TransactionalMailKind`** для позначення типу в логах).

## Як додати новий тип

1. Додайте значення в **`TransactionalMailKind`**.
2. Створіть **`Mailable`** (за потреби **`ShouldQueue`**) з Blade у **`resources/views/mail/`** і спільним layout **`mail.layouts.transactional`**.
3. Додайте метод у **`TransactionalMailService`** з `try` / `catch` і **`Log::warning('transactional_mail_failed', ['kind' => …, 'exception' => …::class])`** без PII.
4. Покрийте **`Mail::fake()`** + `assertQueued` / `assertSent` у feature-тесті (у тестах `QUEUE_CONNECTION=sync` листи з `ShouldQueue` потрапляють у **`assertQueued`**).

Скидання пароля лишається через **`Illuminate\Auth\Notifications\ResetPassword`** і **`MailMessage::view()`** — URL SPA не змінювати без узгодження з **T94**.

Перевизначення текстів з адмінки (**T110**) — `chat_settings.mail_template_overrides`; плейсхолдери — [T110-MAIL-PLACEHOLDERS.md](T110-MAIL-PLACEHOLDERS.md).

Див. також [MAIL-SMTP.md](MAIL-SMTP.md) (**T108**).
