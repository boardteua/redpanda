# Налаштування SMTP (Gmail та інше) — T108

Короткий гайд для **staging/production**. Локально залишайте **`MAIL_MAILER=log`** або **`smtp`** на **Mailpit** (`backend/docker-compose.mailpit.yml`).

## Gmail (рекомендовано: порт 587 + STARTTLS)

1. Увімкніть **двофакторну автентифікацію** для облікового запису Google (за політикою workspace).
2. Створіть **App Password** (Google Account → Security → App passwords) і збережіть його **лише** в secret store / `.env` на сервері.
3. У `.env` на середовищі (приклад — узгодьте `MAIL_FROM_*` з дозволеним відправником / alias у Google):

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your.address@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your.address@gmail.com
MAIL_FROM_NAME="Назва продукту"
```

4. **`MAIL_SCHEME`:** для порту **587** зазвичай **не задавайте** (у Laravel 13 за замовчуванням використовується scheme **`smtp`**, Symfony Mailer застосовує **STARTTLS**, коли сервер його підтримує).
5. **`MAIL_FROM_ADDRESS`:** має відповідати обліковому запису або **підтвердженому alias** у Google Workspace / Gmail; інакше доставка може відхилятися.

### Порт 465 (implicit TLS / `smtps`)

Якщо провайдер вимагає лише 465:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=...
MAIL_PASSWORD=...
```

Перевірте документацію провайдера: не всі поєднання host/port/scheme взаємозамінні.

### Альтернатива: `MAIL_URL`

Один рядок DSN (без збереження в git):

```ini
MAIL_URL="smtp://USERNAME:PASSWORD@smtp.gmail.com:587"
```

Пріоритет і взаємодія з окремими `MAIL_HOST` / `MAIL_PORT` — див. [Laravel Mail](https://laravel.com/docs/13.x/mail).

## Ліміти та операційні очікування

- Gmail має **квоти** на кількість листів (залежно від типу акаунта); піки реєстрацій / скидань паролів можуть вдарити по ліміту.
- Для великих обсягів розгляньте **транзакційний постачальник** (SES, Postmark, Resend тощо) — окреме рішення продукту.

## Troubleshooting

| Симптом | Що перевірити |
|--------|----------------|
| Authentication failed | App Password (не звичайний пароль при 2FA); немає пробілів у `.env`; після змін — перезапуск PHP-FPM / worker |
| Connection timeout / TLS | Файрвол до `smtp.gmail.com:587` або `465`; коректність `MAIL_PORT` / `MAIL_SCHEME` |
| Листи «відхилені» / spam | SPF/DKIM для домену відправника (якщо використовуєте власний домен через Google); узгодженість `MAIL_FROM_ADDRESS` |
| Локально Mailpit «дивно» себе веде | Тимчасово `MAIL_MAILER=log` або переконайтеся, що контейнер Mailpit слухає **1025** |

## Кеш конфігурації

Після змін `MAIL_*` на сервері з **`php artisan config:cache`** перезапустіть воркери та PHP, щоб підхопити нові значення.

## Зв’язок із задачами

- **T109** — шаблони та **`TransactionalMailService`** покладаються на коректний **`MAIL_*`** з цього документа; типи листів — [T109-MAIL-TYPES.md](T109-MAIL-TYPES.md).
- Секрети **не** комітити; у репозиторії лише **`backend/.env.example`** з плейсхолдерами.
