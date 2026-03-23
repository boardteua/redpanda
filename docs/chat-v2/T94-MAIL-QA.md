# T94 — ручний QA листів скидання пароля

Посилання в листі будується з **`APP_URL`** (див. `backend/.env.example`): має збігатися з origin SPA, інакше кнопка «Скинути пароль» веде на неправильний хост.

## Локально з Mailpit

1. У `backend/.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025` (або профіль з `backend/docker-compose.mailpit.yml`).
2. Підняти Mailpit, виконати запит `POST /api/v1/auth/forgot-password` з email існуючого користувача з паролем.
3. Відкрити UI Mailpit (`:8025`), перейти за посиланням → форма `/reset-password` у SPA → новий пароль → вхід.

Для швидкої перевірки без SMTP достатньо `MAIL_MAILER=array` або `log` — лінк шукати в масиві / у `laravel.log` (менш зручно).
