# T60 — QA (черга повідомлень на модерацію)

**Статус:** PASS

## Продуктові рішення (відкриті питання з чекліста)

- **Видалені повідомлення (`post_deleted_at`):** залишаються в черзі, щоб staff міг зняти прапорець; у відповіді API `snippet` порожній, `is_deleted: true` (текст не віддаємо).
- **Real-time / WS:** не впроваджувалось — оновлення списку кнопкою «Оновити» або після дії «Зняти прапорець».
- **«Відкрити в чаті»:** перехід на `/chat?room={post_roomid}&focus_post={post_id}`; прокрутка до рядка спрацьовує, лише якщо повідомлення потрапило в поточну сторінку історії кімнати (зараз `limit: 80` у `loadMessages`). Інакше параметр `focus_post` знімається з URL після завантаження без скролу.

## Backend

- `GET /api/v1/mod/flagged-messages` — `can:moderate`, throttle **`mod-flagged-read`**; query: `room_id`, `page`, `per_page` (1–100).
- `PATCH /api/v1/mod/flagged-messages/{message}` — зняття прапорця, throttle **`mod-actions`**; **422**, якщо прапорця не було.
- Індекс `idx_chat_moderation_flag_room` на `(moderation_flag_at, post_roomid)`.
- Логи: `moderation.flagged_message.cleared` (actor_id, post_id, post_roomid) — без повного тексту.

## Автоматичні тести

```text
php artisan test --filter=FlaggedMessagesModerationApiTest   # PASS
php artisan test --filter=SpaShellTest                       # у т.ч. /chat/staff-flagged — PASS
npm run build                                                 # PASS
```

## Frontend

- Маршрут `/chat/staff-flagged`, пункт меню «я» **«Черга на модерацію»** для модератора/адміна (`userBadgeMenuItems` + `ChatRoom.vue`).
- Таблиця: кімната, автор, уривок, час прапорця, дії «Відкрити в чаті» / «Зняти прапорець»; фільтр за id кімнати; пагінація; порожній стан.

## Ручний сценарій (оператор)

1. Правило стоп-слів з `action=flag` (T53) → повідомлення з прапорцем.
2. Меню «я» → «Черга на модерацію» → рядок у списку.
3. «Зняти прапорець» → рядок зникає після оновлення; у БД `moderation_flag_at` null.
4. За потреби — «Відкрити в чаті» і перевірка скролу для свіжого повідомлення у вікні історії.
