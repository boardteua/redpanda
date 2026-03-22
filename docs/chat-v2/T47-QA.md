# T47 — Розділювач «нові повідомлення» / остання прочитана позиція

## Статус: PASS

## Правила продукту (зафіксовано в PR)

- **Модель:** таблиця `room_read_states` — пара `(user_id, room_id)` → `last_read_post_id` (nullable до першого mark-read).
- **Прочитане:** `POST /api/v1/rooms/{room}/read` з `last_read_post_id`; лише повідомлення, **видимі** користувачу в цій кімнаті (той самий фільтр, що й у `GET .../messages`); оновлення **монотонне** (не нижче за збережене).
- **Throttle:** `chat-mark-read` — 90 запитів/хв на користувача (`AppServiceProvider`).
- **GET історії:** `meta.last_read_post_id`; за `since_read=true` додано `meta.first_unread_post_id` (лише в межах повернутої сторінки).
- **Vue:** після завантаження кімнати — горизонтальний розділювач **перед** першим повідомленням з `post_id > last_read_post_id`, якщо маркер уже був; якщо маркера не було (`null`), лінію не показуємо. Позиція лінії **не зміщується** від нових подій WS у сесії. Зняття лінії: низ стрічки потрапляє у viewport (IntersectionObserver + коротке придушення після programmatic `scrollToBottom` після завантаження). Тоді ж — debounced `POST .../read` з останнім `post_id` у списку.

## Тести

- `php artisan test --filter ChatApiTest` — у т.ч. mark-read, monotonic, `meta`, `since_read`.
- `php artisan test` — повний прогін PASS.
- `npm run build` — PASS.

## Документація

- `docs/chat-v2/openapi.yaml` — `postRoomRead`, розширений `ChatMessageListResponse.meta`, параметр `since_read` на `GET .../messages`.

## Опційно для оператора

- Два клієнти / скрін: лінія між старими та новими після повторного заходу в кімнату з невичитаними повідомленнями.
