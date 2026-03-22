# T51 — Глобальні опції чату (`chat_settings`)

## Статус: PASS

## Бекенд

- Таблиця `chat_settings` (один рядок за замовчуванням з міграції): `room_create_min_public_messages` (дефолт **100**), `public_message_count_scope` (`all_public_rooms` | `default_room_only`), опційно `message_count_room_id` → `rooms.room_id`.
- `GET /api/v1/chat/settings` — будь-який авторизований користувач (у т.ч. гість): лише безпечний зріз для UI (**T44**).
- `PATCH /api/v1/chat/settings` — лише адмін (`can:chat-admin`); при переході на `all_public_rooms` `message_count_room_id` скидається в null.
- `message_count_room_id` у PATCH валідується як існуючий `room_id` **з `access = 0`** (узгоджено з лічбою публічних повідомлень).

## Тести

- `php artisan test --filter ChatSettingsApiTest` — 5 passed.
- Повний `php artisan test` — 131 passed (остання перевірка при закритті T51).

## Фронт

- `ChatSettingsModal.vue` (RpModal): поріг N, область лічби, вибір публічної кімнати для `default_room_only`; відкриття з меню «я» → Налаштування (адмін).

## Документація

- `docs/chat-v2/openapi.yaml` — `getChatSettings`, `patchChatSettings`.

## Опційно для оператора

- Скрін модалки після PATCH; перевірка 403 під звичайним користувачем (curl/браузер).
