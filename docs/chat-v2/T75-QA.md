# T75 — QA evidence (інкремент 1: вітальня + поведінка звуку)

## Scope (зроблено в цьому інкременті)

- Міграція `chat_settings`: `landing_settings` (JSON), `registration_flags` (JSON), `sound_on_every_post` (bool).
- Публічний **`GET /api/v1/landing`** (без auth, throttle `landing-read`): `landing`, `registration`, `users_online`.
- Лічильник **`users_online`**: `COUNT(DISTINCT user_id)` у `sessions` за вікном `config('chat.landing_online_recent_session_seconds')` (лише `SESSION_DRIVER=database`).
- **`GET/PATCH /api/v1/chat/settings`**: у відповіді та PATCH — `sound_on_every_post`, `landing_settings`, `registration_flags` (нормалізація та валідація URL посилань).
- Vue: **`ChatSettingsModal`** — секції вітальні, реєстрації, прапорець legacy-звуку; **`AuthWelcome`** — двоколонковий макет (lg), дані з `/landing`, poll 45 с, прихована реєстрація за прапорцем.
- OpenAPI `0.17.0`: шлях `/api/v1/landing`, розширені `ChatSettingsData` / `PatchChatSettingsRequest`.

## Інкремент 2 — хаб адміна (навігація)

- Маршрут **`/chat/admin`** (`AdminHubView`): сітка карток — налаштування чату (через `?open_chat_settings=1` у чаті), користувачі, стоп-слова, черга, архів; передача `room` у query для зворотних посилань.
- Меню «я» для **адміна**: один пункт **«Панель адміна»** замість окремих пунктів налаштувань/користувачів/модерації; **модератор** (не адмін) — як раніше стоп-слова + черга.
- `ChatRoom`: відкриття **`ChatSettingsModal`** після bootstrap або при зміні query, якщо є `open_chat_settings`; параметр знімається з URL.
- **`RpModal`**: розміри `2xl`…`7xl`; **`ChatSettingsModal`**: `size="7xl"` для довгої форми.
- PHPUnit: `SpaShellTest::test_admin_hub_route_returns_spa_shell`.

## Що лишається для повного T75

- Додаткові вкладки / блоки (завантаження, обмеження, timezone тощо) — поетапно за чеклістом задачі.
- Реєстрація: поглиблені поля (multi-email, угоди тощо) без дублювання T76.
- Узгодження з **T77** (візуальний паритет вітальні з board.te.ua).

## Автоматичні перевірки

```bash
cd backend && php artisan test --filter=LandingApiTest
cd backend && php artisan test --filter=ChatSettingsApiTest
cd backend && php artisan test
cd backend && npm run build
```

## Ручний сценарій (опційно)

1. Адмін: налаштування чату — заповнити новину та посилання, зберегти; у іншому браузері (logout) відкрити `/` — текст і лінки відображаються; лічильник онлайн ≥ 0.
2. Вимкнути реєстрацію — таб «Реєстрація» зникає, форма недоступна.

## Вердикт

**PASS (інкремент 1)** після успішного виконання команд вище.
