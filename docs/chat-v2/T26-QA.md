# T26 — QA: індекси під гарячі запити чату

## Рішення щодо `idx_chat_room_date`

**Залишаємо** індекс `(post_roomid, post_date DESC)` з T01.

- Поточні REST-ендпоінти стрічки та архіву сортують за **`post_id`** (`ChatMessageController`, `ChatArchiveController`); для цього додано **`idx_chat_room_post_id`** `(post_roomid, post_id)`.
- Індекс за датою лишається релевантним для можливих фільтрів/звітів за часом у межах кімнати та узгодження з `docs/board-te-ua/DATABASE-SCHEMA.md` §8; зайве навантаження на запис прийнятне на поточному масштабі. На staging з великим об’ємом варто порівняти плани та за потреби переглянути.

## Додані індекси

| Таблиця | Індекс | Запити / код |
|--------|--------|----------------|
| `chat` | `idx_chat_room_post_id` `(post_roomid, post_id)` | `WHERE post_roomid = ? … ORDER BY post_id DESC` (стрічка, архів) |
| `chat` | `idx_chat_file` `(file)` | `ImagePolicy::view` — `where('file', $image->id)` |
| `private_messages` | `idx_private_recipient_pair_id` `(recipient_id, sender_id, id)` | Симетрія до `idx_private_pair_id`; гілки `recipient_id = ?` у `PrivateMessageController::conversations` / `index` |

## EXPLAIN ANALYZE

На **чистій БД** (PHPUnit, SQLite in-memory) повнокровний `EXPLAIN ANALYZE` не відображає прод-навантаження. Для **MySQL staging** з даними рекомендовано зняти плани до/після для:

- `SELECT … FROM chat WHERE post_roomid = ? ORDER BY post_id DESC LIMIT …`
- `SELECT DISTINCT post_roomid FROM chat WHERE file = ?`
- агрегату розмов у `private_messages` для одного `user_id`

## Автоматичні перевірки

- `php artisan migrate` (чиста БД)
- `php artisan test` — у т.ч. `ChatApiTest`, `PrivateMessageApiTest`, `ChatArchiveApiTest`
- `npm run build` (без змін у фронті — регресія збірки)

**Статус:** PASS (див. актуальний вивід CI / локального запуску на момент закриття задачі).
