# T66 — Slash-команди: спільна інфраструктура — QA

**Вердикт:** PASS

## Доказ

- **PHPUnit:** `tests/Unit/Chat/SlashCommandParserTest.php`; у `tests/Feature/ChatApiTest.php`: `test_post_message_slash_me_and_idempotent_duplicate`, `test_slash_unknown_command_is_client_only_and_hidden_from_others`, `test_slash_unknown_does_not_dispatch_room_broadcast`; у `tests/Feature/ChatImageApiTest.php`: `test_slash_command_with_image_is_rejected`.
- **Збірка фронту:** `npm run build` у каталозі `backend/` — без помилок.

## Що перевірено

- Парсер: ім’я команди (нижній регістр), аргументи, `/` без імені — звичайний текст.
- Реєстр: `/me` → публічне повідомлення (як раніше); невідома команда → `type: client_only`, `meta.slash.client_only: true`, без `MessagePosted` у кімнату.
- `GET .../messages`: `client_only` видно лише автору; інший користувач не отримує рядок (тест з `Sanctum::actingAs` для перемикання користувача).
- Ліміт slash: 45 спроб / 60 с на користувача → `429` (реалізація через `RateLimiter::attempt`).
- `image_id` + рядок, що виглядає як slash-команда → `422`.
- Vue: моноширинний композер при провідному `/`; рядок `client_only` у стрічці з префіксом `>`; `newpost` не для `client_only`; видалення власного `client_only` з інференсу `can_delete`.

## Примітки

- Семантика конкретних команд (окрім `/me` та заглушки «невідома команда») — у T67+.
- Ручна перевірка WS для `client_only` не потрібна: broadcast не виконується.
