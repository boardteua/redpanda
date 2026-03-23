# T85 — Сайдбар: «Почати приватний чат» + автокомпліт по ніку — QA

**Вердикт:** PASS

## Доказ

- **PHPUnit:** `php artisan test --filter=UserAutocompleteApiTest` — PASS (авторизація, валідація `q`, префікс + порядок + ліміт, виключення себе / гостей / `account_disabled_at`, екранування LIKE, гість-сесія може викликати API); `php artisan test --filter=users_lookup` — PASS.
- **Фронт:** `npm run build` у `backend/` — PASS.

## Реалізація (індекс / rate limit)

- **Індекс:** колонка `users.user_name` уже **UNIQUE** (B-tree); запит `LIKE 'prefix%'` з літеральним префіксом залишається префіксним пошуком. Екранування `%`, `_`, `!` у введенні через `LIKE … ESCAPE '!'` (MySQL і SQLite у тестах).
- **Кеш Redis:** не використовується; при потребі можна додати TTL по нормалізованому префіксу окремою задачею.
- **Rate limit:** іменований limiter **`user-autocomplete`** — **45** запитів/хв на користувача (`AppServiceProvider`), маршрут у `routes/api.php`.
- **Контракт:** `GET /api/v1/users/autocomplete?q=` описано в `docs/chat-v2/openapi.yaml` (версія **0.17.3**); `GET /api/v1/users/lookup` без змін (точний нік).

## UI / a11y

- Підпис **«Почати приватний чат»**, `placeholder`, `aria-label` на комбобоксі; список підказок — `role="listbox"` / `role="option"`, `aria-activedescendant` при навігації ↑↓.
- Debounce **400 ms**, мінімум **2** символи перед запитом; до **15** підказок з API.

## Примітка

- Скрін UI у репозиторій не додавався; для повної відповідності чеклісту варто додати скрін префікс → підказка → відкритий приват у наступному рев’ю або за запитом.
