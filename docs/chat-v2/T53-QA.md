# T53 — Автомодерація за стоп-словами

## Статус: PASS

## Продуктове рішення (зафіксовано в PR)

- **Публічні повідомлення в кімнаті** (`type=public`): застосовуються всі дії правил — `mask`, `reject`, `flag` (поле `chat.moderation_flag_at`), `temp_mute` (відхилення повідомлення + `mute_until` на користувача; тривалість з `mute_minutes` або `config('chat.automod_default_mute_minutes')`).
- **Приватні повідомлення та інлайн-приват у кімнаті:** лише **маскування** за правилами з `action=mask` (відхилення / мут / прапорець для публічного автомоду **не** застосовуються).
- **Персонал** (`user_rank` ≥ модератор): обхід жорстких дій автомоду в публічній кімнаті; маскування для привату лишається для всіх.
- **Збіг:** `substring` (як раніше) або `whole_word` (Unicode `\p{L}\p{N}` для меж слова).
- **API CRUD** `GET|POST|PATCH|DELETE /api/v1/mod/filter-words*`: **`can:moderate`** (модератор і адмін); IP-ban та staff users — без змін (адмін / окремі політики).

## Тести

- `php artisan test tests/Feature/StopWordAutomoderationTest.php`
- `php artisan test tests/Feature/ModerationApiTest.php` (модератор керує filter-words; звичайний користувач — 403)
- Повний `php artisan test` — PASS

## Фронт

- `/chat/staff-stop-words` — `StaffStopWordsView.vue`; пункт меню «я» → «Стоп-слова / фільтр» для модератора та адміна (`userBadgeMenuItems.js`).
- `npm run build` — PASS

## Документація

- `docs/chat-v2/openapi.yaml` — розширені схеми `ModFilterWord*`, `PATCH` для `{filterWordId}`.

## Логи

- Відхилення / тимчасовий мут: `moderation.automod.reject`, `moderation.automod.temp_mute` — без тіла повідомлення користувача.
- CRUD правил: `moderation.filter_word.created|updated|removed` — без повного тексту чату.
