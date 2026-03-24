# T110 — плейсхолдери в шаблонах листів

У полях **тема**, **HTML** і **текст** (збережені в `chat_settings.mail_template_overrides`) підтримуються підстановки у форматі **`{{ ключ }}`** (пробіли навколо ключа дозволені).

## `password_reset`

| Ключ | Зміст |
|------|--------|
| `app_name` | `config('app.name')` |
| `reset_url` | Повне посилання на SPA скидання пароля (`/reset-password?token=…&email=…`) |
| `expire_minutes` | Хвилини дії токена з `config/auth.php` |

## `welcome_registered`

| Ключ | Зміст |
|------|--------|
| `app_name` | Назва застосунку |
| `user_name` | Нік зареєстрованого користувача |
| `chat_url` | `APP_URL` + `/chat` |

## `account_security_notice`

| Ключ | Зміст |
|------|--------|
| `app_name` | Назва застосунку |
| `user_name` | Нік отримувача |
| `headline` | Заголовок події (аргумент коду) |
| `body_line` | Рядок опису (аргумент коду) |

Невідомі `{{ ключі }}` залишаються в тексті без змін. Після підстановки HTML проходить **`MailTemplateSanitizer`** (дозволені обмежені теги; `href` лише `http(s)` / `mailto:`).

**Ім’я відправника** (не адреса): поле **`transactional_mail_from_name`** у тих самих налаштуваннях чату; SMTP — [MAIL-SMTP.md](MAIL-SMTP.md).
